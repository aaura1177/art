<?php

namespace App\Console\Commands;

use App\purchaseOrderConsumable;
use App\Support\ConsumableMonthEndInvoiceSupport;
use App\Support\MonthEndPoDeletePlan;
use App\Support\MonthEndPoDeleteService;
use Illuminate\Console\Command;

class DeleteMonthEndMseriesPos extends Command
{
    protected $signature = 'monthend-po:purge-mseries
                            {--apply : Step 2 — permanently delete (default is Step 1 dry-run only)}
                            {--yes : Skip confirmation when using --apply}
                            {--force : Delete even when PO status is not pending (approved / complete)}
                            {--include-hardware : Also purge hardware month-end POs, not only M-series}
                            {--po-id= : Limit to one purchase_order_consumables id}
                            {--pono= : Limit to one PO number e.g. M/7}
                            {--report= : Write full row-by-row dry-run detail to this file path}';

    protected $description = 'M-series month-end: dry-run lists every related id (challan, SI, PB, poc lines…), then --apply purges.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $force = (bool) $this->option('force');
        $includeHardware = (bool) $this->option('include-hardware');
        $poId = $this->filled('po-id') ? (int) $this->option('po-id') : null;
        $pono = $this->filled('pono') ? (string) $this->option('pono') : null;
        $reportPath = $this->filled('report') ? (string) $this->option('report') : null;

        $pos = $this->loadPos($poId, $pono, $includeHardware);
        if ($pos->isEmpty()) {
            $this->warn('No M-series month-end consumable POs matched.');

            return self::SUCCESS;
        }

        $requirePending = ! $force;
        $plans = [];
        foreach ($pos as $po) {
            $plans[] = MonthEndPoDeletePlan::forPo($po, $requirePending);
        }

        $this->printBanner($apply, $includeHardware, $force, count($plans));

        $summaryRows = [];
        $deletable = [];
        $blockedCount = 0;
        $totalRows = 0;

        foreach ($plans as $plan) {
            $note = $plan->blocked ? implode('; ', $plan->blockReasons) : 'OK';
            if ($plan->blocked) {
                $blockedCount++;
            } else {
                $deletable[] = $plan;
            }
            $c = $plan->countsByEntity();
            $rowDeletes = $plan->totalRowDeletes();
            $totalRows += $rowDeletes;

            $summaryRows[] = [
                $plan->kind . ':' . $plan->po->pono,
                (string) $plan->po->id,
                (string) $plan->po->status,
                $c['Challan'] ?? 0,
                $c['ChallanProduct'] ?? 0,
                $c['SupplierInvoice'] ?? 0,
                $c['SupplierInvoiceLine'] ?? 0,
                $c['PurchaseBill'] ?? 0,
                $c['PurchaseBillLine'] ?? 0,
                $c['PoLine'] ?? 0,
                $c['StockLog'] ?? 0,
                $rowDeletes,
                $note,
            ];
        }

        $this->table(
            ['PO', 'PO id', 'St', 'Challan', 'ChLn', 'SI', 'SIP', 'PB', 'PBL', 'poc', 'StkLog', 'Total', 'Note'],
            $summaryRows
        );

        $this->newLine();
        $this->info('What gets removed per PO (yes — all of these were deleted on your earlier --apply run):');
        $this->line('  • supplier_invoices + supplier_invoice_products (+ returns, unique refs if any)');
        $this->line('  • purchase_bill_consumables + pb_table_consumables');
        $this->line('  • challan + challan_products');
        $this->line('  • pocTable lines, popTable, month-end stock_log_consumable (qty reversed)');
        $this->line('  • purchase_order_consumables row');
        $this->newLine();
        $this->info('Summary');
        $this->line('  POs matched: ' . count($plans));
        $this->line('  POs deletable: ' . count($deletable));
        $this->line('  POs blocked: ' . $blockedCount);
        $this->line('  Total records in delete plan: ' . $totalRows);

        if (! $apply) {
            $this->printDetailPreview($plans);
        }

        if ($reportPath !== null && ! $apply) {
            $this->writeReport($reportPath, $plans);
            $this->info('Full row-by-row dry-run report written: ' . $reportPath);
        }

        if ($deletable === []) {
            $this->warn('Nothing to delete. Use --force if POs are no longer pending.');

            return self::SUCCESS;
        }

        if (! $apply) {
            $this->newLine();
            $this->comment('Step 1 complete (dry-run). No database changes.');
            $this->comment('Step 2: php artisan monthend-po:purge-mseries --apply --force --yes --include-hardware');
            $this->comment('Report:  php artisan monthend-po:purge-mseries --report=stock/monthend-purge-dryrun.txt');

            return self::SUCCESS;
        }

        if (! (bool) $this->option('yes')) {
            $msg = sprintf(
                'Permanently delete %d record(s) across %d month-end PO(s)?',
                $totalRows,
                count($deletable)
            );
            if (! $this->confirm($msg, false)) {
                $this->warn('Aborted — no changes written.');

                return self::FAILURE;
            }
        }

        $deleted = 0;
        foreach ($deletable as $plan) {
            try {
                MonthEndPoDeleteService::purge($plan);
                $deleted++;
                $this->line(sprintf(
                    'Deleted %s:%s (PO id %d) — %d related record(s)',
                    $plan->kind,
                    $plan->po->pono,
                    $plan->po->id,
                    $plan->totalRowDeletes()
                ));
            } catch (\Throwable $e) {
                $this->error(sprintf(
                    'Failed %s:%s (id %d): %s',
                    $plan->kind,
                    $plan->po->pono,
                    $plan->po->id,
                    $e->getMessage()
                ));

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info(sprintf('Step 2 complete — %d PO(s), %d record(s) purged.', $deleted, $totalRows));

        return self::SUCCESS;
    }

    /**
     * @param  list<MonthEndPoDeletePlan>  $plans
     */
    private function printDetailPreview(array $plans): void
    {
        $this->newLine();
        $this->info('Row-by-row delete plan (first 80 rows; use --report= for full file):');

        $detailRows = [];
        foreach ($plans as $plan) {
            foreach ($plan->deleteRows as $row) {
                $detailRows[] = [
                    $row['po_pono'],
                    $row['action'],
                    $row['entity'],
                    $row['table'],
                    $row['id'],
                    mb_substr($row['detail'], 0, 80),
                ];
            }
        }

        $this->table(['PO', 'Action', 'Entity', 'Table', 'id', 'Detail'], array_slice($detailRows, 0, 80));

        if (count($detailRows) > 80) {
            $this->comment('  … ' . (count($detailRows) - 80) . ' more rows (use --report=path for complete list)');
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, purchaseOrderConsumable>
     */
    private function loadPos(?int $poId, ?string $pono, bool $includeHardware)
    {
        $query = purchaseOrderConsumable::query()
            ->where('type', 1)
            ->where('address_option', 100)
            ->orderBy('pono');

        if ($poId) {
            $query->where('id', $poId);
        }
        if ($pono) {
            $query->where('pono', $pono);
        } elseif (! $includeHardware) {
            $query->where('pono', 'like', 'M/%');
        }

        return $query->get()->filter(function (purchaseOrderConsumable $po) use ($includeHardware) {
            if (! ConsumableMonthEndInvoiceSupport::isMonthEndConsumablePo($po)) {
                return false;
            }

            return $includeHardware
                ? true
                : ConsumableMonthEndInvoiceSupport::isMseriesMonthEndConsumablePo($po);
        })->values();
    }

    private function printBanner(bool $apply, bool $includeHardware, bool $force, int $count): void
    {
        $scope = $includeHardware ? 'M-series + hardware month-end' : 'M-series only (pono M/…)';

        if ($apply) {
            $this->warn('STEP 2 — PURGE (permanent deletes)');
        } else {
            $this->info('STEP 1 — DRY-RUN (preview only, no database writes)');
        }

        $this->line('Scope: ' . $scope);
        $this->line('Pending only: ' . ($force ? 'no (--force)' : 'yes (use --force for approved POs)'));
        $this->line('POs matched: ' . $count);
        $this->newLine();
    }

    private function filled(string $name): bool
    {
        $v = $this->option($name);

        return $v !== null && $v !== '';
    }

    /**
     * @param  list<MonthEndPoDeletePlan>  $plans
     */
    private function writeReport(string $path, array $plans): void
    {
        $lines = [
            'Month-end PO PURGE — dry-run (every row with id + detail)',
            'Generated: ' . now()->toDateTimeString(),
            '',
            'Columns: PO | Action | Entity | Table | id | Detail',
            'Action DELETE = row removed; UNLINK = po_consumable_id cleared on allocation',
            '',
        ];

        $grandTotal = 0;
        foreach ($plans as $plan) {
            $lines[] = str_repeat('=', 72);
            $lines[] = sprintf(
                'PO %s:%s  id=%d  status=%s  %s',
                $plan->kind,
                $plan->po->pono,
                $plan->po->id,
                $plan->po->status,
                $plan->blocked ? 'BLOCKED: ' . implode('; ', $plan->blockReasons) : 'WILL DELETE'
            );
            $lines[] = str_repeat('-', 72);

            foreach ($plan->countsByEntity() as $entity => $count) {
                $lines[] = sprintf('  %s: %d', $entity, $count);
            }
            $lines[] = '';

            foreach ($plan->deleteRows as $row) {
                $lines[] = sprintf(
                    '%s | %s | %s | %s | %d | %s',
                    $row['po_pono'],
                    $row['action'],
                    $row['entity'],
                    $row['table'],
                    $row['id'],
                    $row['detail']
                );
                $grandTotal++;
            }
            $lines[] = '';
        }

        $lines[] = str_repeat('=', 72);
        $lines[] = 'GRAND TOTAL records in delete plan: ' . $grandTotal;
        $lines[] = 'Apply: php artisan monthend-po:purge-mseries --apply --force --yes --include-hardware';

        file_put_contents($path, implode(PHP_EOL, $lines));
    }
}
