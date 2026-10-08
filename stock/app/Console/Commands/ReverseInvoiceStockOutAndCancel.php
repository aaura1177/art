<?php

namespace App\Console\Commands;

use App\invoice;
use App\Support\FurnitureInvoiceStockOutReverse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReverseInvoiceStockOutAndCancel extends Command
{
    protected $signature = 'invoice:reverse-stockout-and-cancel
                            {invoiceno : Invoice number e.g. GVD/UK/2627/060}
                            {--dry-run : Preview only, do not write}';

    protected $description = 'Reverse furniture stock-out (batch, unique ref, stock logs) then cancel the invoice.';

    public function handle(FurnitureInvoiceStockOutReverse $reverse): int
    {
        $invoiceno = strtoupper(trim((string) $this->argument('invoiceno')));
        $dryRun = (bool) $this->option('dry-run');

        $invoice = invoice::whereRaw('UPPER(invoiceno) = ?', [$invoiceno])->first();
        if (! $invoice) {
            $this->error('Invoice not found: ' . $invoiceno);

            return self::FAILURE;
        }

        $this->info('Invoice #' . $invoice->id . ' ' . $invoice->invoiceno);
        $this->line('status=' . $invoice->status . ' is_canceled=' . $invoice->is_canceled);

        if ($dryRun) {
            $this->warn('DRY-RUN — no database writes.');

            return self::SUCCESS;
        }

        try {
            $summary = DB::transaction(function () use ($reverse, $invoice) {
                return $reverse->reverseThenCancel($invoice->fresh(), true);
            });
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Reversed and canceled.');
        foreach ($summary as $key => $value) {
            $this->line($key . ': ' . (is_array($value) ? implode(',', $value) : (is_bool($value) ? ($value ? 'yes' : 'no') : $value)));
        }

        return self::SUCCESS;
    }
}
