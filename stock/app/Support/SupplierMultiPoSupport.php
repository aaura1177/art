<?php

namespace App\Support;

use App\purchaseOrderConsumable;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class SupplierMultiPoSupport
{
    public const CONSUMABLE_PO_UI_PREFIX = '10000000';

    public static function normalizeConsumablePoId($id): int
    {
        $id = trim((string) $id);
        if (str_starts_with($id, self::CONSUMABLE_PO_UI_PREFIX)) {
            return (int) substr($id, strlen(self::CONSUMABLE_PO_UI_PREFIX));
        }

        return (int) $id;
    }

    /**
     * @param  array<int|string>|string  $ids
     * @return int[]
     */
    public static function normalizeConsumablePoIds($ids): array
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($id) => self::normalizeConsumablePoId($id),
            (array) $ids
        ), fn ($id) => $id > 0)));
    }

    /**
     * @param  iterable<purchaseOrderConsumable>  $purchaseOrders
     * @return array<string, string>  pono => eligible date (d-m-Y)
     */
    public static function ineligiblePoSummary($purchaseOrders): array
    {
        $today = Carbon::today();
        $ineligible = [];

        foreach ($purchaseOrders as $purchaseOrder) {
            $rawDeliveryDate = optional($purchaseOrder)->del_date;
            if (empty($rawDeliveryDate)) {
                continue;
            }

            try {
                $deliveryDate = Carbon::parse($rawDeliveryDate)->startOfDay();
            } catch (\Throwable $e) {
                continue;
            }

            $eligibleDate = $deliveryDate->copy()->subDays(7)->startOfDay();
            if ($today->lt($eligibleDate)) {
                $poNumber = trim((string) (optional($purchaseOrder)->pono ?? 'PO'));
                $ineligible[$poNumber] = $eligibleDate->format('d-m-Y');
            }
        }

        return $ineligible;
    }

    /**
     * @param  Collection<int, purchaseOrderConsumable>  $pos
     */
    public static function consumableEwayThreshold(Collection $pos): float
    {
        foreach ($pos as $po) {
            if (ConsumableMonthEndInvoiceSupport::isMonthEndConsumablePo($po)) {
                continue;
            }
            $city = strtoupper(trim((string) optional($po->supplier)->city));
            if ($city === 'JAIPUR') {
                return 200000.0;
            }
        }

        return 100000.0;
    }

    /**
     * @param  Collection<int, purchaseOrderConsumable>  $pos
     */
    public static function requiresConsumableEway(Collection $pos, float $invoiceTotal): bool
    {
        foreach ($pos as $po) {
            if (! ConsumableMonthEndInvoiceSupport::isMonthEndConsumablePo($po)) {
                return $invoiceTotal > self::consumableEwayThreshold($pos);
            }
        }

        return false;
    }

    public static function purchaseBillHasMultiPoColumn(string $table): bool
    {
        return Schema::hasColumn($table, 'mulitple_po_purchaseBill');
    }

    /**
     * Whether purchaseOrder_id on a bill table accepts comma-separated PO ids (VARCHAR).
     */
    public static function purchaseBillOrderIdAcceptsCsv(string $table): bool
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'purchaseOrder_id')) {
            return false;
        }

        try {
            $database = Schema::getConnection()->getDatabaseName();
            $row = Schema::getConnection()->selectOne(
                'SELECT DATA_TYPE FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$database, $table, 'purchaseOrder_id']
            );
            $dataType = strtolower((string) ($row->DATA_TYPE ?? ''));

            return in_array($dataType, ['varchar', 'char', 'text', 'mediumtext', 'longtext'], true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Value for purchase_bill*.purchaseOrder_id on multi-PO approve/create.
     *
     * @param  int[]  $poIds
     */
    public static function purchaseBillPurchaseOrderIdValue(array $poIds, string $table): int|string
    {
        $poIds = array_values(array_filter(array_map('intval', $poIds), fn ($id) => $id > 0));
        if ($poIds === []) {
            return 0;
        }
        if (count($poIds) === 1 || self::purchaseBillOrderIdAcceptsCsv($table)) {
            return implode(',', $poIds);
        }

        return $poIds[0];
    }

    /**
     * @param  int[]  $poIds
     */
    public static function applyMultiPoPurchaseBillAttrs(array &$billAttrs, string $table, array $poIds, ?string $multiPoNumbers): void
    {
        $billAttrs['purchaseOrder_id'] = self::purchaseBillPurchaseOrderIdValue($poIds, $table);
        if (self::purchaseBillHasMultiPoColumn($table) && $multiPoNumbers !== null && $multiPoNumbers !== '') {
            $billAttrs['mulitple_po_purchaseBill'] = $multiPoNumbers;
        }
    }

    /**
     * Whether a purchase bill spans multiple POs (consumable/carton/furniture).
     */
    public static function isMultiPoPurchaseBill(object $bill): bool
    {
        $multi = trim((string) ($bill->mulitple_po_purchaseBill ?? ''));
        if ($multi !== '') {
            return true;
        }

        return str_contains(trim((string) ($bill->purchaseOrder_id ?? '')), ',');
    }

    /**
     * Comma-separated PO numbers for purchase bill list/modal headers.
     */
    public static function displayPurchaseBillPoNumbers(object $bill): string
    {
        $multi = trim((string) ($bill->mulitple_po_purchaseBill ?? ''));
        if ($multi !== '') {
            return $multi;
        }

        if (isset($bill->purchaseOrder) && ! empty($bill->purchaseOrder->pono)) {
            return (string) $bill->purchaseOrder->pono;
        }

        $rawIds = trim((string) ($bill->purchaseOrder_id ?? ''));
        if ($rawIds === '') {
            return '';
        }

        $idList = array_values(array_filter(array_map('trim', explode(',', $rawIds))));
        if ($idList === []) {
            return '';
        }

        $ponos = purchaseOrderConsumable::whereIn('id', self::normalizeConsumablePoIds($idList))
            ->pluck('pono')
            ->filter()
            ->unique()
            ->values();

        return $ponos->implode(', ');
    }

    /**
     * Supplier ref(s) for consumable/carton purchase bill rows.
     */
    public static function consumablePurchaseBillSupplierRefs(object $bill): string
    {
        $multi = trim((string) ($bill->mulitple_po_purchaseBill ?? ''));
        if ($multi !== '') {
            $poNos = array_values(array_filter(array_map('trim', explode(',', $multi))));
            if ($poNos !== []) {
                return purchaseOrderConsumable::whereIn('pono', $poNos)
                    ->pluck('ref_supplier')
                    ->filter()
                    ->unique()
                    ->values()
                    ->implode(', ');
            }
        }

        $rawIds = trim((string) ($bill->purchaseOrder_id ?? ''));
        if (str_contains($rawIds, ',')) {
            $ponos = purchaseOrderConsumable::whereIn('id', self::normalizeConsumablePoIds(explode(',', $rawIds)))
                ->pluck('ref_supplier')
                ->filter()
                ->unique()
                ->values();

            if ($ponos->isNotEmpty()) {
                return $ponos->implode(', ');
            }
        }

        return (string) optional($bill->purchaseOrder)->ref_supplier;
    }

    /**
     * Supplier display name on purchase bill list/modal.
     */
    public static function purchaseBillSupplierName(object $bill): string
    {
        if (! empty($bill->supplier_id)) {
            $supplier = null;
            if ($bill instanceof \Illuminate\Database\Eloquent\Model && $bill->relationLoaded('supplier')) {
                $supplier = $bill->supplier;
            }
            if (! $supplier) {
                $supplier = \App\supplier::find($bill->supplier_id);
            }
            if ($supplier && ! empty($supplier->c_name)) {
                return (string) $supplier->c_name;
            }
        }

        return (string) optional(optional($bill->purchaseOrder)->supplier)->c_name;
    }

    /**
     * Consumable PO ids linked to a purchase bill (single or multi-PO).
     *
     * @return int[]
     */
    public static function consumablePurchaseBillPoIds(object $bill): array
    {
        $rawIds = trim((string) ($bill->purchaseOrder_id ?? ''));
        if (str_contains($rawIds, ',')) {
            return self::normalizeConsumablePoIds(explode(',', $rawIds));
        }

        if ($rawIds !== '' && (int) $rawIds > 0) {
            return [(int) $rawIds];
        }

        $multi = trim((string) ($bill->mulitple_po_purchaseBill ?? ''));
        if ($multi !== '') {
            $poNos = array_values(array_filter(array_map('trim', explode(',', $multi))));

            return purchaseOrderConsumable::whereIn('pono', $poNos)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return [];
    }

    /**
     * First consumable PO id on a purchase bill (for modal supplier block).
     */
    public static function firstConsumablePurchaseBillPoId(object $bill): int
    {
        $rawIds = trim((string) ($bill->purchaseOrder_id ?? ''));
        if ($rawIds === '') {
            return 0;
        }

        return self::normalizeConsumablePoIds(explode(',', $rawIds))[0] ?? 0;
    }

    /**
     * Admin supplier invoice list status (Pending / Approved / Verified / Cancelled).
     *
     * @return array{label: string, class: string}
     */
    public static function adminSupplierInvoiceStatus(object $invoice): array
    {
        if ((int) ($invoice->status ?? 0) === 2) {
            return ['label' => 'Cancelled', 'class' => 'badge badge-danger'];
        }

        if ((int) ($invoice->is_approved ?? 0) === 1) {
            $bill = self::resolvePurchaseBillForInvoice($invoice);
            if ($bill && (int) ($bill->is_checked ?? 0) === 1) {
                return ['label' => 'Verified', 'class' => 'badge badge-success'];
            }

            return ['label' => 'Approved', 'class' => 'badge badge-info'];
        }

        return ['label' => 'Pending', 'class' => 'badge badge-warning'];
    }

    protected static function resolvePurchaseBillForInvoice(object $invoice): ?object
    {
        $type = (string) ($invoice->purchase_order_type ?? '');

        if ($type === 'Consumable') {
            if ($invoice instanceof \App\supplierInvoice && $invoice->relationLoaded('consumablePurchaseBill')) {
                return $invoice->consumablePurchaseBill;
            }

            return \App\purchaseBillConsumable::where('supplier_invoice_id', $invoice->id)->first();
        }

        if ($type === 'Carton') {
            if ($invoice instanceof \App\supplierInvoice && $invoice->relationLoaded('cartonPurchaseBill')) {
                return $invoice->cartonPurchaseBill;
            }

            return \App\PurchaseBillCarton::where('supplier_invoice_id', $invoice->id)->first();
        }

        return null;
    }

    /**
     * Comma-separated PO numbers for multi-PO invoice list/modal headers.
     */
    public static function displayMultiPoNumbers(object $invoice): string
    {
        $stored = trim((string) ($invoice->mulitple_po ?? ''));
        $rawIds = trim((string) ($invoice->purchase_order_id ?? ''));

        if ($rawIds === '' || ! str_contains($rawIds, ',')) {
            return $stored;
        }

        $idList = array_values(array_filter(array_map('trim', explode(',', $rawIds))));
        if (count($idList) <= 1) {
            return $stored;
        }

        $type = (string) ($invoice->purchase_order_type ?? '');

        if ($type === 'Furniture') {
            $ponos = \App\purchaseOrder::whereIn('id', $idList)->pluck('pono')->filter()->unique()->values();
        } elseif (in_array($type, ['Consumable', 'Carton'], true)) {
            $ponos = purchaseOrderConsumable::whereIn('id', self::normalizeConsumablePoIds($idList))
                ->pluck('pono')
                ->filter()
                ->unique()
                ->values();
        } else {
            return $stored;
        }

        $fromDb = $ponos->implode(', ');

        return $fromDb !== '' ? $fromDb : $stored;
    }
}
