<?php

namespace App\Support;

use App\poTable;
use App\purchaseOrder;
use App\PurchaseOrderActivityLog;
use App\PurchaseOrderVersion;
use App\Notification;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Furniture PO content versions (v1 on first update) + activity logs (send/cancel/status/accept).
 */
final class PurchaseOrderVersionWriter
{
    public const HEADER_FIELDS = [
        'pono' => 'PO No.',
        'supplier_id' => 'Supplier',
        'podate' => 'PO date',
        'del_date' => 'Delivery date',
        'ref_supplier' => 'Supplier ref',
        'buyer_orderno' => 'Buyer order no',
        'payterms' => 'Pay terms',
        'remarks' => 'Remarks',
        'subTotal' => 'Sub total',
        'tgst' => 'Total GST',
        'tquantity' => 'Total qty',
        'tamount' => 'Total amount',
        'totaldiscount' => 'Total discount',
        'address_option' => 'Address option',
        'po_revise_date' => 'Revise date',
    ];

    public const LINE_FIELDS = [
        'product_id' => 'Product',
        'ean' => 'EAN',
        'quantity' => 'Qty',
        'unit' => 'Unit',
        'rate' => 'Rate',
        'amount' => 'Amount',
        'gstslab' => 'GST slab',
        'gstamount' => 'GST amount',
        'priority' => 'Priority',
        'delivery_point' => 'Delivery point',
        'discount' => 'Discount',
        'discount_type' => 'Discount type',
        'legs' => 'Legs',
        'description' => 'Description',
    ];

    public static function versionsTableExists(): bool
    {
        try {
            return Schema::hasTable('purchase_order_versions');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function activityTableExists(): bool
    {
        try {
            return Schema::hasTable('purchase_order_activity_logs');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Snapshot of current live PO (header + lines). Call BEFORE mutating on update.
     *
     * @return array{header: array<string, mixed>, lines: array<int, array<string, mixed>>}
     */
    public static function buildSnapshot(purchaseOrder $po): array
    {
        $po->loadMissing('poTable');

        $header = [];
        foreach (array_keys(self::HEADER_FIELDS) as $key) {
            $header[$key] = self::normalizeScalar($po->{$key} ?? null);
        }

        $lines = [];
        foreach ($po->poTable as $line) {
            $row = [];
            foreach (array_keys(self::LINE_FIELDS) as $key) {
                $row[$key] = self::normalizeScalar($line->{$key} ?? null);
            }
            $lines[] = $row;
        }

        usort($lines, function ($a, $b) {
            return strcmp((string) ($a['product_id'] ?? ''), (string) ($b['product_id'] ?? ''));
        });

        return [
            'header' => $header,
            'lines' => $lines,
        ];
    }

    /**
     * After a content update: write next version (v1 on first update). Skips if nothing changed.
     */
    public static function recordUpdate(purchaseOrder $po, array $beforeSnapshot): ?PurchaseOrderVersion
    {
        if (! self::versionsTableExists()) {
            return null;
        }

        $po->refresh();
        $po->load('poTable');
        $after = self::buildSnapshot($po);
        $changed = self::diffSnapshots($beforeSnapshot, $after);

        if ($changed === []) {
            return null;
        }

        $nextVersion = (int) PurchaseOrderVersion::where('purchase_order_id', $po->id)->max('version');
        $nextVersion = $nextVersion + 1;

        [$userId, $label] = self::actor();

        $summary = self::friendlyVersionSummary($changed, $nextVersion);
        if (strlen($summary) > 490) {
            $summary = substr($summary, 0, 487).'...';
        }

        $version = PurchaseOrderVersion::create([
            'purchase_order_id' => (int) $po->id,
            'pono' => (string) $po->pono,
            'version' => $nextVersion,
            'snapshot_json' => json_encode($after, JSON_UNESCAPED_UNICODE),
            'before_snapshot_json' => json_encode($beforeSnapshot, JSON_UNESCAPED_UNICODE),
            'changed_fields_json' => json_encode($changed, JSON_UNESCAPED_UNICODE),
            'change_summary' => $summary,
            'changed_by' => $userId,
            'changed_by_label' => $label,
            'supplier_accepted_at' => null,
            'supplier_accepted_by' => null,
            'supplier_accepted_by_label' => null,
            'created_at' => now(),
        ]);

        self::notifySupplierOfNewVersion($po, $nextVersion);

        return $version;
    }

    /**
     * Dashboard / bell-style notification for the supplier login user(s).
     */
    public static function notifySupplierOfNewVersion(purchaseOrder $po, int $version): void
    {
        try {
            $users = User::where('supplier_id', (int) $po->supplier_id)->get();
            foreach ($users as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'notification' => 'PO '.$po->pono.' was updated to v'.$version
                        .'. Please review and accept the new version before raising invoice.',
                    'is_read' => 0,
                ]);
            }
        } catch (\Throwable $e) {
            // Never block PO save on notification failure
        }
    }

    /**
     * Pending furniture PO versions for a supplier (for popup).
     *
     * @return list<array{purchase_order_id:int,pono:string,pending:int,latest:int,versions_url:string}>
     */
    public static function pendingVersionPopupRows(int $supplierId): array
    {
        if (! self::versionsTableExists() || $supplierId <= 0) {
            return [];
        }

        $pending = PurchaseOrderVersion::query()
            ->whereNull('supplier_accepted_at')
            ->whereIn('purchase_order_id', function ($q) use ($supplierId) {
                $q->select('id')
                    ->from('purchase_order')
                    ->where('supplier_id', $supplierId)
                    ->whereNull('deleted_at');
            })
            ->selectRaw('purchase_order_id, COUNT(*) as pending_count, MAX(version) as latest_version, MAX(pono) as pono')
            ->groupBy('purchase_order_id')
            ->orderByDesc('latest_version')
            ->get();

        $rows = [];
        foreach ($pending as $row) {
            $poId = (int) $row->purchase_order_id;
            $rows[] = [
                'purchase_order_id' => $poId,
                'pono' => (string) ($row->pono ?: ('#'.$poId)),
                'pending' => (int) $row->pending_count,
                'latest' => (int) $row->latest_version,
                'versions_url' => url('/supplier-dashboard/purchase-orders/'.$poId.'/versions'),
            ];
        }

        return $rows;
    }

    public static function logActivity(
        purchaseOrder $po,
        string $eventType,
        ?string $fromValue = null,
        ?string $toValue = null,
        ?string $summary = null
    ): ?PurchaseOrderActivityLog {
        if (! self::activityTableExists()) {
            return null;
        }

        [$userId, $label] = self::actor();

        if ($summary === null || $summary === '') {
            $summary = self::eventLabel($eventType);
            if ($fromValue !== null || $toValue !== null) {
                $summary .= ': '.(string) ($fromValue ?? '—').' → '.(string) ($toValue ?? '—');
            }
        }

        return PurchaseOrderActivityLog::create([
            'purchase_order_id' => (int) $po->id,
            'pono' => (string) $po->pono,
            'event_type' => $eventType,
            'from_value' => $fromValue,
            'to_value' => $toValue,
            'summary' => $summary,
            'changed_by' => $userId,
            'changed_by_label' => $label,
            'created_at' => now(),
        ]);
    }

    /**
     * Raise invoice allowed when there are no versions, or every version is supplier-accepted.
     */
    public static function canRaiseInvoice(int $purchaseOrderId): bool
    {
        if (! self::versionsTableExists()) {
            return true;
        }

        $pending = PurchaseOrderVersion::where('purchase_order_id', $purchaseOrderId)
            ->whereNull('supplier_accepted_at')
            ->count();

        return $pending === 0;
    }

    /**
     * @return array{ok: bool, message: string, pending: int, latest: ?int}
     */
    public static function invoiceGateStatus(int $purchaseOrderId): array
    {
        if (! self::versionsTableExists()) {
            return ['ok' => true, 'message' => '', 'pending' => 0, 'latest' => null];
        }

        $latest = (int) PurchaseOrderVersion::where('purchase_order_id', $purchaseOrderId)->max('version');
        $pending = PurchaseOrderVersion::where('purchase_order_id', $purchaseOrderId)
            ->whereNull('supplier_accepted_at')
            ->count();

        if ($pending === 0) {
            return [
                'ok' => true,
                'message' => '',
                'pending' => 0,
                'latest' => $latest > 0 ? $latest : null,
            ];
        }

        $pendingList = PurchaseOrderVersion::where('purchase_order_id', $purchaseOrderId)
            ->whereNull('supplier_accepted_at')
            ->orderBy('version')
            ->pluck('version')
            ->map(fn ($v) => 'v'.$v)
            ->implode(', ');

        return [
            'ok' => false,
            'message' => 'Cannot raise invoice until all PO versions are accepted. Pending: '.$pendingList.'.',
            'pending' => $pending,
            'latest' => $latest > 0 ? $latest : null,
        ];
    }

    public static function acceptVersion(PurchaseOrderVersion $version): bool
    {
        if ($version->isAccepted()) {
            return true;
        }

        [$userId, $label] = self::actor();
        $version->supplier_accepted_at = now();
        $version->supplier_accepted_by = $userId;
        $version->supplier_accepted_by_label = $label;

        return $version->save();
    }

    public static function latestVersionNumber(int $purchaseOrderId): ?int
    {
        if (! self::versionsTableExists()) {
            return null;
        }
        $v = (int) PurchaseOrderVersion::where('purchase_order_id', $purchaseOrderId)->max('version');

        return $v > 0 ? $v : null;
    }

    /**
     * @param  iterable<int>  $purchaseOrderIds
     * @return array<int, int> poId => latest version
     */
    public static function latestVersionMap(iterable $purchaseOrderIds): array
    {
        if (! self::versionsTableExists()) {
            return [];
        }
        $ids = collect($purchaseOrderIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $rows = PurchaseOrderVersion::query()
            ->whereIn('purchase_order_id', $ids)
            ->selectRaw('purchase_order_id, MAX(version) as max_version')
            ->groupBy('purchase_order_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->purchase_order_id] = (int) $row->max_version;
        }

        return $map;
    }

    /**
     * @param  iterable<int>  $purchaseOrderIds
     * @return array<int, int> poId => pending version count
     */
    public static function pendingVersionCountMap(iterable $purchaseOrderIds): array
    {
        if (! self::versionsTableExists()) {
            return [];
        }
        $ids = collect($purchaseOrderIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $rows = PurchaseOrderVersion::query()
            ->whereIn('purchase_order_id', $ids)
            ->whereNull('supplier_accepted_at')
            ->selectRaw('purchase_order_id, COUNT(*) as pending_count')
            ->groupBy('purchase_order_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->purchase_order_id] = (int) $row->pending_count;
        }

        return $map;
    }

    public static function eventLabel(string $eventType): string
    {
        return match ($eventType) {
            'send' => 'Sent to supplier',
            'cancel' => 'Canceled',
            'status' => 'Status changed',
            'accept_po' => 'PO accepted by supplier',
            'accept_version' => 'Version accepted by supplier',
            default => ucfirst(str_replace('_', ' ', $eventType)),
        };
    }

    /** Human label for purchase_order.status values. */
    public static function poStatusLabel($value): string
    {
        return match ((string) $value) {
            '0' => 'Pending',
            '1' => 'Complete',
            '2' => 'Canceled',
            default => ($value === null || $value === '') ? '—' : (string) $value,
        };
    }

    /**
     * One simple line for activity tables (no raw 0/1 From–To).
     */
    public static function activityMessage($activity): string
    {
        $type = (string) ($activity->event_type ?? '');
        $from = $activity->from_value ?? null;
        $to = $activity->to_value ?? null;
        $summary = trim((string) ($activity->summary ?? ''));

        return match ($type) {
            'send' => 'PO was sent to the supplier',
            'accept_po' => 'Supplier accepted this PO',
            'accept_version' => $summary !== ''
                ? $summary
                : ('Supplier accepted '.((string) ($to ?: 'a version'))),
            'cancel' => 'PO was canceled',
            'status' => 'Status changed from '
                .self::poStatusLabel($from)
                .' to '
                .self::poStatusLabel($to),
            default => $summary !== '' ? $summary : self::eventLabel($type),
        };
    }

    public static function friendlyWhen($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        try {
            $dt = $value instanceof \DateTimeInterface
                ? \Carbon\Carbon::instance($value)
                : \Carbon\Carbon::parse($value);

            return $dt->format('d M Y, H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    public static function formatValue($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    /**
     * Human labels matching furniture PO create/edit selects.
     */
    public static function formatFieldValue(?string $fieldKey, $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $key = (string) $fieldKey;
        $str = is_bool($value)
            ? ($value ? '1' : '0')
            : (is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE));

        return match ($key) {
            'legs' => match ($str) {
                '1' => 'With Legs',
                '2' => 'Without Legs',
                '0' => '—',
                default => $str,
            },
            'address_option' => match ($str) {
                '1' => 'Office',
                '2' => 'Factory',
                '100' => 'Month-end',
                default => $str,
            },
            'discount_type' => match ($str) {
                'Per' => 'Per',
                'Amount' => 'Amount',
                default => $str,
            },
            'priority' => $str === '' ? 'None' : $str,
            'delivery_point' => $str === '' ? 'None' : $str,
            'admin_approved' => match ($str) {
                '1', 'true' => 'Yes',
                '0', 'false' => 'No',
                default => $str,
            },
            default => self::formatValue($value),
        };
    }

    /**
     * Short summary for version list (works for new + existing changed_fields JSON).
     *
     * @param  array<string, array{label?: string, from?: mixed, to?: mixed}>  $changed
     */
    public static function friendlyVersionSummary(array $changed, ?int $version = null): string
    {
        $grouped = self::groupChangesForDisplay($changed);
        $bits = [];

        $headerFields = [];
        foreach ($grouped['header'] as $row) {
            // Skip noisy auto-totals when line prices also changed
            if (in_array($row['key'] ?? '', ['subTotal', 'tgst', 'tamount'], true) && $grouped['products'] !== []) {
                continue;
            }
            if (in_array($row['key'] ?? '', ['po_revise_date'], true)) {
                continue;
            }
            $headerFields[] = $row['field'];
        }
        $headerFields = array_values(array_unique($headerFields));
        if ($headerFields !== []) {
            $bits[] = implode(', ', array_slice($headerFields, 0, 4));
        }

        foreach ($grouped['products'] as $sku => $rows) {
            $fields = [];
            foreach ($rows as $row) {
                if (in_array($row['key'] ?? '', ['amount', 'gstamount'], true)) {
                    continue; // derived from rate/qty — shown under rate if needed
                }
                $fields[] = $row['field'];
            }
            $fields = array_values(array_unique($fields));
            if ($fields === []) {
                $fields = ['details'];
            }
            $bits[] = $sku.' ('.implode(', ', array_slice($fields, 0, 4)).')';
        }

        foreach ($grouped['other'] as $row) {
            $bits[] = $row['field'];
        }

        if ($bits === []) {
            $bits[] = count($changed).' change(s)';
        }

        $prefix = $version ? ('v'.$version.' · ') : '';

        return $prefix.implode(' · ', array_slice($bits, 0, 5));
    }

    /**
     * Group changes for a simple compare UI.
     *
     * @param  array<string, array{label?: string, from?: mixed, to?: mixed}>  $changed
     * @return array{header: list<array>, products: array<string, list<array>>, other: list<array>}
     */
    public static function groupChangesForDisplay(array $changed): array
    {
        $header = [];
        $products = [];
        $other = [];
        $productIds = [];

        foreach ($changed as $key => $info) {
            if (str_starts_with((string) $key, 'header.')) {
                $fieldKey = substr((string) $key, 7);
                $header[] = [
                    'key' => $fieldKey,
                    'field' => self::HEADER_FIELDS[$fieldKey] ?? ($info['label'] ?? $fieldKey),
                    'from' => $info['from'] ?? null,
                    'to' => $info['to'] ?? null,
                ];
                continue;
            }

            if (preg_match('/^line\.(\d+)\.(.+)$/', (string) $key, $m)) {
                $productIds[] = (int) $m[1];
                $fieldKey = $m[2];
                $skuKey = 'pid:'.$m[1];
                if (! isset($products[$skuKey])) {
                    $products[$skuKey] = [
                        'product_id' => (int) $m[1],
                        'rows' => [],
                    ];
                }
                if ($fieldKey === 'added' || $fieldKey === 'removed') {
                    $products[$skuKey]['rows'][] = [
                        'key' => $fieldKey,
                        'field' => $fieldKey === 'added' ? 'Added' : 'Removed',
                        'from' => $info['from'] ?? null,
                        'to' => $info['to'] ?? null,
                    ];
                } else {
                    $products[$skuKey]['rows'][] = [
                        'key' => $fieldKey,
                        'field' => self::LINE_FIELDS[$fieldKey] ?? ($info['label'] ?? $fieldKey),
                        'from' => $info['from'] ?? null,
                        'to' => $info['to'] ?? null,
                    ];
                }
                continue;
            }

            $other[] = [
                'key' => (string) $key,
                'field' => $info['label'] ?? (string) $key,
                'from' => $info['from'] ?? null,
                'to' => $info['to'] ?? null,
            ];
        }

        $codes = [];
        if ($productIds !== []) {
            try {
                $codes = \App\product::whereIn('id', array_unique($productIds))
                    ->pluck('code', 'id')
                    ->all();
            } catch (\Throwable $e) {
                $codes = [];
            }
        }

        $bySku = [];
        foreach ($products as $bundle) {
            $pid = (int) $bundle['product_id'];
            $sku = $codes[$pid] ?? ('Product #'.$pid);
            if (! isset($bySku[$sku])) {
                $bySku[$sku] = [];
            }
            foreach ($bundle['rows'] as $row) {
                $bySku[$sku][] = $row;
            }
        }

        return [
            'header' => $header,
            'products' => $bySku,
            'other' => $other,
        ];
    }

    /**
     * @return array<string, array{label: string, from: mixed, to: mixed}>
     */
    public static function diffSnapshots(array $before, array $after): array
    {
        $changed = [];

        $beforeHeader = $before['header'] ?? [];
        $afterHeader = $after['header'] ?? [];
        foreach (self::HEADER_FIELDS as $key => $label) {
            $from = $beforeHeader[$key] ?? null;
            $to = $afterHeader[$key] ?? null;
            if (! self::valuesEqual($from, $to)) {
                $changed['header.'.$key] = [
                    'label' => $label,
                    'from' => $from,
                    'to' => $to,
                ];
            }
        }

        $beforeLines = self::linesByProduct($before['lines'] ?? []);
        $afterLines = self::linesByProduct($after['lines'] ?? []);
        $allProducts = array_unique(array_merge(array_keys($beforeLines), array_keys($afterLines)));

        foreach ($allProducts as $productId) {
            $b = $beforeLines[$productId] ?? null;
            $a = $afterLines[$productId] ?? null;
            if ($b === null && $a !== null) {
                $changed['line.'.$productId.'.added'] = [
                    'label' => 'Line product '.$productId.' added',
                    'from' => null,
                    'to' => self::lineSummary($a),
                ];
                continue;
            }
            if ($b !== null && $a === null) {
                $changed['line.'.$productId.'.removed'] = [
                    'label' => 'Line product '.$productId.' removed',
                    'from' => self::lineSummary($b),
                    'to' => null,
                ];
                continue;
            }
            foreach (self::LINE_FIELDS as $key => $label) {
                if (in_array($key, ['product_id'], true)) {
                    continue;
                }
                $from = $b[$key] ?? null;
                $to = $a[$key] ?? null;
                if (! self::valuesEqual($from, $to)) {
                    $changed['line.'.$productId.'.'.$key] = [
                        'label' => 'Product '.$productId.' · '.$label,
                        'from' => $from,
                        'to' => $to,
                    ];
                }
            }
        }

        return $changed;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<string, array<string, mixed>>
     */
    private static function linesByProduct(array $lines): array
    {
        $map = [];
        foreach ($lines as $line) {
            $pid = (string) ($line['product_id'] ?? '');
            if ($pid === '') {
                continue;
            }
            // If duplicate products, last wins for diff (matches live matching by product_id)
            $map[$pid] = $line;
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private static function lineSummary(array $line): string
    {
        return 'qty='.($line['quantity'] ?? '—')
            .' rate='.($line['rate'] ?? '—')
            .' amt='.($line['amount'] ?? '—');
    }

    /**
     * @return array{0: ?int, 1: ?string}
     */
    private static function actor(): array
    {
        $user = Auth::user();
        if (! $user) {
            return [null, 'System'];
        }

        $label = trim(($user->firstname ?? '').' '.($user->lastname ?? ''));
        if ($label === '') {
            $label = $user->email ?? ('User #'.$user->id);
        } elseif (! empty($user->email)) {
            $label .= ' ('.$user->email.')';
        }

        return [(int) $user->id, $label];
    }

    private static function normalizeScalar($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_numeric($value) && ! is_string($value)) {
            return round((float) $value, 4);
        }
        if (is_numeric($value) && preg_match('/^-?\d+(\.\d+)?$/', (string) $value)) {
            return round((float) $value, 4);
        }

        return (string) $value;
    }

    private static function valuesEqual($a, $b): bool
    {
        if ($a === null && $b === null) {
            return true;
        }
        if (is_numeric($a) && is_numeric($b)) {
            return abs((float) $a - (float) $b) < 0.00001;
        }

        return (string) $a === (string) $b;
    }
}
