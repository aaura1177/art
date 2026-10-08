<?php

namespace App\Support;

use App\Challan;
use App\ChallanProduct;
use App\consumable;
use App\invoice;
use App\pbTableConsumable;
use App\pocTable;
use App\popTable;
use App\purchaseBillConsumable;
use App\purchaseOrderConsumable;
use App\stockLogConsumable;
use App\supplier;
use App\supplierInvoice;
use App\supplierInvoiceProduct;
use App\supplierInvoiceReturn;
use App\supplierInvoiceReturnProduct;
use App\UniqueReferenceNumber;
use App\UniqueReferenceNumberProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class MonthEndPoDeleteService
{
    public static function countMonthEndStockLogs(purchaseOrderConsumable $po): int
    {
        return self::monthEndStockLogsForPo($po)->count();
    }

    /**
     * Month-end stock in/out log rows tied to this PO (deleted on purge; consumable qty reversed).
     *
     * @return \Illuminate\Support\Collection<int, stockLogConsumable>
     */
    public static function monthEndStockLogsForPo(purchaseOrderConsumable $po)
    {
        $supplier = supplier::find($po->supplier_id);
        if (! $supplier) {
            return collect();
        }

        $sourceInvoice = invoice::where('invoiceno', (string) $po->ref_supplier)->first();
        $sourceInvoiceId = $sourceInvoice ? (int) $sourceInvoice->id : 0;
        $hasStockLogInvoiceId = Schema::hasColumn('stock_log_consumable', 'invoice_id');
        $targetSupplierId = (int) ($po->supplier_id ?? 0);
        $targetSupplierName = trim((string) ($supplier->c_name ?? ''));

        $logs = collect();
        $consumableIds = pocTable::where('poid', $po->id)->distinct()->pluck('consumable_id');

        foreach ($consumableIds as $consumableId) {
            $batch = stockLogConsumable::where('consumable_id', $consumableId)
                ->where('voucher_no', $po->ref_supplier)
                ->when($hasStockLogInvoiceId && $sourceInvoiceId > 0, function ($q) use ($sourceInvoiceId) {
                    $q->where('invoice_id', $sourceInvoiceId);
                })
                ->where(function ($q) use ($targetSupplierId, $targetSupplierName) {
                    $q->where('supplier_id', $targetSupplierId);
                    if ($targetSupplierName !== '') {
                        $q->orWhere(function ($qq) use ($targetSupplierName) {
                            $qq->whereNull('supplier_id')->where('supplier_name', $targetSupplierName);
                        });
                    }
                })
                ->where(function ($q) {
                    $q->where(function ($qq) {
                        $qq->where('type', 1)->where('remark', 'MonthEnd PO Stock In');
                    })->orWhere(function ($qq) {
                        $qq->where('type', 2)->where('remark', 'MonthEnd PO Stock Out');
                    });
                })
                ->orderBy('id')
                ->get();
            $logs = $logs->merge($batch);
        }

        return $logs;
    }

    /**
     * Delete one PO and all related data (same stock reversal as UI deleteConsumableMonthEndPo).
     */
    public static function purge(MonthEndPoDeletePlan $plan): void
    {
        $po = $plan->po;
        $id = (int) $po->id;

        DB::transaction(function () use ($plan, $po, $id) {
            $siIds = $plan->supplierInvoices->pluck('id')->all();

            if ($siIds !== []) {
                $returnIds = supplierInvoiceReturn::whereIn('supplier_invoice_id', $siIds)->pluck('id')->all();
                if ($returnIds !== []) {
                    supplierInvoiceReturnProduct::whereIn('supplier_invoice_return_id', $returnIds)->delete();
                }
                supplierInvoiceReturn::whereIn('supplier_invoice_id', $siIds)->delete();

                $refIds = UniqueReferenceNumber::whereIn('supplier_invoice_id', $siIds)->pluck('id')->all();
                if ($refIds !== []) {
                    UniqueReferenceNumberProduct::whereIn('unique_referencenumber_id', $refIds)->delete();
                    UniqueReferenceNumber::whereIn('id', $refIds)->delete();
                }

                supplierInvoiceProduct::whereIn('supplier_invoice_id', $siIds)->delete();
                supplierInvoice::whereIn('id', $siIds)->delete();
            }

            foreach ($plan->purchaseBills as $bill) {
                pbTableConsumable::where('purchaseBill_id', $bill->id)->delete();
                $bill->delete();
            }

            self::reverseMonthEndStockLogs($po);

            ChallanProduct::where('purchase_order_id', $id)->delete();
            Challan::where('purchase_order_id', $id)->delete();

            pocTable::where('poid', $id)->delete();
            popTable::where('poid', $id)->delete();

            DB::table('containers_allocation_details')
                ->where('po_consumable_id', $id)
                ->update(['po_consumable_id' => null]);

            $po->delete();
        });
    }

    /**
     * @see \App\Http\Controllers\purchaseOrderController::deleteConsumableMonthEndPo
     */
    public static function reverseMonthEndStockLogs(purchaseOrderConsumable $purchaseOrder): void
    {
        $id = (int) $purchaseOrder->id;
        $supplier = supplier::where('id', $purchaseOrder->supplier_id)->first();
        if (! $supplier) {
            return;
        }

        $sourceInvoice = invoice::where('invoiceno', (string) $purchaseOrder->ref_supplier)->first();
        $sourceInvoiceId = $sourceInvoice ? (int) $sourceInvoice->id : 0;
        $hasStockLogInvoiceId = Schema::hasColumn('stock_log_consumable', 'invoice_id');
        $targetSupplierId = (int) ($purchaseOrder->supplier_id ?? 0);
        $targetSupplierName = trim((string) ($supplier->c_name ?? ''));

        $consumableIds = pocTable::where('poid', $id)
            ->distinct()
            ->pluck('consumable_id')
            ->map(fn ($v) => (int) $v)
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($consumableIds as $consumableId) {
            $oldIn = (float) stockLogConsumable::where('consumable_id', $consumableId)
                ->where('voucher_no', $purchaseOrder->ref_supplier)
                ->when($hasStockLogInvoiceId && $sourceInvoiceId > 0, function ($q) use ($sourceInvoiceId) {
                    $q->where('invoice_id', $sourceInvoiceId);
                })
                ->where(function ($q) use ($targetSupplierId, $targetSupplierName) {
                    $q->where('supplier_id', $targetSupplierId);
                    if ($targetSupplierName !== '') {
                        $q->orWhere(function ($qq) use ($targetSupplierName) {
                            $qq->whereNull('supplier_id')->where('supplier_name', $targetSupplierName);
                        });
                    }
                })
                ->where('type', 1)
                ->where('remark', 'MonthEnd PO Stock In')
                ->sum('quantity');
            $oldOut = (float) stockLogConsumable::where('consumable_id', $consumableId)
                ->where('voucher_no', $purchaseOrder->ref_supplier)
                ->when($hasStockLogInvoiceId && $sourceInvoiceId > 0, function ($q) use ($sourceInvoiceId) {
                    $q->where('invoice_id', $sourceInvoiceId);
                })
                ->where(function ($q) use ($targetSupplierId, $targetSupplierName) {
                    $q->where('supplier_id', $targetSupplierId);
                    if ($targetSupplierName !== '') {
                        $q->orWhere(function ($qq) use ($targetSupplierName) {
                            $qq->whereNull('supplier_id')->where('supplier_name', $targetSupplierName);
                        });
                    }
                })
                ->where('type', 2)
                ->where('remark', 'MonthEnd PO Stock Out')
                ->sum('quantity');

            $netDelta = (0.0 - 0.0) - ($oldIn - $oldOut);

            $consumable = consumable::find($consumableId);
            if ($consumable && abs($netDelta) > 0.000001) {
                $consumable->quantity = (float) $consumable->quantity + $netDelta;
                $consumable->save();
            }

            stockLogConsumable::where('consumable_id', $consumableId)
                ->where('voucher_no', $purchaseOrder->ref_supplier)
                ->when($hasStockLogInvoiceId && $sourceInvoiceId > 0, function ($q) use ($sourceInvoiceId) {
                    $q->where('invoice_id', $sourceInvoiceId);
                })
                ->where(function ($q) use ($targetSupplierId, $targetSupplierName) {
                    $q->where('supplier_id', $targetSupplierId);
                    if ($targetSupplierName !== '') {
                        $q->orWhere(function ($qq) use ($targetSupplierName) {
                            $qq->whereNull('supplier_id')->where('supplier_name', $targetSupplierName);
                        });
                    }
                })
                ->where(function ($q) {
                    $q->where(function ($qq) {
                        $qq->where('type', 1)->where('remark', 'MonthEnd PO Stock In');
                    })->orWhere(function ($qq) {
                        $qq->where('type', 2)->where('remark', 'MonthEnd PO Stock Out');
                    });
                })
                ->delete();

            $allLogs = stockLogConsumable::where('consumable_id', $consumableId)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();
            if ($allLogs->isNotEmpty()) {
                $currentStock = (float) ($allLogs->first()->opening_balance ?? 0);
                foreach ($allLogs as $log) {
                    $log->opening_balance = $currentStock;
                    $log->remaining_stock = (int) $log->type === 1
                        ? $currentStock + (float) $log->quantity
                        : $currentStock - (float) $log->quantity;
                    $currentStock = (float) $log->remaining_stock;
                    $log->save();
                }
            }
        }
    }
}
