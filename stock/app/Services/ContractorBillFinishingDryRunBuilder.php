<?php

namespace App\Services;

use App\contractorBill;
use App\finishRate;
use App\invoice;
use App\setting;
use App\Support\ContractorBillFinishingCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContractorBillFinishingDryRunBuilder
{
    /**
     * @param  array<int, int>  $ids
     */
    public static function billsMatchingDryRunFilters(array $ids, Carbon $fromDt, Carbon $toDt): Collection
    {
        return contractorBill::query()
            ->whereIn('invoice_id', $ids)
            ->whereBetween('created_at', [$fromDt, $toDt])
            ->whereNotNull('finishing')
            ->where('finishing', '!=', '')
            ->where('finishing', '!=', '0')
            ->with(['invoice', 'product'])
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Same corrected rate/amount as dry-run Excel; updates contractor_bill.finishing_rate and amount.
     *
     * @return array{updated: int, skipped: int}
     */
    public static function applyCorrections(string $invoiceIdsCsv, string $createdFrom, string $createdTo): array
    {
        $ids = array_values(array_filter(array_map('intval', explode(',', $invoiceIdsCsv))));
        $fromDt = Carbon::parse($createdFrom)->startOfDay();
        $toDt = Carbon::parse($createdTo)->endOfDay();

        if ($ids === []) {
            return ['updated' => 0, 'skipped' => 0];
        }

        $finishRatesByName = finishRate::query()->get()->keyBy('name');
        $bills = self::billsMatchingDryRunFilters($ids, $fromDt, $toDt);

        if ($bills->isEmpty()) {
            return ['updated' => 0, 'skipped' => 0];
        }

        $invoiceCache = invoice::query()->whereIn('id', $bills->pluck('invoice_id')->unique()->all())->get()->keyBy('id');

        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($bills, $invoiceCache, $finishRatesByName, &$updated, &$skipped): void {
            foreach ($bills as $bill) {
                $inv = $invoiceCache->get((int) $bill->invoice_id);
                $product = $bill->product;
                if (! $inv || ! $product) {
                    $skipped++;

                    continue;
                }

                $finishingName = trim((string) $bill->finishing);
                $vol = ContractorBillFinishingCalculator::volumeCbm($product);
                $fr = $finishRatesByName->get($finishingName);
                $correctedRate = ContractorBillFinishingCalculator::finishingRateOldPriceFormula($fr, $product, $vol);
                $qty = (float) ($bill->quantity ?? 0);

                $bill->finishing_rate = $correctedRate;
                $bill->amount = $correctedRate * $qty;
                $bill->save();
                $updated++;
            }
        });

        return ['updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * Build ordered rows for {@see \App\Exports\ContractorBillFinishingDryRunExport}.
     *
     * @return array{0: Collection<int, array<int, mixed>>, 1: ?string} [rows, contractor_finishing_new_rate_from raw]
     */
    public static function buildRows(string $invoiceIdsCsv, string $createdFrom, string $createdTo): array
    {
        $ids = array_values(array_filter(array_map('intval', explode(',', $invoiceIdsCsv))));
        $fromDt = Carbon::parse($createdFrom)->startOfDay();
        $toDt = Carbon::parse($createdTo)->endOfDay();

        $companySettings = setting::query()->first();
        $cutoverRaw = $companySettings->contractor_finishing_new_rate_from ?? null;

        $finishRatesByName = finishRate::query()->get()->keyBy('name');

        $bills = self::billsMatchingDryRunFilters($ids, $fromDt, $toDt);

        if ($bills->isEmpty()) {
            return [collect(), $cutoverRaw];
        }

        $invoiceCache = invoice::query()->whereIn('id', $bills->pluck('invoice_id')->unique()->all())->get()->keyBy('id');

        $rows = collect();
        foreach ($bills as $bill) {
            /** @var contractorBill $bill */
            $inv = $invoiceCache->get((int) $bill->invoice_id);
            $product = $bill->product;
            if (! $inv || ! $product) {
                $rows->push([
                    $bill->id,
                    $bill->invoice_id,
                    $inv ? (string) ($inv->invoiceno ?? '') : '',
                    $inv ? (string) ($inv->date ?? '') : '',
                    $bill->created_at?->format('Y-m-d H:i:s') ?? '',
                    $bill->product_id,
                    $product ? (string) ($product->code ?? '') : '',
                    $bill->contractor_id,
                    (string) $bill->finishing,
                    $bill->quantity,
                    '',
                    $bill->finishing_rate,
                    $bill->amount,
                    'MISSING_INVOICE_OR_PRODUCT',
                    '',
                    '',
                    '',
                    '',
                    '',
                ]);

                continue;
            }

            $finishingName = trim((string) $bill->finishing);
            $vol = ContractorBillFinishingCalculator::volumeCbm($product);

            $fr = $finishRatesByName->get($finishingName);
            $qty = (float) ($bill->quantity ?? 0);

            $storedRate = (float) ($bill->finishing_rate ?? 0);
            $storedAmount = (float) ($bill->amount ?? 0);

            $correctedRate = ContractorBillFinishingCalculator::finishingRateOldPriceFormula(
                $fr,
                $product,
                $vol
            );
            $correctedAmount = $correctedRate * $qty;

            $masterOld = ($fr !== null && $fr->old_rate !== null && $fr->old_rate !== '')
                ? (float) $fr->old_rate
                : '';
            $masterCur = $fr !== null ? (float) ($fr->rate ?? 0) : '';

            $rows->push([
                $bill->id,
                $bill->invoice_id,
                (string) ($inv->invoiceno ?? ''),
                (string) ($inv->date ?? ''),
                $bill->created_at?->format('Y-m-d H:i:s') ?? '',
                $bill->product_id,
                (string) ($product->code ?? ''),
                $bill->contractor_id,
                $finishingName,
                $qty,
                round($vol, 6),
                round($storedRate, 4),
                round($storedAmount, 4),
                round($correctedRate, 4),
                round($correctedAmount, 4),
                round($correctedRate - $storedRate, 4),
                round($correctedAmount - $storedAmount, 4),
                $masterOld,
                $masterCur,
            ]);
        }

        return [$rows, $cutoverRaw];
    }
}
