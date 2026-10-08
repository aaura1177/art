<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class SustainabilityCountryEmissions
{
    public const COUNTRIES = ['UK', 'US', 'EU', 'CA', 'IN'];

    /**
     * Map invoice destination text to a market country.
     */
    public static function mapDestinationToCountry(?string $destination): string
    {
        $d = strtoupper(trim((string) $destination));

        if ($d === '') {
            return 'OTHER';
        }

        if (
            str_contains($d, 'UK')
            || str_contains($d, 'UNITED KINGDOM')
            || str_contains($d, 'FELIXSTOWE')
            || str_contains($d, 'SOUTHAMPTON')
        ) {
            return 'UK';
        }

        if (
            str_contains($d, 'USA')
            || str_contains($d, 'UNITED STATES')
            || str_contains($d, 'NEW YORK')
            || str_contains($d, 'LONG BEACH')
            || str_contains($d, 'NJ/')
            || str_contains($d, 'CALIFORNIA')
        ) {
            return 'US';
        }

        if (
            str_contains($d, 'CANADA')
            || str_contains($d, 'TORONTO')
            || str_contains($d, 'MONTREAL')
        ) {
            return 'CA';
        }

        if (
            str_contains($d, 'GERMANY')
            || str_contains($d, 'HAMBURG')
            || str_contains($d, 'EU')
            || str_contains($d, 'NETHERLAND')
            || str_contains($d, 'FRANCE')
            || str_contains($d, 'ITALY')
            || str_contains($d, 'SPAIN')
        ) {
            return 'EU';
        }

        if (
            str_contains($d, 'INDIA')
            || str_contains($d, 'JAIPUR')
            || $d === 'IN'
        ) {
            return 'IN';
        }

        return 'OTHER';
    }

    /**
     * Site invoice + Stage 5 emission-log pairs (same as Stage 5 populate).
     *
     * @return array<int, array{invoice_table: string, log_table: string}>
     */
    protected static function stage5InvoiceSources(): array
    {
        return [
            ['invoice_table' => 'invoice', 'log_table' => 'emission_invoice_logs_in'],
            ['invoice_table' => 'invoice_uk', 'log_table' => 'emission_invoice_logs_uk'],
            ['invoice_table' => 'invoice_us', 'log_table' => 'emission_invoice_logs_us'],
            ['invoice_table' => 'invoice_eu', 'log_table' => 'emission_invoice_logs_eu'],
            ['invoice_table' => 'invoice_canada', 'log_table' => 'emission_invoice_logs_ca'],
            ['invoice_table' => 'invoice_california', 'log_table' => 'emission_invoice_logs_california'],
        ];
    }

    /**
     * Invoices that were actually processed into Stage 5 (emission logs),
     * matching saved Stage 5 / Stage 0 qty.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    protected static function stage5ProcessedInvoices(string $startDate, string $endDate)
    {
        $processedMonths = DB::table('sustainability_stage5')
            ->whereBetween('month_year', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('month_year')
            ->all();

        if (empty($processedMonths)) {
            return collect();
        }

        $rows = collect();

        foreach (self::stage5InvoiceSources() as $source) {
            $invoiceIds = DB::table($source['log_table'])
                ->whereIn('processed_month', $processedMonths)
                ->pluck('invoice_id')
                ->unique()
                ->values()
                ->all();

            if (empty($invoiceIds)) {
                continue;
            }

            $batch = DB::table($source['invoice_table'] . ' as i')
                ->leftJoin('ports as p', 'i.discharge', '=', 'p.name')
                ->whereIn('i.id', $invoiceIds)
                ->whereBetween('i.date', [$startDate, $endDate])
                ->whereNotNull('i.date')
                ->where('i.invoiceno', 'like', 'GVD/UK/%')
                ->where('i.invoiceno', 'not like', '%TEMP%')
                ->whereRaw('(i.containerno IS NULL OR UPPER(TRIM(i.containerno)) != ?)', ['TBC'])
                ->where('i.is_canceled', 0)
                ->select(
                    'i.destination',
                    'i.postloading',
                    'i.totalquantity',
                    'p.carbon_emission_per_container',
                    'p.name as port_name'
                )
                ->get();

            $rows = $rows->concat($batch);
        }

        return $rows;
    }

    /**
     * Build destination shares from Stage 5–processed invoices only.
     * Used only to allocate saved Stage totals — not as emission amounts.
     * Qty base aligns with saved Stage 0 / Stage 5 qty.
     *
     * @return array{qty: array<string,float>, s4: array<string,int>, s5: array<string,float>, qty_total: float, s4_total: int, s5_total: float}
     */
    public static function destinationShares(string $startDate, string $endDate): array
    {
        $keys = array_merge(self::COUNTRIES, ['OTHER']);
        $qty = array_fill_keys($keys, 0.0);
        $s4 = array_fill_keys($keys, 0);
        $s5 = array_fill_keys($keys, 0.0);

        foreach (self::stage5ProcessedInvoices($startDate, $endDate) as $row) {
            // Stage 5 only stores rows with a matched port
            if (!$row->port_name) {
                continue;
            }

            $country = self::mapDestinationToCountry($row->destination);
            $qty[$country] += (float) $row->totalquantity;
            $s5[$country] += (float) $row->carbon_emission_per_container;

            if (SustainabilityStage4Postloading::isEligible($row->postloading)) {
                $s4[$country]++;
            }
        }

        return [
            'qty' => $qty,
            's4' => $s4,
            's5' => $s5,
            'qty_total' => array_sum($qty),
            's4_total' => array_sum($s4),
            's5_total' => array_sum($s5),
        ];
    }

    /**
     * List OTHER destinations from Stage 5–processed invoices only.
     *
     * @return array<int, array{destination: string, invoices: int, qty: float}>
     */
    public static function otherDestinations(string $startDate, string $endDate): array
    {
        $grouped = [];

        foreach (self::stage5ProcessedInvoices($startDate, $endDate) as $row) {
            if (!$row->port_name) {
                continue;
            }

            if (self::mapDestinationToCountry($row->destination) !== 'OTHER') {
                continue;
            }

            $key = $row->destination ?: '(blank)';
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['destination' => $key, 'invoices' => 0, 'qty' => 0.0];
            }
            $grouped[$key]['invoices']++;
            $grouped[$key]['qty'] += (float) $row->totalquantity;
        }

        $other = array_values(array_map(function ($row) {
            $row['qty'] = round($row['qty'], 2);
            return $row;
        }, $grouped));

        usort($other, fn ($a, $b) => $b['qty'] <=> $a['qty']);

        return $other;
    }

    /**
     * Build one allocation row for a country/bucket key.
     */
    protected static function allocationRow(
        string $key,
        array $shares,
        array $s6ByLocation,
        float $savedS123Period,
        float $savedS4Period,
        float $savedS5Period,
        float $savedQtyPeriod
    ): array {
        $qtyShare = $shares['qty_total'] > 0
            ? $shares['qty'][$key] / $shares['qty_total']
            : 0.0;
        $s4Share = $shares['s4_total'] > 0
            ? $shares['s4'][$key] / $shares['s4_total']
            : 0.0;
        $s5Share = $shares['s5_total'] > 0
            ? $shares['s5'][$key] / $shares['s5_total']
            : 0.0;

        $qtyCountry = $savedQtyPeriod * $qtyShare;
        $alloc123 = $savedS123Period * $qtyShare;
        $alloc4 = $savedS4Period * $s4Share;
        $alloc5 = $savedS5Period * $s5Share;
        $alloc6 = $s6ByLocation[$key] ?? 0.0;
        $totalCountry = $alloc123 + $alloc4 + $alloc5 + $alloc6;
        $perProduct = $qtyCountry > 0 ? $totalCountry / $qtyCountry : 0.0;

        return [
            'country' => $key,
            'invoice_qty' => round($shares['qty'][$key], 2),
            'invoice_s4_containers' => (int) $shares['s4'][$key],
            'invoice_s5_signal' => round($shares['s5'][$key], 2),
            'qty_share' => round($qtyShare * 100, 2),
            's4_share' => round($s4Share * 100, 2),
            's5_share' => round($s5Share * 100, 2),
            'qty_country' => round($qtyCountry, 2),
            'stage_1_3' => round($alloc123, 5),
            'stage_4' => round($alloc4, 5),
            'stage_5' => round($alloc5, 5),
            'stage_6' => round($alloc6, 5),
            'total_carbon' => round($totalCountry, 5),
            'per_product' => round($perProduct, 5),
        ];
    }

    /**
     * Detailed per-country calculation breakdown for UI explanation.
     *
     * @return array{
     *   inputs: array<string,float|int>,
     *   share_bases: array<string,float|int>,
     *   countries: array<int, array<string, mixed>>,
     *   other: array<string, mixed>
     * }
     */
    public static function countryBreakdown(
        string $startDate,
        string $endDate,
        float $savedS123Period,
        float $savedS4Period,
        float $savedS5Period,
        float $savedQtyPeriod
    ): array {
        $shares = self::destinationShares($startDate, $endDate);

        $s6ByLocation = DB::table('sustainability_stage6')
            ->select('location', DB::raw('SUM(carbon_emission) as emission'))
            ->whereBetween('month_year', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->groupBy('location')
            ->pluck('emission', 'location')
            ->map(fn ($v) => (float) $v)
            ->all();

        $savedS6Period = array_sum($s6ByLocation);
        $countries = [];

        foreach (self::COUNTRIES as $country) {
            $countries[] = self::allocationRow(
                $country,
                $shares,
                $s6ByLocation,
                $savedS123Period,
                $savedS4Period,
                $savedS5Period,
                $savedQtyPeriod
            );
        }

        $otherRow = self::allocationRow(
            'OTHER',
            $shares,
            $s6ByLocation,
            $savedS123Period,
            $savedS4Period,
            $savedS5Period,
            $savedQtyPeriod
        );

        $sumFive = array_sum(array_column($countries, 'total_carbon'));

        return [
            'inputs' => [
                'stage_1_3' => round($savedS123Period, 5),
                'stage_4' => round($savedS4Period, 5),
                'stage_5' => round($savedS5Period, 5),
                'stage_6' => round($savedS6Period, 5),
                'qty' => round($savedQtyPeriod, 2),
                'total_carbon' => round($savedS123Period + $savedS4Period + $savedS5Period + $savedS6Period, 5),
            ],
            'share_bases' => [
                'qty_total' => round($shares['qty_total'], 2),
                's4_total' => (int) $shares['s4_total'],
                's5_total' => round($shares['s5_total'], 2),
            ],
            'countries' => $countries,
            'other' => [
                'summary' => $otherRow,
                'destinations' => self::otherDestinations($startDate, $endDate),
                'five_countries_total' => round($sumFive, 5),
                'check_total' => round($sumFive + $otherRow['total_carbon'], 5),
            ],
        ];
    }

    /**
     * Per Product Emissions by country using saved stage totals + destination shares.
     *
     * Per Product (C) =
     *   (saved_S1–3 × qty_share(C) + saved_S4 × s4_share(C) + saved_S5 × s5_share(C) + saved_S6(C))
     *   ÷ (saved_qty × qty_share(C))
     *
     * @return array<string, float|string> label => value
     */
    public static function perProductByCountry(
        string $startDate,
        string $endDate,
        float $savedS123Period,
        float $savedS4Period,
        float $savedS5Period,
        float $savedQtyPeriod
    ): array {
        $breakdown = self::countryBreakdown(
            $startDate,
            $endDate,
            $savedS123Period,
            $savedS4Period,
            $savedS5Period,
            $savedQtyPeriod
        );

        $result = [];
        foreach ($breakdown['countries'] as $row) {
            $result["Per Product Emissions ({$row['country']})"] = $row['per_product'];
        }

        return $result;
    }
}
