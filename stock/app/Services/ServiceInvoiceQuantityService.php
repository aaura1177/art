<?php

namespace App\Services;

use App\Service;
use App\serviceTable;
use App\SubServiceTable;
use App\SubServiceProductTable;
use App\serviceProductTable;
use App\supplierServiceInvoice;

class ServiceInvoiceQuantityService
{
    public static function activeInvoiceIdsForPo(int $poId): array
    {
        return supplierServiceInvoice::query()
            ->where('purchase_order_id', $poId)
            ->where(function ($q) {
                $q->where('status', '!=', 2)->orWhereNull('status');
            })
            ->pluck('id')
            ->all();
    }

    public static function recalculateSubRemaining(SubServiceTable $sub): void
    {
        $invoiceIds = self::activeInvoiceIdsForPo((int) $sub->po_id);

        $usedQty = (float) SubServiceProductTable::query()
            ->whereIn('supplier_invoice_id', $invoiceIds)
            ->where('product_id', $sub->product_id)
            ->where('sub_name', $sub->sub_name)
            ->sum('sub_quantity');

        $usedAmount = (float) SubServiceProductTable::query()
            ->whereIn('supplier_invoice_id', $invoiceIds)
            ->where('product_id', $sub->product_id)
            ->where('sub_name', $sub->sub_name)
            ->sum('sub_amount');

        $usedPercentage = (float) SubServiceProductTable::query()
            ->whereIn('supplier_invoice_id', $invoiceIds)
            ->where('product_id', $sub->product_id)
            ->where('sub_name', $sub->sub_name)
            ->sum('sub_percentage');

        $serviceTable = $sub->serviceTable;
        $isCount = $serviceTable && $serviceTable->unit === 'Count';

        if ($isCount) {
            $sub->sub_remaining_percentage = max(0, round(100 - $usedPercentage, 2));
            if ($sub->sub_remaining_percentage <= 0.0001) {
                $sub->sub_remaining_percentage = 0;
                $sub->sub_remaining_amount = 0;
                $sub->sub_remqty = 0;
            } else {
                $sub->sub_remaining_amount = max(0, round((float) $sub->sub_amount - $usedAmount, 2));
                $subQty = (float) $sub->sub_quantity;
                $sub->sub_remqty = max(0, round($subQty * ($sub->sub_remaining_percentage / 100), 4));
            }
        } else {
            $sub->sub_remqty = max(0, round((float) $sub->sub_quantity - $usedQty, 4));
            $sub->sub_remaining_amount = max(0, round((float) $sub->sub_amount - $usedAmount, 2));
        }

        $sub->save();
    }

    public static function recalculateParentRemaining(serviceTable $poProduct): void
    {
        $hasSubs = SubServiceTable::where('serviceTable_id', $poProduct->id)->exists();

        if ($hasSubs) {
            $poProduct->remqty = SubServiceTable::where('serviceTable_id', $poProduct->id)->sum('sub_remqty');
            $poProduct->remaining_amount = SubServiceTable::where('serviceTable_id', $poProduct->id)->sum('sub_remaining_amount');

            if ($poProduct->unit === 'Count') {
                $lineAmount = (float) $poProduct->amount;
                if ($lineAmount > 0.0001) {
                    $poProduct->remaining_percentage = max(0, round(
                        ((float) $poProduct->remaining_amount / $lineAmount) * 100,
                        2
                    ));
                } else {
                    $poProduct->remaining_percentage = (float) ($poProduct->remaining_percentage ?? 100);
                }

                if ((float) $poProduct->remaining_percentage <= 0.0001) {
                    $poProduct->remaining_percentage = 0;
                    $poProduct->remaining_amount = 0;
                    $poProduct->remqty = 0;
                }
            }

            $poProduct->save();
            return;
        }

        $invoiceIds = self::activeInvoiceIdsForPo((int) $poProduct->poid);

        if ($poProduct->unit === 'Hours') {
            $usedQty = (float) serviceProductTable::query()
                ->whereIn('supplier_invoice_id', $invoiceIds)
                ->where('product_id', $poProduct->product_id)
                ->where('unit', 'Hours')
                ->sum('quantity');

            $poProduct->remqty = max(0, round((float) $poProduct->quantity - $usedQty, 4));
        } else {
            $usedPercentage = (float) serviceProductTable::query()
                ->whereIn('supplier_invoice_id', $invoiceIds)
                ->where('product_id', $poProduct->product_id)
                ->where('unit', 'Count')
                ->sum('percentage');

            $poProduct->remaining_percentage = max(0, round(100 - $usedPercentage, 2));

            if ((float) $poProduct->remaining_percentage <= 0.0001) {
                $poProduct->remaining_percentage = 0;
                $poProduct->remaining_amount = 0;
                $poProduct->remqty = 0;
            } else {
                $poProduct->remqty = (float) $poProduct->quantity;
            }
        }

        if ((float) ($poProduct->remaining_percentage ?? 0) > 0.0001 || $poProduct->unit === 'Hours') {
            $usedAmount = (float) serviceProductTable::query()
                ->whereIn('supplier_invoice_id', $invoiceIds)
                ->where('product_id', $poProduct->product_id)
                ->sum('amount');

            $poProduct->remaining_amount = max(0, round((float) $poProduct->amount - $usedAmount, 2));
        }

        $poProduct->save();
    }

    public static function isPoComplete(int $poId): bool
    {
        $lines = serviceTable::where('poid', $poId)->get();
        if ($lines->isEmpty()) {
            return false;
        }

        foreach ($lines as $line) {
            if ($line->unit === 'Count') {
                if ((float) $line->remaining_percentage > 0.0001 || (float) $line->remaining_amount > 0.01) {
                    return false;
                }
            } elseif ((float) $line->remqty > 0.0001 || (float) $line->remaining_amount > 0.01) {
                return false;
            }
        }

        return true;
    }

    public static function recalculatePoHeader(int $poId): void
    {
        $po = Service::find($poId);
        if (!$po) {
            return;
        }

        $po->remqty = serviceTable::where('poid', $poId)->sum('remqty');
        $po->status = self::isPoComplete($poId) ? 1 : 0;
        $po->save();
    }

    public static function syncAllForPo(int $poId): void
    {
        $subs = SubServiceTable::with('serviceTable')->where('po_id', $poId)->get();
        foreach ($subs as $sub) {
            self::recalculateSubRemaining($sub);
        }

        $parents = serviceTable::where('poid', $poId)->get();
        foreach ($parents as $parent) {
            self::recalculateParentRemaining($parent);
        }

        self::recalculatePoHeader($poId);
    }

    public static function snapshotSubRemainingOnInvoiceLine(SubServiceProductTable $line, SubServiceTable $sub): void
    {
        $line->sub_remqty = $sub->sub_remqty;
        $line->sub_remaining_amount = $sub->sub_remaining_amount;
        $line->sub_remaining_percentage = $sub->sub_remaining_percentage;
        $line->save();
    }
}
