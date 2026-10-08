<?php

namespace App\Support;

use App\courier;
use App\CourierSnapshot;
use App\Services\UsCourierRuleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Captures full courier config snapshots on create/update and diffs against the previous version.
 */
final class CourierSnapshotWriter
{
    /** Flattened "columns" tracked for last-updated + change detail. */
    public const TRACKED_COLUMNS = [
        'name' => 'Courier name',
        'rate' => 'Base price',
        'fuel_charge_percent' => 'Fuel charge (%)',
        'country' => 'Country',
        'is_default' => 'Default for country?',
        'fixed_rate_weight' => 'Included weight',
        'rate_per_kg' => 'Extra charge per kg/lb',
        'weight_rate_tiers' => 'Weight price bands',
        'custom_condition' => 'Old-style extra charges',
        'surcharge_rules' => 'Extra charge rules',
    ];

    public static function tableExists(): bool
    {
        try {
            return Schema::hasTable('courier_snapshots');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function capture(courier $courier, string $eventType = 'update'): ?CourierSnapshot
    {
        if (! self::tableExists()) {
            return null;
        }

        $courier->refresh();
        $state = self::buildState($courier);
        $previous = CourierSnapshot::where('courier_id', $courier->id)
            ->orderByDesc('id')
            ->first();

        $prevState = $previous ? $previous->snapshot : null;
        $changed = self::diffStates($prevState, $state);

        if ($eventType === 'update' && $previous && $changed === []) {
            // Nothing actually changed — skip empty snapshot
            return null;
        }

        if ($eventType === 'create' && $changed === []) {
            foreach (array_keys(self::TRACKED_COLUMNS) as $key) {
                $changed[$key] = [
                    'label' => self::TRACKED_COLUMNS[$key],
                    'from' => null,
                    'to' => $state[$key] ?? null,
                ];
            }
        }

        $user = Auth::user();
        $label = null;
        if ($user) {
            $label = trim(($user->firstname ?? '').' '.($user->lastname ?? ''));
            if ($label === '') {
                $label = $user->email ?? ('User #'.$user->id);
            } else {
                $label .= $user->email ? ' ('.$user->email.')' : '';
            }
        }

        $summaryParts = [];
        foreach ($changed as $key => $info) {
            $summaryParts[] = $info['label'] ?? $key;
        }
        $summary = $eventType === 'create'
            ? 'Created'
            : ($summaryParts === [] ? 'Updated' : 'Changed: '.implode(', ', $summaryParts));
        if (strlen($summary) > 490) {
            $summary = substr($summary, 0, 487).'...';
        }

        return CourierSnapshot::create([
            'courier_id' => $courier->id,
            'courier_name' => $courier->name,
            'country' => $courier->country,
            'snapshot_json' => json_encode($state, JSON_UNESCAPED_UNICODE),
            'changed_fields_json' => json_encode($changed, JSON_UNESCAPED_UNICODE),
            'change_summary' => $summary,
            'changed_by' => $user->id ?? null,
            'changed_by_label' => $label,
            'event_type' => $eventType,
            'created_at' => now(),
        ]);
    }

    /**
     * Build normalized state for snapshot/diff.
     *
     * @return array<string, mixed>
     */
    public static function buildState(courier $courier): array
    {
        $tiers = DB::table('courier_weight_rate_tiers')
            ->where('courier_id', $courier->id)
            ->where('is_active', 1)
            ->orderBy('weight_from')
            ->orderBy('sort_order')
            ->get(['weight_from', 'weight_to', 'rate_per_unit', 'sort_order', 'is_active'])
            ->map(fn ($t) => [
                'weight_from' => (float) $t->weight_from,
                'weight_to' => $t->weight_to !== null ? (float) $t->weight_to : null,
                'rate_per_unit' => (float) $t->rate_per_unit,
                'sort_order' => (int) $t->sort_order,
                'is_active' => (int) $t->is_active,
            ])
            ->values()
            ->all();

        $custom = $courier->custom_condition;
        if (is_string($custom)) {
            $decoded = json_decode($custom, true);
            $custom = is_array($decoded) ? $decoded : $custom;
        }

        $rules = [];
        if (UsCourierRuleService::tablesExist()) {
            $country = UsCourierRuleService::normalizeCountry((string) $courier->country);
            if (UsCourierRuleService::supportsBlockEngine($country)) {
                $rules = UsCourierRuleService::loadBlocksArray((int) $courier->id, $country);
            }
        }

        return [
            'name' => (string) $courier->name,
            'rate' => (float) $courier->rate,
            'fuel_charge_percent' => (float) $courier->fuel_charge_percent,
            'country' => (string) $courier->country,
            'is_default' => (int) $courier->is_default,
            'fixed_rate_weight' => $courier->fixed_rate_weight !== null ? (float) $courier->fixed_rate_weight : null,
            'rate_per_kg' => $courier->rate_per_kg !== null ? (float) $courier->rate_per_kg : null,
            'weight_rate_tiers' => $tiers,
            'custom_condition' => $custom,
            'surcharge_rules' => $rules,
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
        foreach (self::TRACKED_COLUMNS as $key => $label) {
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

    private static function valuesEqual($a, $b): bool
    {
        return json_encode(self::normalizeForCompare($a)) === json_encode(self::normalizeForCompare($b));
    }

    private static function normalizeForCompare($value)
    {
        if (is_array($value)) {
            // Sort associative keys for stable compare; keep list order for bands/rules
            $isList = array_keys($value) === range(0, count($value) - 1);
            if ($isList) {
                return array_map([self::class, 'normalizeForCompare'], $value);
            }
            ksort($value);
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = self::normalizeForCompare($v);
            }

            return $out;
        }
        if (is_float($value) || is_int($value)) {
            return round((float) $value, 4);
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }

    /**
     * For each courier, when each tracked column was last changed.
     *
     * @return array<int, array{courier: courier, columns: array<string, array{label: string, last_updated: ?string, snapshot_id: ?int}>, last_snapshot_at: ?string}>
     */
    public static function columnLastUpdatedMatrix($couriers): array
    {
        $out = [];
        foreach ($couriers as $courier) {
            $columns = [];
            foreach (self::TRACKED_COLUMNS as $key => $label) {
                $columns[$key] = [
                    'label' => $label,
                    'last_updated' => null,
                    'snapshot_id' => null,
                ];
            }

            $snapshots = CourierSnapshot::where('courier_id', $courier->id)
                ->orderByDesc('id')
                ->get(['id', 'changed_fields_json', 'created_at', 'event_type']);

            $lastSnapshotAt = $snapshots->first()->created_at ?? $courier->updated_at;

            foreach ($snapshots as $snap) {
                $changed = $snap->changed_fields;
                if ($snap->event_type === 'create' && $changed === []) {
                    $changed = array_fill_keys(array_keys(self::TRACKED_COLUMNS), true);
                }
                foreach ($columns as $key => &$col) {
                    if ($col['last_updated'] !== null) {
                        continue;
                    }
                    if (array_key_exists($key, $changed)) {
                        $col['last_updated'] = optional($snap->created_at)->format('Y-m-d H:i:s');
                        $col['snapshot_id'] = (int) $snap->id;
                    }
                }
                unset($col);
            }

            $out[] = [
                'courier' => $courier,
                'columns' => $columns,
                'last_snapshot_at' => $lastSnapshotAt
                    ? (is_string($lastSnapshotAt) ? $lastSnapshotAt : $lastSnapshotAt->format('Y-m-d H:i:s'))
                    : null,
            ];
        }

        return $out;
    }
}
