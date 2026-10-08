<?php

namespace App\Services\Carton;

use App\CartonSupplierInvoiceReversal;
use App\packaging;
use App\PbTableCorton;
use App\popTable;
use App\PurchaseBillCarton;
use App\purchaseOrderConsumable;
use App\soTable;
use App\StockLogCarton;
use App\supplierInvoice;
use App\supplierInvoiceProduct;
use Illuminate\Support\Facades\DB;

class ReverseCartonSupplierInvoiceService
{
    /** Payment statuses treated as paid (block reversal). */
    private const PAID_PAYMENT_STATUSES = [3, 5];

    /**
     * @return array{supplierInvoice: supplierInvoice, bill: PurchaseBillCarton, lines: \Illuminate\Support\Collection, purchaseOrder: purchaseOrderConsumable}
     */
    public function buildPreview(int $supplierInvoiceId): array
    {
        $context = $this->resolveContext($supplierInvoiceId);
        $this->assertReversible($context);

        return $context;
    }

    public function reverse(int $supplierInvoiceId, ?string $reason, int $reversedByUserId): void
    {
        $context = $this->resolveContext($supplierInvoiceId);
        $this->assertReversible($context);

        /** @var supplierInvoice $supplierInvoice */
        $supplierInvoice = $context['supplierInvoice'];
        /** @var PurchaseBillCarton $bill */
        $bill = $context['bill'];
        /** @var purchaseOrderConsumable $purchaseOrder */
        $purchaseOrder = $context['purchaseOrder'];
        $lines = $context['lines'];

        $linesSnapshot = $lines->map(function (PbTableCorton $line) {
            return [
                'product_id' => (int) $line->product_id,
                'receiveqty' => (float) $line->receiveqty,
                'receiveqty2' => (float) $line->receiveqty2,
            ];
        })->values()->all();

        DB::transaction(function () use (
            $supplierInvoice,
            $bill,
            $purchaseOrder,
            $lines,
            $linesSnapshot,
            $reason,
            $reversedByUserId
        ) {
            $poId = (int) $purchaseOrder->id;
            $skipStock = (int) $purchaseOrder->address_option === 100;

            foreach ($lines as $line) {
                $productId = (int) $line->product_id;
                $r1 = (float) $line->receiveqty;
                $r2 = (float) $line->receiveqty2;

                if ($r1 <= 0 && $r2 <= 0) {
                    continue;
                }

                $poLine = popTable::where('poid', $poId)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if (!$poLine) {
                    throw new \RuntimeException(
                        'PO line not found for product id ' . $productId . '.'
                    );
                }

                $poLine->remqty_box1 = (float) $poLine->remqty_box1 + $r1;
                $poLine->remqty_box2 = (float) $poLine->remqty_box2 + $r2;
                $poLine->save();

                if (!$skipStock) {
                    $pkg = packaging::where('product_id', $productId)->lockForUpdate()->first();
                    if ($pkg) {
                        $box1 = (float) $pkg->box_1_qty;
                        $box2 = (float) $pkg->box_2_qty;
                        if ($r1 > 0 && $box1 < $r1) {
                            throw new \RuntimeException(
                                'Insufficient packaging box 1 stock for product ' . $productId
                                . ' (have ' . $box1 . ', need to remove ' . $r1 . ').'
                            );
                        }
                        if ($r2 > 0 && $box2 < $r2) {
                            throw new \RuntimeException(
                                'Insufficient packaging box 2 stock for product ' . $productId
                                . ' (have ' . $box2 . ', need to remove ' . $r2 . ').'
                            );
                        }
                        $pkg->box_1_qty = $box1 - $r1;
                        $pkg->box_2_qty = $box2 - $r2;
                        $pkg->save();
                    }
                }
            }

            StockLogCarton::where('entity_id', $bill->id)->where('type', 1)->delete();

            PbTableCorton::where('purchaseBill_id', $bill->id)->delete();

            $billId = (int) $bill->id;
            $supplierInvoiceId = (int) $supplierInvoice->id;

            PurchaseBillCarton::where('id', $billId)->delete();

            supplierInvoiceProduct::where('supplier_invoice_id', $supplierInvoiceId)->delete();
            soTable::where('supplier_invoice_id', $supplierInvoiceId)->delete();

            CartonSupplierInvoiceReversal::create([
                'supplier_invoice_id' => $supplierInvoiceId,
                'purchase_bill_carton_id' => $billId,
                'purchase_order_id' => $poId,
                'reversed_by' => $reversedByUserId,
                'reason' => $reason ? trim($reason) : null,
                'lines_snapshot' => $linesSnapshot,
            ]);

            supplierInvoice::where('id', $supplierInvoiceId)->delete();

            $this->refreshPurchaseOrderStatus($poId);
        });
    }

    /**
     * @return array{supplierInvoice: supplierInvoice, bill: PurchaseBillCarton, lines: \Illuminate\Support\Collection, purchaseOrder: purchaseOrderConsumable}
     */
    private function resolveContext(int $supplierInvoiceId): array
    {
        $supplierInvoice = supplierInvoice::find($supplierInvoiceId);
        if (!$supplierInvoice) {
            throw new \RuntimeException('Supplier invoice not found.');
        }

        if ($supplierInvoice->purchase_order_type !== 'Carton') {
            throw new \RuntimeException('This action is only for carton supplier invoices.');
        }

        $purchaseOrder = purchaseOrderConsumable::find($supplierInvoice->purchase_order_id);
        if (!$purchaseOrder || (int) $purchaseOrder->type !== 2) {
            throw new \RuntimeException('Carton purchase order not found for this invoice.');
        }

        $bill = PurchaseBillCarton::where('supplier_invoice_id', $supplierInvoiceId)->first();
        if (!$bill) {
            throw new \RuntimeException('No carton purchase bill found for this supplier invoice.');
        }

        $lines = PbTableCorton::where('purchaseBill_id', $bill->id)->get();
        if ($lines->isEmpty()) {
            throw new \RuntimeException('No purchase bill lines found to reverse.');
        }

        return compact('supplierInvoice', 'bill', 'lines', 'purchaseOrder');
    }

    /**
     * @param array{supplierInvoice: supplierInvoice, bill: PurchaseBillCarton, lines: \Illuminate\Support\Collection, purchaseOrder: purchaseOrderConsumable} $context
     */
    private function assertReversible(array $context): void
    {
        $supplierInvoice = $context['supplierInvoice'];
        $bill = $context['bill'];

        if ((int) $supplierInvoice->status === 2) {
            throw new \RuntimeException('This invoice was cancelled by the supplier and cannot be reversed here.');
        }

        if ((int) $supplierInvoice->is_approved !== 1) {
            throw new \RuntimeException('Only approved carton invoices with an inward can be reversed.');
        }

        if (CartonSupplierInvoiceReversal::where('supplier_invoice_id', $supplierInvoice->id)->exists()) {
            throw new \RuntimeException('This supplier invoice was already reversed.');
        }

        $paymentStatus = (int) ($bill->payment_status ?? 1);
        if (in_array($paymentStatus, self::PAID_PAYMENT_STATUSES, true)) {
            throw new \RuntimeException('Cannot reverse: purchase bill payment status is marked as paid.');
        }

        $billId = (int) $bill->id;
        if (DB::table('reject_repair')->where('purchase_bill_id', $billId)->exists()) {
            throw new \RuntimeException('Cannot reverse: reject/repair records exist for this purchase bill.');
        }
        if (DB::table('reject_repair_ptable')->where('purchase_bill_id', $billId)->exists()) {
            throw new \RuntimeException('Cannot reverse: reject/repair line records exist for this purchase bill.');
        }

        foreach ($context['lines'] as $line) {
            $r1 = (float) $line->receiveqty;
            $r2 = (float) $line->receiveqty2;
            if ($r1 <= 0 && $r2 <= 0) {
                continue;
            }

            if ((int) $context['purchaseOrder']->address_option === 100) {
                continue;
            }

            $pkg = packaging::where('product_id', (int) $line->product_id)->first();
            if (!$pkg) {
                continue;
            }
            if ($r1 > 0 && (float) $pkg->box_1_qty < $r1) {
                throw new \RuntimeException(
                    'Insufficient packaging box 1 stock to reverse product ' . (int) $line->product_id . '.'
                );
            }
            if ($r2 > 0 && (float) $pkg->box_2_qty < $r2) {
                throw new \RuntimeException(
                    'Insufficient packaging box 2 stock to reverse product ' . (int) $line->product_id . '.'
                );
            }
        }
    }

    private function refreshPurchaseOrderStatus(int $poId): void
    {
        $purchaseOrder = purchaseOrderConsumable::where('id', $poId)->lockForUpdate()->first();
        if (!$purchaseOrder) {
            return;
        }

        $statusremqty = 0.0;
        $statusremqty2 = 0.0;
        foreach (popTable::where('poid', $poId)->get() as $row) {
            $statusremqty += (float) $row->remqty_box1;
            $statusremqty2 += (float) $row->remqty_box2;
        }

        $purchaseOrder->status = ($statusremqty == 0.0 && $statusremqty2 == 0.0) ? 1 : 0;
        $purchaseOrder->remqty = $statusremqty;
        $purchaseOrder->save();
    }
}
