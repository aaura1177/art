<?php

namespace App\Support;

use App\pocTable;
use App\purchaseOrderConsumable;
use App\soTable;
use App\supplierInvoice;
use Illuminate\Support\Facades\Schema;

/**
 * Month-end consumable POs (address_option = 100) can have multiple pocTable rows per consumable_id.
 * Optional column supplier_invoice_products.poc_table_id ties SI lines to a specific PO line.
 */
final class ConsumableMonthEndInvoiceSupport
{
    public static function isMonthEndConsumablePo(?purchaseOrderConsumable $po): bool
    {
        return $po !== null
            && (int) $po->type === 1
            && (int) ($po->address_option ?? 0) === 100;
    }

    public static function isHardwareMonthEndConsumablePo(?purchaseOrderConsumable $po): bool
    {
        if (! self::isMonthEndConsumablePo($po)) {
            return false;
        }

        return str_starts_with(trim((string) ($po->remarks ?? '')), 'Hardware MonthEnd PO');
    }

    /** M-series month-end PO (wf consumables; excludes hardware month-end POs). */
    public static function isMseriesMonthEndConsumablePo(?purchaseOrderConsumable $po): bool
    {
        return self::isMonthEndConsumablePo($po) && ! self::isHardwareMonthEndConsumablePo($po);
    }

    public static function lineGstAmount(float $amount, float $gstPercent): float
    {
        return round($amount * $gstPercent / 100.0, 2);
    }

    /**
     * Header totals from poc lines: round GST per line, then sum (furniture-style).
     *
     * @return array{subTotal: float, tgst: float, tamount: float, tquantity: float, line_fixes: int}
     */
    public static function headerTotalsFromPocLines(iterable $lines): array
    {
        $subTotal = 0.0;
        $tgst = 0.0;
        $tquantity = 0.0;
        $lineFixes = 0;

        foreach ($lines as $line) {
            $amount = (float) ($line->amount ?? 0);
            $gstPct = (float) ($line->gstslab ?? 0);
            $correctGst = self::lineGstAmount($amount, $gstPct);
            if (round((float) ($line->gstamount ?? 0), 2) !== $correctGst) {
                $lineFixes++;
            }
            $subTotal += $amount;
            $tgst += $correctGst;
            $tquantity += (float) ($line->quantity ?? 0);
        }

        $subTotal = round($subTotal, 2);
        $tgst = round($tgst, 2);

        return [
            'subTotal' => $subTotal,
            'tgst' => $tgst,
            'tamount' => round($subTotal + $tgst, 2),
            'tquantity' => $tquantity,
            'line_fixes' => $lineFixes,
        ];
    }

    /** GST amount shown on supplier-invoice print for a consumable line. */
    public static function supplierInvoiceLineGstAmount(
        purchaseOrderConsumable $po,
        $supplierInvoiceProductRow,
        ?pocTable $pocLine
    ): float {
        if (self::isMonthEndConsumablePo($po) && $pocLine !== null) {
            return round((float) $pocLine->gstamount, 2);
        }
        $gstslab = (float) (optional($pocLine)->gstslab ?? 0);

        return round((float) $supplierInvoiceProductRow->amount * $gstslab / 100.0, 2);
    }

    public static function supplierInvoiceProductsHasPocTableId(): bool
    {
        static $cached = null;
        if ($cached === null) {
            $cached = Schema::hasColumn('supplier_invoice_products', 'poc_table_id');
        }

        return $cached;
    }

    public static function resolvePocLine(
        purchaseOrderConsumable $po,
        int $consumableId,
        ?int $pocTableIdFromRequest
    ): ?pocTable {
        if (self::isMonthEndConsumablePo($po)
            && self::supplierInvoiceProductsHasPocTableId()
            && $pocTableIdFromRequest !== null
            && $pocTableIdFromRequest > 0
        ) {
            $row = pocTable::query()
                ->where('poid', $po->id)
                ->where('id', $pocTableIdFromRequest)
                ->first();
            if ($row && (int) $row->consumable_id === $consumableId) {
                return $row;
            }
        }

        return pocTable::query()
            ->where('poid', $po->id)
            ->where('consumable_id', $consumableId)
            ->first();
    }

    /**
     * Find supplier invoice line for approve / PB creation.
     */
    public static function resolveSoLine(
        int $supplierInvoiceId,
        int $purchaseOrderId,
        int $consumableId,
        ?int $pocTableId,
        ?purchaseOrderConsumable $po
    ): ?soTable {
        if (self::isMonthEndConsumablePo($po)
            && self::supplierInvoiceProductsHasPocTableId()
            && $pocTableId !== null
            && $pocTableId > 0
        ) {
            $hit = soTable::query()
                ->where('supplier_invoice_id', $supplierInvoiceId)
                ->where('purchase_order_id', $purchaseOrderId)
                ->where('product_id', $consumableId)
                ->where('poc_table_id', $pocTableId)
                ->first();
            if ($hit) {
                return $hit;
            }
        }

        return soTable::query()
            ->where('supplier_invoice_id', $supplierInvoiceId)
            ->where('purchase_order_id', $purchaseOrderId)
            ->where('product_id', $consumableId)
            ->first();
    }

    public static function pocTableIdFromRequest(?purchaseOrderConsumable $po, array $pbRow): ?int
    {
        if (! self::isMonthEndConsumablePo($po) || ! self::supplierInvoiceProductsHasPocTableId()) {
            return null;
        }
        $id = isset($pbRow['poc_table_id']) ? (int) $pbRow['poc_table_id'] : 0;

        return $id > 0 ? $id : null;
    }

    /**
     * Attach correct poc row for approve preview / GST when duplicate consumables exist.
     */
    public static function resolvePotableForSupplierInvoiceLine(
        purchaseOrderConsumable $po,
        $supplierInvoiceProductRow
    ): ?pocTable {
        if ((int) $po->type !== 1) {
            return pocTable::query()
                ->where('poid', $supplierInvoiceProductRow->purchase_order_id)
                ->where('consumable_id', $supplierInvoiceProductRow->product_id)
                ->first();
        }
        if (self::isMonthEndConsumablePo($po)
            && self::supplierInvoiceProductsHasPocTableId()
            && ! empty($supplierInvoiceProductRow->poc_table_id)
        ) {
            $row = pocTable::query()
                ->where('poid', $po->id)
                ->where('id', (int) $supplierInvoiceProductRow->poc_table_id)
                ->first();
            if ($row) {
                return $row;
            }
        }

        return pocTable::query()
            ->where('poid', $supplierInvoiceProductRow->purchase_order_id)
            ->where('consumable_id', $supplierInvoiceProductRow->product_id)
            ->first();
    }

    /**
     * Unique DOM id prefix per invoice line (M-series can repeat consumable_id on one PO).
     */
    public static function invoiceLineDomKey(int $poid, int $pocTableId, int $rowIndex): string
    {
        if ($pocTableId > 0) {
            return 'p' . $poid . '_l' . $pocTableId;
        }

        return 'p' . $poid . '_r' . $rowIndex;
    }

    public static function virtualPocLineForInvoiceQty(pocTable $poc, float $receiveQty): object
    {
        $pocQty = (float) ($poc->quantity ?? 0);
        if ($pocQty <= 0 || $receiveQty <= 0) {
            return (object) [
                'amount' => 0.0,
                'gstslab' => (float) ($poc->gstslab ?? 0),
                'gstamount' => 0.0,
                'quantity' => $receiveQty,
            ];
        }

        if (abs($receiveQty - $pocQty) < 0.0001) {
            return (object) [
                'amount' => round((float) $poc->amount, 2),
                'gstslab' => (float) ($poc->gstslab ?? 0),
                'gstamount' => round((float) $poc->gstamount, 2),
                'quantity' => $receiveQty,
            ];
        }

        $ratio = $receiveQty / $pocQty;
        $amount = round((float) $poc->amount * $ratio, 2);
        $gstPct = (float) ($poc->gstslab ?? 0);

        return (object) [
            'amount' => $amount,
            'gstslab' => $gstPct,
            'gstamount' => self::lineGstAmount($amount, $gstPct),
            'quantity' => $receiveQty,
        ];
    }

    /**
     * @return array{subTotal: float, tgst: float, tamount: float, tquantity: float}|null
     */
    public static function headerTotalsFromSubmittedPbRows(array $pbRows, purchaseOrderConsumable $po): ?array
    {
        if (! self::isMonthEndConsumablePo($po)) {
            return null;
        }

        $virtualLines = [];
        foreach ($pbRows as $pb) {
            $receiveQty = (float) ($pb['receiveqty'] ?? 0);
            if ($receiveQty <= 0) {
                continue;
            }

            $poc = self::resolvePocLine(
                $po,
                (int) ($pb['product'] ?? 0),
                self::pocTableIdFromRequest($po, $pb)
            );
            if (! $poc) {
                continue;
            }

            $virtualLines[] = self::virtualPocLineForInvoiceQty($poc, $receiveQty);
        }

        if ($virtualLines === []) {
            return null;
        }

        $totals = self::headerTotalsFromPocLines($virtualLines);

        return [
            'subTotal' => $totals['subTotal'],
            'tgst' => $totals['tgst'],
            'tamount' => $totals['tamount'],
            'tquantity' => $totals['tquantity'],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, purchaseOrderConsumable>|array<int, purchaseOrderConsumable>  $purchaseOrders
     * @return array{subTotal: float, tgst: float, tamount: float, tquantity: float}|null
     */
    public static function headerTotalsFromSubmittedPbRowsMulti(array $pbRows, $purchaseOrders): ?array
    {
        $byPo = [];
        foreach ($pbRows as $pb) {
            $receiveQty = (float) ($pb['receiveqty'] ?? 0);
            if ($receiveQty <= 0) {
                continue;
            }
            $poid = (int) ($pb['poid'] ?? 0);
            if ($poid <= 0) {
                continue;
            }
            $byPo[$poid][] = $pb;
        }

        if ($byPo === []) {
            return null;
        }

        $subTotal = 0.0;
        $tgst = 0.0;
        $tquantity = 0.0;
        $usedMonthEnd = false;

        foreach ($byPo as $poid => $rows) {
            $po = is_array($purchaseOrders)
                ? ($purchaseOrders[$poid] ?? null)
                : $purchaseOrders->get($poid);
            if (! $po instanceof purchaseOrderConsumable) {
                continue;
            }

            if (self::isMonthEndConsumablePo($po)) {
                $chunk = self::headerTotalsFromSubmittedPbRows($rows, $po);
                if ($chunk === null) {
                    continue;
                }
                $usedMonthEnd = true;
                $subTotal += $chunk['subTotal'];
                $tgst += $chunk['tgst'];
                $tquantity += $chunk['tquantity'];

                continue;
            }

            foreach ($rows as $pb) {
                $amount = round((float) ($pb['amount'] ?? 0), 2);
                $gstPct = (float) ($pb['gstslab'] ?? 0);
                $subTotal += $amount;
                $tgst += self::lineGstAmount($amount, $gstPct);
                $tquantity += (float) ($pb['receiveqty'] ?? 0);
            }
        }

        if (! $usedMonthEnd) {
            return null;
        }

        $subTotal = round($subTotal, 2);
        $tgst = round($tgst, 2);

        return [
            'subTotal' => $subTotal,
            'tgst' => $tgst,
            'tamount' => round($subTotal + $tgst, 2),
            'tquantity' => $tquantity,
        ];
    }

    public static function soLineAmountFromRequest(
        purchaseOrderConsumable $po,
        array $pb,
        ?pocTable $pocLine = null
    ): float {
        $amount = round((float) ($pb['amount'] ?? 0), 2);
        if (! self::isMonthEndConsumablePo($po)) {
            return $amount;
        }

        $receiveQty = (float) ($pb['receiveqty'] ?? 0);
        if ($receiveQty <= 0) {
            return 0.0;
        }

        $poc = $pocLine ?? self::resolvePocLine(
            $po,
            (int) ($pb['product'] ?? 0),
            self::pocTableIdFromRequest($po, $pb)
        );
        if (! $poc) {
            return $amount;
        }

        return (float) self::virtualPocLineForInvoiceQty($poc, $receiveQty)->amount;
    }

    public static function soLineTotalFromRequest(
        purchaseOrderConsumable $po,
        array $pb,
        ?pocTable $pocLine = null
    ): float {
        $amount = round((float) ($pb['amount'] ?? 0), 2);
        $gstPct = (float) ($pb['gstslab'] ?? 0);
        if (! self::isMonthEndConsumablePo($po)) {
            return round($amount + self::lineGstAmount($amount, $gstPct), 2);
        }

        $receiveQty = (float) ($pb['receiveqty'] ?? 0);
        if ($receiveQty <= 0) {
            return 0.0;
        }

        $poc = $pocLine ?? self::resolvePocLine(
            $po,
            (int) ($pb['product'] ?? 0),
            self::pocTableIdFromRequest($po, $pb)
        );
        if (! $poc) {
            return round($amount + self::lineGstAmount($amount, $gstPct), 2);
        }

        $virtual = self::virtualPocLineForInvoiceQty($poc, $receiveQty);

        return round((float) $virtual->amount + (float) $virtual->gstamount, 2);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, purchaseOrderConsumable>|purchaseOrderConsumable  $purchaseOrders
     */
    public static function applyMonthEndHeaderToInvoice(
        supplierInvoice $invoice,
        array $pbRows,
        $purchaseOrders
    ): bool {
        if ($purchaseOrders instanceof purchaseOrderConsumable) {
            $headerTotals = self::headerTotalsFromSubmittedPbRows($pbRows, $purchaseOrders);
        } else {
            $headerTotals = self::headerTotalsFromSubmittedPbRowsMulti($pbRows, $purchaseOrders);
        }

        if ($headerTotals === null) {
            return false;
        }

        $invoice->subTotal = $headerTotals['subTotal'];
        $invoice->tgst = $headerTotals['tgst'];
        $invoice->tamount = $headerTotals['tamount'];
        $invoice->tquantity = $headerTotals['tquantity'];
        $invoice->roundoff = 0;
        $invoice->save();

        return true;
    }
}
