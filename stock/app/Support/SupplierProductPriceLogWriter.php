<?php

namespace App\Support;

use App\supplierProduct;
use App\SupplierProductPriceLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only price change logs for supplier_products (rate, UK45, revise/approve).
 */
final class SupplierProductPriceLogWriter
{
    public const TRACKED_FIELDS = [
        'rate' => 'Price',
        'uk_45_rate' => 'UK 45 Price',
        'pending_rate' => 'Pending rate',
        'effective_date' => 'Effective date',
        'admin_approved' => 'Admin approved',
    ];

    public static function tableExists(): bool
    {
        try {
            return Schema::hasTable('supplier_product_price_logs');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>|null  $beforeState  State before mutation (null = treat as create)
     */
    public static function log(
        supplierProduct $sp,
        string $eventType,
        string $source,
        ?array $beforeState = null
    ): ?SupplierProductPriceLog {
        if (! self::tableExists()) {
            return null;
        }

        $sp->refresh();
        $after = self::buildState($sp);
        $changed = self::diffStates($beforeState, $after);

        if ($eventType === 'update' && $changed === []) {
            return null;
        }

        if (in_array($eventType, ['create', 'remove'], true) && $changed === []) {
            $state = $eventType === 'remove' ? ($beforeState ?? $after) : $after;
            foreach (array_keys(self::TRACKED_FIELDS) as $key) {
                $changed[$key] = [
                    'label' => self::TRACKED_FIELDS[$key],
                    'from' => $eventType === 'remove' ? ($state[$key] ?? null) : null,
                    'to' => $eventType === 'remove' ? null : ($state[$key] ?? null),
                ];
            }
        }

        [$userId, $label] = self::actor();

        $summaryParts = [];
        foreach ($changed as $info) {
            $summaryParts[] = $info['label'] ?? '';
        }
        $summaryParts = array_filter($summaryParts);
        $summary = self::eventLabel($eventType);
        if ($summaryParts !== []) {
            $summary .= ': '.implode(', ', $summaryParts);
        }
        if (strlen($summary) > 490) {
            $summary = substr($summary, 0, 487).'...';
        }

        return SupplierProductPriceLog::create([
            'supplier_product_id' => $sp->id,
            'product_id' => (int) $sp->product_id,
            'supplier_id' => (int) $sp->supplier_id,
            'event_type' => $eventType,
            'source' => $source,
            'snapshot_json' => json_encode($after, JSON_UNESCAPED_UNICODE),
            'changed_fields_json' => json_encode($changed, JSON_UNESCAPED_UNICODE),
            'change_summary' => $summary,
            'changed_by' => $userId,
            'changed_by_label' => $label,
            'created_at' => now(),
        ]);
    }

    /**
     * Log a remove before the row is deleted (uses before state only).
     *
     * @param  array<string, mixed>  $beforeState
     */
    public static function logRemove(
        int $productId,
        int $supplierId,
        ?int $supplierProductId,
        array $beforeState,
        string $source = 'supplier_update'
    ): ?SupplierProductPriceLog {
        if (! self::tableExists()) {
            return null;
        }

        $changed = [];
        foreach (self::TRACKED_FIELDS as $key => $label) {
            $from = $beforeState[$key] ?? null;
            if ($from === null || $from === '') {
                continue;
            }
            $changed[$key] = [
                'label' => $label,
                'from' => $from,
                'to' => null,
            ];
        }
        if ($changed === []) {
            return null;
        }

        [$userId, $label] = self::actor();

        $summaryParts = array_column($changed, 'label');
        $summary = 'Removed: '.implode(', ', $summaryParts);
        if (strlen($summary) > 490) {
            $summary = substr($summary, 0, 487).'...';
        }

        return SupplierProductPriceLog::create([
            'supplier_product_id' => $supplierProductId,
            'product_id' => $productId,
            'supplier_id' => $supplierId,
            'event_type' => 'remove',
            'source' => $source,
            'snapshot_json' => json_encode($beforeState, JSON_UNESCAPED_UNICODE),
            'changed_fields_json' => json_encode($changed, JSON_UNESCAPED_UNICODE),
            'change_summary' => $summary,
            'changed_by' => $userId,
            'changed_by_label' => $label,
            'created_at' => now(),
        ]);
    }

    /**
     * Capture current row state for later diff (call before mutating).
     *
     * @return array<string, mixed>
     */
    public static function buildState(supplierProduct $sp): array
    {
        return [
            'rate' => self::normalizeNumber($sp->rate),
            'uk_45_rate' => self::normalizeNumber($sp->uk_45_rate),
            'pending_rate' => self::normalizeNumber($sp->pending_rate),
            'effective_date' => $sp->effective_date !== null && $sp->effective_date !== ''
                ? (string) $sp->effective_date
                : null,
            'admin_approved' => $sp->admin_approved === null || $sp->admin_approved === ''
                ? null
                : (int) (bool) $sp->admin_approved,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{label: string, from: mixed, to: mixed}>
     */
    public static function diffStates(?array $before, array $after): array
    {
        $changed = [];
        foreach (self::TRACKED_FIELDS as $key => $label) {
            $from = $before[$key] ?? null;
            $to = $after[$key] ?? null;
            if (! self::valuesEqual($from, $to)) {
                $changed[$key] = [
                    'label' => $label,
                    'from' => $from,
                    'to' => $to,
                ];
            }
        }

        return $changed;
    }

    public static function eventLabel(string $eventType): string
    {
        return match ($eventType) {
            'create' => 'Created',
            'update' => 'Updated',
            'revise' => 'Revise requested',
            'approve' => 'Approved',
            'remove' => 'Removed',
            default => ucfirst($eventType),
        };
    }

    public static function sourceLabel(string $source): string
    {
        return match ($source) {
            'import' => 'Pricing import',
            'csv_import' => 'CSV import',
            'supplier_create' => 'Supplier create',
            'supplier_update' => 'Supplier edit',
            'revise' => 'Supplier revise',
            'approve' => 'Admin approve',
            'allocation' => 'Allocation',
            default => $source,
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
        if (is_bool($value) || $value === 0 || $value === 1) {
            if ($value === true || $value === 1 || $value === '1') {
                return 'Yes';
            }
            if ($value === false || $value === 0 || $value === '0') {
                return 'No';
            }
        }

        return (string) $value;
    }

    /**
     * Latest log per product_id+supplier_id for pricing table.
     *
     * @param  iterable<int>  $productIds
     * @return array<string, SupplierProductPriceLog> keyed by "{product_id}:{supplier_id}"
     */
    public static function latestByProductSupplier(iterable $productIds): array
    {
        if (! self::tableExists()) {
            return [];
        }

        $productIds = collect($productIds)->filter()->unique()->values();
        if ($productIds->isEmpty()) {
            return [];
        }

        $rows = SupplierProductPriceLog::query()
            ->whereIn('product_id', $productIds)
            ->orderByDesc('id')
            ->get();

        $latest = [];
        foreach ($rows as $row) {
            $key = $row->product_id.':'.$row->supplier_id;
            if (! isset($latest[$key])) {
                $latest[$key] = $row;
            }
        }

        return $latest;
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

    private static function normalizeNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 4);
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
