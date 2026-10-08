<?php

namespace App\Console\Commands;

use App\pricingTable;
use App\Services\PricingCourierRecalcService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RecalcCourierChargeableWeight extends Command
{
    protected $signature = 'pricing:recalc-courier-chargeable
                            {--destination=uk : Destination country code (uk, us, eu, canada, etc.)}
                            {--apply : Write changes to DB (default is dry-run)}
                            {--report= : Optional CSV path under storage/app}';

    protected $description = 'Recalculate courier/delivery costs using max(volumetric, box) chargeable weight.';

    public function handle(): int
    {
        $destination = strtolower(trim((string) $this->option('destination')));
        $apply = (bool) $this->option('apply');

        $rows = pricingTable::query()
            ->whereRaw('LOWER(TRIM(destination)) = ?', [$destination])
            ->whereNotNull('courierType')
            ->where('courierType', '!=', '')
            ->where('courierType', '!=', '0')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info("No pricing rows with courier for destination [{$destination}].");

            return self::SUCCESS;
        }

        $this->line($apply ? '=== APPLY MODE ===' : '=== DRY RUN (pass --apply to update) ===');
        $this->info("Scanning {$rows->count()} row(s) for destination [{$destination}]");

        $reportRows = [];
        $changed = 0;
        $errors = 0;

        foreach ($rows as $row) {
            try {
                $result = PricingCourierRecalcService::recalculateForRow($row);
            } catch (\Throwable $e) {
                $errors++;
                $reportRows[] = $this->reportLine($row, null, 'ERROR: ' . $e->getMessage());
                continue;
            }

            if ($result === null) {
                $reportRows[] = $this->reportLine($row, null, 'Skipped: no courier match');
                continue;
            }

            $oldCourier = round((float) $row->courierCost, 2);
            $newCourier = $result['courierCost'];
            $delta = round($newCourier - $oldCourier, 2);
            $isChanged = abs($delta) >= 0.01
                || (int) $row->newDelCost !== (int) $result['newDelCost'];

            if ($isChanged) {
                $changed++;
                if ($apply) {
                    pricingTable::where('id', $row->id)->update([
                        'courierCost' => $result['courierCost'],
                        'deliveryCost' => $result['deliveryCost'],
                        'newDelCost' => $result['newDelCost'],
                    ]);
                }
            }

            $reportRows[] = $this->reportLine($row, $result, $isChanged ? 'CHANGED' : 'unchanged');
        }

        $this->info("Rows with cost change: {$changed}");
        if ($errors > 0) {
            $this->warn("Errors: {$errors}");
        }

        $preview = array_slice($reportRows, 0, 30);
        $this->table(array_keys($preview[0] ?? []), array_map('array_values', $preview));
        if (count($reportRows) > 30) {
            $this->comment('... and ' . (count($reportRows) - 30) . ' more rows.');
        }

        $reportPath = $this->option('report');
        if ($reportPath) {
            $csv = $this->toCsv($reportRows);
            Storage::disk('local')->put(ltrim($reportPath, '/'), $csv);
            $this->info('Report written: storage/app/' . ltrim($reportPath, '/'));
        }

        if (! $apply) {
            $this->comment('No DB updates. Re-run with --apply to write changes.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>|null  $result
     * @return array<string, string|int|float>
     */
    private function reportLine(pricingTable $row, ?array $result, string $status): array
    {
        return [
            'pricing_id' => $row->id,
            'product_id' => $row->product_id,
            'courier' => (string) $row->courierType,
            'chargeable' => $result['chargeableWeight'] ?? '',
            'old_courier' => round((float) $row->courierCost, 2),
            'new_courier' => $result['courierCost'] ?? '',
            'old_newDelCost' => (int) $row->newDelCost,
            'new_newDelCost' => $result['newDelCost'] ?? '',
            'status' => $status,
        ];
    }

    /**
     * @param  array<int, array<string, string|int|float>>  $rows
     */
    private function toCsv(array $rows): string
    {
        if ($rows === []) {
            return '';
        }
        $out = fopen('php://temp', 'r+');
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($out, array_values($row));
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv === false ? '' : $csv;
    }
}
