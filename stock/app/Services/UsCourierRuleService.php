<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UsCourierRuleService
{
    /** Normalized (uppercase) courier.country values that use the block engine. */
    public const BLOCK_ENGINE_COUNTRIES = ['US', 'UK', 'EU', 'CANADA', 'AUSTRALIA'];

    /** US & Canada: inches + lbs in rules / pricing metrics. */
    public const US_BLOCK_UNIT_COUNTRIES = ['US', 'CANADA'];

    public static function normalizeCountry(string $country): string
    {
        return strtoupper(trim($country));
    }

    public static function supportsBlockEngine(string $country): bool
    {
        return in_array(self::normalizeCountry($country), self::BLOCK_ENGINE_COUNTRIES, true);
    }

    public static function usesUsBlockUnits(string $country): bool
    {
        return in_array(self::normalizeCountry($country), self::US_BLOCK_UNIT_COUNTRIES, true);
    }

    public static function engineIdForCountry(string $country): string
    {
        return self::usesUsBlockUnits($country) ? 'us_blocks_v1' : 'uk_blocks_v1';
    }

    /**
     * After create/update: save JSON for this courier country, drop orphan block rows for other engine countries.
     */
    public static function syncBlocksFromRequest(int $courierId, string $rawCountry, ?string $usRuleBlocksJson, ?string $ukRuleBlocksJson): void
    {
        if (! self::tablesExist()) {
            return;
        }

        $cU = self::normalizeCountry($rawCountry);
        if (self::supportsBlockEngine($cU)) {
            $json = self::usesUsBlockUnits($cU) ? $usRuleBlocksJson : $ukRuleBlocksJson;
            self::replaceRulesFromJson($courierId, $json, $cU);
            foreach (self::BLOCK_ENGINE_COUNTRIES as $other) {
                if ($other !== $cU) {
                    self::deleteRulesForCourierCountry($courierId, $other);
                }
            }
        } else {
            self::deleteAllRulesForCourier($courierId);
        }
    }

    public static function tablesExist(): bool
    {
        return Schema::hasTable('courier_rule_blocks')
            && Schema::hasTable('courier_rule_conditions')
            && Schema::hasTable('courier_rule_tiers');
    }

    public static function buildPayload(int $courierId, string $country): ?array
    {
        if (! self::tablesExist()) {
            return null;
        }

        $c = self::normalizeCountry($country);
        if (! self::supportsBlockEngine($c)) {
            return null;
        }

        $blocks = DB::table('courier_rule_blocks')
            ->where('courier_id', $courierId)
            ->where('country', $c)
            ->where('is_active', 1)
            ->orderBy('block_priority')
            ->get();

        if ($blocks->isEmpty()) {
            return null;
        }

        $payload = [
            'engine' => self::engineIdForCountry($c),
            'country' => $c,
            'combine_mode' => 'sum_matched_blocks',
            'tier_selection' => 'highest_threshold',
            'blocks' => [],
        ];

        $countryCode = $c;
        foreach ($blocks as $b) {
            $conditions = DB::table('courier_rule_conditions')
                ->where('block_id', $b->id)
                ->where('is_active', 1)
                ->orderBy('condition_priority')
                ->get();

            $condList = [];
            foreach ($conditions as $cond) {
                $tiers = DB::table('courier_rule_tiers')
                    ->where('condition_id', $cond->id)
                    ->where('is_active', 1)
                    ->get();

                $tierList = [];
                foreach ($tiers as $t) {
                    $tierList[] = [
                        'operator' => $t->operator,
                        'value_min' => $t->value_min !== null ? (float) $t->value_min : null,
                        'value_max' => $t->value_max !== null ? (float) $t->value_max : null,
                        'surcharge' => (float) $t->surcharge,
                    ];
                }

                $defaultUnit = self::usesUsBlockUnits($countryCode) ? 'in' : 'cm';
                $condList[] = [
                    'condition_priority' => (int) $cond->condition_priority,
                    'attribute_key' => $cond->attribute_key,
                    'unit' => $cond->unit ?? $defaultUnit,
                    'tiers' => $tierList,
                ];
            }

            $payload['blocks'][] = [
                'block_name' => $b->block_name,
                'block_priority' => (int) $b->block_priority,
                'condition_operator' => $b->condition_operator ?? 'OR',
                'conditions' => $condList,
            ];
        }

        return $payload;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function loadBlocksArray(int $courierId, string $country): array
    {
        if (! self::tablesExist()) {
            return [];
        }

        $countryCode = self::normalizeCountry($country);
        if (! self::supportsBlockEngine($countryCode)) {
            return [];
        }

        $blocks = DB::table('courier_rule_blocks')
            ->where('courier_id', $courierId)
            ->where('country', $countryCode)
            ->where('is_active', 1)
            ->orderBy('block_priority')
            ->get();

        $out = [];
        $defaultUnit = self::usesUsBlockUnits($countryCode) ? 'in' : 'cm';
        foreach ($blocks as $b) {
            $conditions = DB::table('courier_rule_conditions')
                ->where('block_id', $b->id)
                ->where('is_active', 1)
                ->orderBy('condition_priority')
                ->get();

            $conds = [];
            foreach ($conditions as $cond) {
                $tiers = DB::table('courier_rule_tiers')
                    ->where('condition_id', $cond->id)
                    ->where('is_active', 1)
                    ->get();

                $tArr = [];
                foreach ($tiers as $t) {
                    $tArr[] = [
                        'operator' => $t->operator,
                        'value_min' => $t->value_min,
                        'value_max' => $t->value_max,
                        'surcharge' => $t->surcharge,
                    ];
                }

                $conds[] = [
                    'condition_priority' => (int) $cond->condition_priority,
                    'attribute_key' => $cond->attribute_key,
                    'unit' => $cond->unit ?? $defaultUnit,
                    'tiers' => $tArr,
                ];
            }

            $out[] = [
                'block_name' => $b->block_name,
                'block_priority' => (int) $b->block_priority,
                'condition_operator' => $b->condition_operator ?? 'OR',
                'conditions' => $conds,
            ];
        }

        return $out;
    }

    public static function deleteRulesForCourierCountry(int $courierId, string $country): void
    {
        if (! self::tablesExist()) {
            return;
        }

        $countryCode = self::normalizeCountry($country);

        $blockIds = DB::table('courier_rule_blocks')
            ->where('courier_id', $courierId)
            ->where('country', $countryCode)
            ->pluck('id');

        foreach ($blockIds as $bid) {
            $condIds = DB::table('courier_rule_conditions')->where('block_id', $bid)->pluck('id');
            foreach ($condIds as $cid) {
                DB::table('courier_rule_tiers')->where('condition_id', $cid)->delete();
            }
            DB::table('courier_rule_conditions')->where('block_id', $bid)->delete();
        }
        DB::table('courier_rule_blocks')->where('courier_id', $courierId)->where('country', $countryCode)->delete();
    }

    public static function deleteAllRulesForCourier(int $courierId): void
    {
        if (! self::tablesExist()) {
            return;
        }

        $blockIds = DB::table('courier_rule_blocks')
            ->where('courier_id', $courierId)
            ->pluck('id');

        foreach ($blockIds as $bid) {
            $condIds = DB::table('courier_rule_conditions')->where('block_id', $bid)->pluck('id');
            foreach ($condIds as $cid) {
                DB::table('courier_rule_tiers')->where('condition_id', $cid)->delete();
            }
            DB::table('courier_rule_conditions')->where('block_id', $bid)->delete();
        }
        DB::table('courier_rule_blocks')->where('courier_id', $courierId)->delete();
    }

    /** @deprecated Use deleteRulesForCourierCountry($id, 'US') or deleteAllRulesForCourier */
    public static function deleteUsRulesForCourier(int $courierId): void
    {
        self::deleteRulesForCourierCountry($courierId, 'US');
    }

    public static function replaceRulesFromJson(int $courierId, ?string $json, string $country): void
    {
        if (! self::tablesExist() || $json === null || trim($json) === '') {
            return;
        }

        $countryCode = self::normalizeCountry($country);
        if (! self::supportsBlockEngine($countryCode)) {
            return;
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return;
        }

        DB::transaction(function () use ($courierId, $decoded, $countryCode) {
            self::deleteRulesForCourierCountry($courierId, $countryCode);

            foreach ($decoded as $block) {
                if (! is_array($block)) {
                    continue;
                }

                $op = strtoupper((string) ($block['condition_operator'] ?? 'OR'));
                if ($op !== 'AND' && $op !== 'OR') {
                    $op = 'OR';
                }

                $bid = DB::table('courier_rule_blocks')->insertGetId([
                    'courier_id' => $courierId,
                    'country' => $countryCode,
                    'block_name' => $block['block_name'] ?? null,
                    'block_priority' => (int) ($block['block_priority'] ?? 100),
                    'condition_operator' => $op,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($block['conditions'] ?? [] as $cond) {
                    if (! is_array($cond)) {
                        continue;
                    }

                    $cid = DB::table('courier_rule_conditions')->insertGetId([
                        'block_id' => $bid,
                        'condition_priority' => (int) ($cond['condition_priority'] ?? 100),
                        'attribute_key' => (string) ($cond['attribute_key'] ?? ''),
                        'unit' => (string) ($cond['unit'] ?? (self::usesUsBlockUnits($countryCode) ? 'in' : 'cm')),
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    foreach ($cond['tiers'] ?? [] as $tier) {
                        if (! is_array($tier)) {
                            continue;
                        }

                        DB::table('courier_rule_tiers')->insert([
                            'block_id' => $bid,
                            'condition_id' => $cid,
                            'tier_priority' => 0,
                            'operator' => (string) ($tier['operator'] ?? '>'),
                            'value_min' => self::normalizeDecimal($tier['value_min'] ?? null),
                            'value_max' => self::normalizeDecimal($tier['value_max'] ?? null),
                            'surcharge' => (float) ($tier['surcharge'] ?? 0),
                            'is_active' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });
    }

    private static function normalizeDecimal($v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }

        return is_numeric($v) ? (float) $v : null;
    }
}
