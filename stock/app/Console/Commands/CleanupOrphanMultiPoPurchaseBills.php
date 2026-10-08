<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanupOrphanMultiPoPurchaseBills extends Command
{
    protected $signature = 'multi-po:cleanup-orphan-purchase-bills
                            {--invoice-id= : Comma-separated supplier_invoices.id values e.g. 9331,9334}
                            {--apply : Delete matched orphan purchase bills (default is dry-run)}
                            {--yes : Skip confirmation when using --apply}';

    protected $description = 'Remove orphan multi-PO purchase_bill headers left by failed approve (0 lines, no stock logs).';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $invoiceIds = $this->parseInvoiceIds();

        if ($invoiceIds === null) {
            return self::FAILURE;
        }

        $orphans = $this->findOrphans($invoiceIds);

        if ($orphans->isEmpty()) {
            $this->info('No orphan purchase bills matched.');

            return self::SUCCESS;
        }

        $this->line($apply ? 'DELETE candidates:' : 'DRY-RUN — would delete:');
        $rows = [];
        foreach ($orphans as $pb) {
            $rows[] = [
                (string) $pb->id,
                (string) ($pb->supplier_invoice_id ?? '0'),
                (string) ($pb->supp_inv_no ?? ''),
                (string) ($pb->getinserailno ?? ''),
                (string) ($pb->mulitple_po_purchaseBill ?? ''),
                (string) $pb->line_count,
                (string) $pb->stock_log_count,
                (string) $pb->created_at,
            ];
        }

        $this->table(
            ['pb_id', 'si_id', 'supp_inv_no', 'getin', 'multi_po', 'lines', 'stock_logs', 'created_at'],
            $rows
        );

        if (! $apply) {
            $this->warn('Dry-run only. Re-run with --apply to delete.');

            return self::SUCCESS;
        }

        if (! $this->option('yes') && ! $this->confirm('Delete ' . $orphans->count() . ' orphan purchase bill(s)?')) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($orphans as $pb) {
            DB::table('purchase_bill')->where('id', $pb->id)->delete();
            $deleted++;
            $this->line("Deleted purchase_bill id={$pb->id} getin={$pb->getinserailno}");
        }

        $this->info("Deleted {$deleted} orphan purchase bill(s).");

        return self::SUCCESS;
    }

    /**
     * @return array<int, int>|null
     */
    private function parseInvoiceIds(): ?array
    {
        $raw = trim((string) $this->option('invoice-id'));
        if ($raw === '') {
            $this->error('Provide --invoice-id=9331,9334 (comma-separated supplier_invoices.id).');

            return null;
        }

        $ids = array_values(array_unique(array_filter(array_map(
            static fn ($v) => (int) trim($v),
            explode(',', $raw)
        ))));

        if ($ids === []) {
            $this->error('No valid invoice ids in --invoice-id.');

            return null;
        }

        return $ids;
    }

    /**
     * @param  array<int, int>  $invoiceIds
     */
    private function findOrphans(array $invoiceIds)
    {
        $invoices = DB::table('supplier_invoices')
            ->whereIn('id', $invoiceIds)
            ->get(['id', 'supplier_invoice_number', 'is_approved', 'mulitple_po']);

        if ($invoices->isEmpty()) {
            $this->warn('None of the supplier invoices exist.');

            return collect();
        }

        $suppInvNos = $invoices->pluck('supplier_invoice_number')->filter()->unique()->values()->all();

        $query = DB::table('purchase_bill as pb')
            ->select([
                'pb.id',
                'pb.supplier_invoice_id',
                'pb.supp_inv_no',
                'pb.getinserailno',
                'pb.mulitple_po_purchaseBill',
                'pb.created_at',
                DB::raw('(SELECT COUNT(*) FROM pb_table pt WHERE pt.purchaseBill_id = pb.id) as line_count'),
                DB::raw('(SELECT COUNT(*) FROM stock_log sl WHERE sl.entity_id = pb.id AND sl.type = 1) as stock_log_count'),
            ])
            ->where(function ($q) use ($invoiceIds, $suppInvNos) {
                $q->whereIn('pb.supplier_invoice_id', $invoiceIds)
                    ->orWhere(function ($q2) use ($suppInvNos) {
                        $q2->whereIn('pb.supp_inv_no', $suppInvNos)
                            ->where(function ($q3) {
                                $q3->whereNull('pb.supplier_invoice_id')
                                    ->orWhere('pb.supplier_invoice_id', 0);
                            });
                    });
            })
            ->having('line_count', '=', 0)
            ->having('stock_log_count', '=', 0);

        if (Schema::hasColumn('purchase_bill', 'mulitple_po_purchaseBill')) {
            $query->whereNotNull('pb.mulitple_po_purchaseBill')
                ->where('pb.mulitple_po_purchaseBill', '!=', '');
        }

        return $query->orderBy('pb.id')->get();
    }
}
