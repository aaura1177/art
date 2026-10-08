<?php

namespace App\Console\Commands;

use App\Challan;
use App\pocTable;
use App\purchaseBillConsumable;
use App\purchaseOrderConsumable;
use App\supplierInvoice;
use App\Support\ConsumableMonthEndInvoiceSupport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AlignMonthEndPoTotals extends Command
{
    protected $signature = 'monthend-po:align-totals
                            {--apply : Persist corrections (default is preview only)}
                            {--po-id= : Limit to one purchase_order_consumables id}
                            {--pono= : Limit to one PO number e.g. M/7}';

    protected $description = 'Preview or apply month-end PO totals (M-series + hardware month-end) from poc lines.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $poId = $this->option('po-id') !== null && $this->option('po-id') !== ''
            ? (int) $this->option('po-id')
            : null;
        $pono = $this->option('pono') !== null && $this->option('pono') !== ''
            ? (string) $this->option('pono')
            : null;

        $query = purchaseOrderConsumable::query()
            ->where('type', 1)
            ->where('address_option', 100)
            ->orderBy('id');

        if ($poId) {
            $query->where('id', $poId);
        }
        if ($pono) {
            $query->where('pono', $pono);
        }

        $pos = $query->get();
        if ($pos->isEmpty()) {
            $this->warn('No month-end consumable POs matched.');

            return self::SUCCESS;
        }

        $this->info($apply ? 'APPLY mode — writing changes.' : 'DRY-RUN — no database writes.');
        $this->line('POs scanned: ' . $pos->count());
        $this->newLine();

        $headers = [
            'PO',
            'Entity',
            'ID',
            'Field',
            'Stored',
            'Corrected',
            'Delta',
        ];
        $rows = [];
        $changeCount = 0;

        foreach ($pos as $po) {
            if (! ConsumableMonthEndInvoiceSupport::isMonthEndConsumablePo($po)) {
                continue;
            }

            $kind = ConsumableMonthEndInvoiceSupport::isHardwareMonthEndConsumablePo($po) ? 'HW' : 'M';
            $lines = pocTable::where('poid', $po->id)->get();
            $totals = ConsumableMonthEndInvoiceSupport::headerTotalsFromPocLines($lines);

            $poChanges = $this->diffFields($po, [
                'subTotal' => $totals['subTotal'],
                'tgst' => $totals['tgst'],
                'tamount' => $totals['tamount'],
                'tquantity' => $totals['tquantity'],
            ], 'PO', $kind . ':' . $po->pono, (int) $po->id, $rows);

            $challan = Challan::where('purchase_order_id', $po->id)->first();
            if ($challan) {
                $poChanges += $this->diffFields($challan, [
                    'subTotal' => $totals['subTotal'],
                    'tgst' => $totals['tgst'],
                    'tamount' => $totals['tamount'],
                    'tquantity' => $totals['tquantity'],
                ], 'Challan', $kind . ':' . $po->pono, (int) $challan->id, $rows);
            }

            $invoices = supplierInvoice::where('purchase_order_id', $po->id)
                ->where('purchase_order_type', '!=', 'Furniture')
                ->get();
            foreach ($invoices as $si) {
                $poChanges += $this->diffFields($si, [
                    'subTotal' => $totals['subTotal'],
                    'tgst' => $totals['tgst'],
                    'tamount' => $totals['tamount'],
                    'tquantity' => $totals['tquantity'],
                ], 'SupplierInv', $kind . ':' . $po->pono, (int) $si->id, $rows);
            }

            $bills = purchaseBillConsumable::where('purchaseOrder_id', $po->id)->get();
            foreach ($bills as $bill) {
                $poChanges += $this->diffFields($bill, [
                    'subtotal' => $totals['subTotal'],
                    'gst' => $totals['tgst'],
                    'total' => $totals['tamount'],
                    'quantity' => $totals['tquantity'],
                ], 'PurchaseBill', $kind . ':' . $po->pono, (int) $bill->id, $rows);
            }

            if ($poChanges > 0 && $apply) {
                DB::transaction(function () use ($po, $lines, $totals, $challan, $invoices, $bills) {
                    foreach ($lines as $line) {
                        $correctGst = ConsumableMonthEndInvoiceSupport::lineGstAmount(
                            (float) $line->amount,
                            (float) $line->gstslab
                        );
                        if (round((float) $line->gstamount, 2) !== $correctGst) {
                            $line->gstamount = $correctGst;
                            $line->save();
                        }
                    }

                    $po->update([
                        'subTotal' => $totals['subTotal'],
                        'tgst' => $totals['tgst'],
                        'tamount' => $totals['tamount'],
                        'tquantity' => $totals['tquantity'],
                    ]);

                    if ($challan) {
                        $challan->update([
                            'subTotal' => $totals['subTotal'],
                            'tgst' => $totals['tgst'],
                            'tamount' => $totals['tamount'],
                            'tquantity' => $totals['tquantity'],
                        ]);
                    }

                    foreach ($invoices as $si) {
                        $si->update([
                            'subTotal' => $totals['subTotal'],
                            'tgst' => $totals['tgst'],
                            'tamount' => $totals['tamount'],
                            'tquantity' => $totals['tquantity'],
                        ]);
                    }

                    foreach ($bills as $bill) {
                        $bill->update([
                            'subtotal' => $totals['subTotal'],
                            'gst' => $totals['tgst'],
                            'total' => $totals['tamount'],
                            'quantity' => $totals['tquantity'],
                        ]);
                    }
                });
            }

            $changeCount += $poChanges;

            if ($poChanges > 0) {
                $this->line(sprintf(
                    '%s:%s (id %d): %d field(s) %s; poc line gst fixes: %d',
                    $kind,
                    $po->pono,
                    $po->id,
                    $poChanges,
                    $apply ? 'updated' : 'would change',
                    $totals['line_fixes']
                ));
            }
        }

        if ($rows !== []) {
            $this->table($headers, $rows);
        } else {
            $this->info('All scanned records already match poc-line totals.');
        }

        $this->newLine();
        $this->info('Total field mismatches: ' . $changeCount);
        if (! $apply && $changeCount > 0) {
            $this->comment('Re-run with --apply to persist.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, float>  $corrected
     * @param  array<int, array<int, string|float>>  $rows
     */
    private function diffFields(
        object $model,
        array $corrected,
        string $entity,
        string $pono,
        int $entityId,
        array &$rows
    ): int {
        $n = 0;
        foreach ($corrected as $field => $newVal) {
            $stored = (float) ($model->{$field} ?? 0);
            $newVal = round((float) $newVal, 2);
            if (abs($stored - $newVal) < 0.005) {
                continue;
            }
            $n++;
            $rows[] = [
                $pono,
                $entity,
                $entityId,
                $field,
                number_format($stored, 2, '.', ''),
                number_format($newVal, 2, '.', ''),
                number_format($newVal - $stored, 2, '.', ''),
            ];
        }

        return $n;
    }
}
