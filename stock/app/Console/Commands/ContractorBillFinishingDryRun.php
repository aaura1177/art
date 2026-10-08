<?php

namespace App\Console\Commands;

use App\Exports\ContractorBillFinishingDryRunExport;
use App\Services\ContractorBillFinishingDryRunBuilder;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ContractorBillFinishingDryRun extends Command
{
    protected $signature = 'contractor-bill:finishing-dry-run
                            {--invoice-ids=1053,1055,1056,1059,1058,1062,1063,1050 : Comma-separated invoice IDs}
                            {--created-from=2026-04-01 : contractor_bill.created_at from date (inclusive)}
                            {--created-to=2026-04-02 : contractor_bill.created_at to date (inclusive)}
                            {--output= : Relative path under storage/app (default: reports/contractor_bill_finishing_dry_run_TIMESTAMP.xlsx)}';

    protected $description = 'Dry-run Excel: contractor_bill stored vs corrected old-price formula. No DB updates.';

    public function handle(): int
    {
        $csv = (string) $this->option('invoice-ids');
        $ids = array_values(array_filter(array_map('intval', explode(',', $csv))));
        if ($ids === []) {
            $this->error('No invoice IDs.');

            return self::FAILURE;
        }

        $from = (string) $this->option('created-from');
        $to = (string) $this->option('created-to');

        [$rows, $cutoverRaw] = ContractorBillFinishingDryRunBuilder::buildRows($csv, $from, $to);

        if ($rows->isEmpty()) {
            $this->warn('No contractor_bill rows matched filters.');

            return self::SUCCESS;
        }

        $out = $this->option('output');
        if (! $out) {
            $out = 'reports/contractor_bill_finishing_dry_run_' . now()->format('Y-m-d_His') . '.xlsx';
        }

        Excel::store(new ContractorBillFinishingDryRunExport($rows), $out, 'local');
        $full = storage_path('app/' . $out);
        $this->info('Rows: ' . $rows->count());
        $this->info('Wrote: ' . $full);
        $this->line('Cutover (contractor_finishing_new_rate_from): ' . ($cutoverRaw ?? '(null → 1970-01-01)'));

        return self::SUCCESS;
    }
}
