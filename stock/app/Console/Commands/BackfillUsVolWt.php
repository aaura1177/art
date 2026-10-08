<?php

namespace App\Console\Commands;

use App\pricingTable;
use App\setting;
use App\Support\PricingVolumetricWeight;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackfillUsVolWt extends Command
{
    protected $signature = 'pricing:backfill-us-volwt
                            {--apply : Write corrected lbs volWt to US rows (default is dry-run)}
                            {--report= : Optional CSV path under storage/app}';

    protected $description = 'Backfill volWt on US destination pricing rows using the US lbs volumetric formula.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $firstSetting = setting::first();
        if (! $firstSetting) {
            $this->error('No settings row found.');

            return self::FAILURE;
        }
        $settingData = $firstSetting->toArray();

        $rows = pricingTable::query()
            ->whereRaw('LOWER(TRIM(destination)) = ?', ['us'])
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No US destination pricing rows found.');

            return self::SUCCESS;
        }

        $this->line($apply ? '=== APPLY MODE ===' : '=== DRY RUN (pass --apply to update) ===');
        $this->info('Scanning ' . $rows->count() . ' US row(s).');

        $reportRows = [];
        $changed = 0;
        $skipped = 0;
        $unchanged = 0;

        foreach ($rows as $row) {
            $pricingData = [
                'boxwidth' => $row->boxwidth,
                'boxheight' => $row->boxheight,
                'boxdepth' => $row->boxdepth,
            ];
            $newVolWt = PricingVolumetricWeight::forDestination($pricingData, $settingData, 'us');
            $oldVolWt = (float) ($row->volWt ?? 0);

            if ($newVolWt <= 0) {
                $skipped++;
                $reportRows[] = [
                    'id' => $row->id,
                    'product_id' => $row->product_id,
                    'old_volWt' => $oldVolWt,
                    'new_volWt' => '',
                    'status' => 'skipped_no_dims',
                ];
                continue;
            }

            if (abs($newVolWt - $oldVolWt) < 0.01) {
                $unchanged++;
                $reportRows[] = [
                    'id' => $row->id,
                    'product_id' => $row->product_id,
                    'old_volWt' => $oldVolWt,
                    'new_volWt' => $newVolWt,
                    'status' => 'unchanged',
                ];
                continue;
            }

            $changed++;
            $reportRows[] = [
                'id' => $row->id,
                'product_id' => $row->product_id,
                'old_volWt' => $oldVolWt,
                'new_volWt' => $newVolWt,
                'status' => $apply ? 'updated' : 'would_change',
            ];

            if ($apply) {
                pricingTable::where('id', $row->id)->update(['volWt' => $newVolWt]);
            }
        }

        $this->info("Would change / changed: {$changed}");
        $this->info("Unchanged: {$unchanged}");
        $this->info("Skipped (no dimensions): {$skipped}");

        $preview = collect($reportRows)->whereIn('status', ['would_change', 'updated'])->take(50);
        if ($preview->isNotEmpty()) {
            $this->table(
                ['id', 'product_id', 'old_volWt', 'new_volWt', 'status'],
                $preview->values()->all()
            );
            if ($changed > 50) {
                $this->comment('... and ' . ($changed - 50) . ' more changed row(s).');
            }
        }

        $reportPath = $this->option('report');
        if ($reportPath) {
            $csv = "id,product_id,old_volWt,new_volWt,status\n";
            foreach ($reportRows as $line) {
                $csv .= implode(',', [
                    $line['id'],
                    $line['product_id'],
                    $line['old_volWt'],
                    $line['new_volWt'],
                    $line['status'],
                ]) . "\n";
            }
            Storage::disk('local')->put(ltrim($reportPath, '/'), $csv);
            $this->info('Report written to storage/app/' . ltrim($reportPath, '/'));
        }

        if (! $apply && $changed > 0) {
            $this->comment('No changes made. Re-run with --apply to update volWt on US rows.');
        }

        return self::SUCCESS;
    }
}
