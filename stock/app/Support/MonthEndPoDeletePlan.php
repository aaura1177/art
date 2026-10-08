<?php

namespace App\Support;

use App\Challan;
use App\ChallanProduct;
use App\pbTableConsumable;
use App\pocTable;
use App\popTable;
use App\purchaseBillConsumable;
use App\purchaseOrderConsumable;
use App\supplierInvoice;
use App\supplierInvoiceProduct;
use App\supplierInvoiceReturn;
use App\supplierInvoiceReturnProduct;
use App\UniqueReferenceNumber;
use App\UniqueReferenceNumberProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Preview of every row removed when purging one M-series month-end PO. */
final class MonthEndPoDeletePlan
{
    public purchaseOrderConsumable $po;

    public string $kind;

    public bool $blocked;

    /** @var list<string> */
    public array $blockReasons = [];

    /** @var Collection<int, Challan> */
    public Collection $challans;

    /** @var Collection<int, supplierInvoice> */
    public Collection $supplierInvoices;

    /** @var Collection<int, purchaseBillConsumable> */
    public Collection $purchaseBills;

    /**
     * Every record that --apply will delete or unlink.
     *
     * @var list<array{po_pono: string, po_id: int, action: string, entity: string, table: string, id: int, detail: string}>
     */
    public array $deleteRows = [];

    public static function forPo(purchaseOrderConsumable $po, bool $requirePending): self
    {
        $plan = new self;
        $plan->po = $po;
        $plan->blocked = false;
        $plan->kind = ConsumableMonthEndInvoiceSupport::isHardwareMonthEndConsumablePo($po) ? 'HW' : 'M';
        $plan->challans = Challan::where('purchase_order_id', $po->id)->get();
        // Exclude legacy Furniture SIs still pointing at this consumable PO id (same filter as MonthEndPoAlignPlan).
        $plan->supplierInvoices = supplierInvoice::where('purchase_order_id', $po->id)
            ->where('purchase_order_type', '!=', 'Furniture')
            ->get();
        $plan->purchaseBills = purchaseBillConsumable::where('purchaseOrder_id', $po->id)->get();

        $plan->collectDeleteRows();

        if ($requirePending && (int) $po->status !== 0) {
            $plan->blocked = true;
            $plan->blockReasons[] = 'PO status is not pending (status=' . (int) $po->status . '); use --force to delete anyway';
        }

        if (! ConsumableMonthEndInvoiceSupport::isMseriesMonthEndConsumablePo($po)
            && ! ConsumableMonthEndInvoiceSupport::isHardwareMonthEndConsumablePo($po)
        ) {
            $plan->blocked = true;
            $plan->blockReasons[] = 'Not a month-end consumable PO (address_option must be 100)';
        }

        return $plan;
    }

    public function totalRowDeletes(): int
    {
        return count($this->deleteRows);
    }

    /** @return array<string, int> */
    public function countsByEntity(): array
    {
        $counts = [];
        foreach ($this->deleteRows as $row) {
            $key = $row['entity'];
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }

    private function collectDeleteRows(): void
    {
        $po = $this->po;
        $poId = (int) $po->id;

        foreach ($this->supplierInvoices as $si) {
            $siId = (int) $si->id;
            $returnIds = supplierInvoiceReturn::where('supplier_invoice_id', $siId)->pluck('id')->all();
            foreach ($returnIds as $returnId) {
                foreach (supplierInvoiceReturnProduct::where('supplier_invoice_return_id', $returnId)->get() as $rp) {
                    $this->addRow('DELETE', 'SupplierInvoiceReturnProduct', 'supplier_invoice_return_products', (int) $rp->id,
                        'return_id=' . $returnId . ' product_id=' . ($rp->product_id ?? ''));
                }
                $ret = supplierInvoiceReturn::find($returnId);
                $this->addRow('DELETE', 'SupplierInvoiceReturn', 'supplier_invoice_returns', (int) $returnId,
                    'supplier_invoice_id=' . $siId . ' type=' . ($ret->type ?? ''));
            }

            foreach (UniqueReferenceNumber::where('supplier_invoice_id', $siId)->get() as $ref) {
                $refId = (int) $ref->id;
                foreach (UniqueReferenceNumberProduct::where('unique_referencenumber_id', $refId)->get() as $rp) {
                    $this->addRow('DELETE', 'UniqueReferenceProduct', 'unique_referencenumber_products', (int) $rp->id,
                        'ref_no=' . ($ref->ref_no ?? '') . ' product_id=' . ($rp->product_id ?? ''));
                }
                $this->addRow('DELETE', 'UniqueReference', 'unique_referencenumber', $refId,
                    'ref_no=' . ($ref->ref_no ?? '') . ' batch_id=' . ($ref->batch_id ?? ''));
            }

            foreach (supplierInvoiceProduct::where('supplier_invoice_id', $siId)->get() as $sip) {
                $this->addRow('DELETE', 'SupplierInvoiceLine', 'supplier_invoice_products', (int) $sip->id,
                    'product_id=' . ($sip->product_id ?? '') . ' qty=' . ($sip->quantity ?? 0)
                    . ' amount=' . ($sip->amount ?? 0) . ' total=' . ($sip->total ?? 0));
            }

            $this->addRow('DELETE', 'SupplierInvoice', 'supplier_invoices', $siId,
                'inv_no=' . ($si->supplier_invoice_number ?? '') . ' internal=' . ($si->internal_invoice_number ?? '')
                . ' subTotal=' . ($si->subTotal ?? 0) . ' tgst=' . ($si->tgst ?? 0) . ' tamount=' . ($si->tamount ?? 0));
        }

        foreach ($this->purchaseBills as $bill) {
            $pbId = (int) $bill->id;
            foreach (pbTableConsumable::where('purchaseBill_id', $pbId)->get() as $line) {
                $this->addRow('DELETE', 'PurchaseBillLine', 'pb_table_consumables', (int) $line->id,
                    'product_id=' . ($line->product_id ?? '') . ' receiveqty=' . ($line->receiveqty ?? 0)
                    . ' amount=' . ($line->amount ?? 0));
            }
            $this->addRow('DELETE', 'PurchaseBill', 'purchase_bill_consumables', $pbId,
                'supp_inv_no=' . ($bill->supp_inv_no ?? '') . ' subtotal=' . ($bill->subtotal ?? 0)
                . ' gst=' . ($bill->gst ?? 0) . ' total=' . ($bill->total ?? 0)
                . ' supplier_invoice_id=' . ($bill->supplier_invoice_id ?? ''));
        }

        foreach (MonthEndPoDeleteService::monthEndStockLogsForPo($po) as $log) {
            $this->addRow('DELETE', 'StockLog', 'stock_log_consumable', (int) $log->id,
                'consumable_id=' . ($log->consumable_id ?? '') . ' type=' . ($log->type ?? '')
                . ' remark=' . ($log->remark ?? '') . ' qty=' . ($log->quantity ?? 0)
                . ' voucher=' . ($log->voucher_no ?? ''));
        }

        foreach (ChallanProduct::where('purchase_order_id', $poId)->get() as $cp) {
            $this->addRow('DELETE', 'ChallanProduct', 'challan_products', (int) $cp->id,
                'challan_id=' . ($cp->challan_id ?? '') . ' product_id=' . ($cp->product_id ?? '')
                . ' qty=' . ($cp->quantity ?? 0));
        }

        foreach ($this->challans as $ch) {
            $this->addRow('DELETE', 'Challan', 'challan', (int) $ch->id,
                'challan_no=' . ($ch->challan_number ?? '') . ' subTotal=' . ($ch->subTotal ?? 0)
                . ' tgst=' . ($ch->tgst ?? 0) . ' tamount=' . ($ch->tamount ?? 0));
        }

        foreach (pocTable::where('poid', $poId)->get() as $line) {
            $this->addRow('DELETE', 'PoLine', 'pocTable', (int) $line->id,
                'consumable_id=' . ($line->consumable_id ?? '') . ' qty=' . ($line->quantity ?? 0)
                . ' amount=' . ($line->amount ?? 0) . ' gstamount=' . ($line->gstamount ?? 0));
        }

        foreach (popTable::where('poid', $poId)->get() as $line) {
            $this->addRow('DELETE', 'PoCartonLine', 'popTable', (int) $line->id,
                'product_id=' . ($line->product_id ?? '') . ' amount=' . ($line->amount ?? 0));
        }

        $allocCount = (int) DB::table('containers_allocation_details')->where('po_consumable_id', $poId)->count();
        if ($allocCount > 0) {
            $this->addRow('UNLINK', 'ContainerAllocation', 'containers_allocation_details', $poId,
                $allocCount . ' row(s): set po_consumable_id=NULL');
        }

        $this->addRow('DELETE', 'PurchaseOrder', 'purchase_order_consumables', $poId,
            'pono=' . $po->pono . ' ref_supplier=' . ($po->ref_supplier ?? '')
            . ' subTotal=' . ($po->subTotal ?? 0) . ' tgst=' . ($po->tgst ?? 0) . ' tamount=' . ($po->tamount ?? 0)
            . ' status=' . ($po->status ?? ''));
    }

    private function addRow(string $action, string $entity, string $table, int $id, string $detail): void
    {
        $this->deleteRows[] = [
            'po_pono' => (string) $this->po->pono,
            'po_id' => (int) $this->po->id,
            'action' => $action,
            'entity' => $entity,
            'table' => $table,
            'id' => $id,
            'detail' => $detail,
        ];
    }
}
