<?php

namespace App\Support;

/**
 * Plain-language formatting for courier history screens.
 */
final class CourierSnapshotPresenter
{
    public static function friendlyWhen($datetime): string
    {
        if (! $datetime) {
            return 'Not changed yet';
        }
        try {
            $dt = $datetime instanceof \DateTimeInterface
                ? \Carbon\Carbon::instance($datetime)
                : \Carbon\Carbon::parse($datetime);

            return $dt->format('j M Y') . ' at ' . $dt->format('g:i A');
        } catch (\Throwable $e) {
            return (string) $datetime;
        }
    }

    public static function eventLabel(string $eventType): string
    {
        return match ($eventType) {
            'create' => 'Courier was added',
            'update' => 'Courier was updated',
            'delete' => 'Courier was removed',
            default => ucfirst($eventType),
        };
    }

    /**
     * Turn a stored value into short readable text for lists.
     */
    public static function shortValue($value, string $key = ''): string
    {
        if ($value === null || $value === '') {
            return 'None';
        }
        if ($key === 'is_default') {
            return ((int) $value === 1) ? 'Yes (default for this country)' : 'No';
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value)) {
            if ($key === 'weight_rate_tiers') {
                $n = count($value);

                return $n === 0 ? 'No bands set' : ($n === 1 ? '1 weight band' : "{$n} weight bands");
            }
            if ($key === 'surcharge_rules') {
                $n = count($value);

                return $n === 0 ? 'No extra charge rules' : ($n === 1 ? '1 rule group' : "{$n} rule groups");
            }
            if ($key === 'custom_condition') {
                $n = is_array($value) ? count($value) : 0;

                return $n === 0 ? 'None' : ($n === 1 ? '1 old-style condition' : "{$n} old-style conditions");
            }

            return count($value) . ' items';
        }

        return (string) $value;
    }

    /**
     * HTML-safe multi-line explanation of a value (for before/after detail).
     */
    public static function detailHtml($value, string $key = ''): string
    {
        if ($value === null || $value === '') {
            return '<span class="text-muted">None</span>';
        }

        if ($key === 'is_default') {
            return e(self::shortValue($value, $key));
        }

        if ($key === 'weight_rate_tiers' && is_array($value)) {
            if ($value === []) {
                return '<span class="text-muted">No weight bands</span>';
            }
            $rows = '';
            foreach ($value as $i => $band) {
                $from = $band['weight_from'] ?? '?';
                $to = $band['weight_to'] ?? null;
                $rate = $band['rate_per_unit'] ?? '?';
                $till = $to === null || $to === '' ? 'and above' : 'up to '.$to;
                $n = $i + 1;
                $rows .= '<li><strong>Band '.$n.':</strong> Over '.$from.' '.$till.' → charge <strong>'.$rate.'</strong> per unit</li>';
            }

            return '<ul class="mb-0 ps-3">'.$rows.'</ul>';
        }

        if ($key === 'surcharge_rules' && is_array($value)) {
            if ($value === []) {
                return '<span class="text-muted">No extra charge rules</span>';
            }
            $html = '';
            foreach ($value as $bi => $block) {
                $name = e($block['block_name'] ?? ('Rule group '.($bi + 1)));
                $op = strtoupper((string) ($block['condition_operator'] ?? 'OR'));
                $opText = $op === 'AND' ? 'all conditions must match' : 'first matching condition applies';
                $html .= '<div class="mb-2"><strong>'.$name.'</strong> <span class="text-muted">('.$opText.')</span>';
                $conds = $block['conditions'] ?? [];
                if (is_array($conds) && $conds !== []) {
                    $html .= '<ul class="mb-0 ps-3">';
                    foreach ($conds as $cond) {
                        $attr = e($cond['attribute_key'] ?? 'measure');
                        $unit = e($cond['unit'] ?? '');
                        $tiers = $cond['tiers'] ?? [];
                        $tierBits = [];
                        if (is_array($tiers)) {
                            foreach ($tiers as $t) {
                                $opT = e($t['operator'] ?? '>');
                                $min = $t['value_min'] ?? '';
                                $max = $t['value_max'] ?? '';
                                $sur = $t['surcharge'] ?? 0;
                                if (($t['operator'] ?? '') === 'between') {
                                    $tierBits[] = "between {$min} and {$max} → extra {$sur}";
                                } else {
                                    $tierBits[] = "{$opT} {$min} → extra {$sur}";
                                }
                            }
                        }
                        $html .= '<li>Check <strong>'.$attr.'</strong>'.($unit ? ' ('.$unit.')' : '').': '.e(implode('; ', $tierBits)).'</li>';
                    }
                    $html .= '</ul>';
                }
                $html .= '</div>';
            }

            return $html;
        }

        if ($key === 'custom_condition' && is_array($value)) {
            if ($value === []) {
                return '<span class="text-muted">None</span>';
            }
            $html = '<ul class="mb-0 ps-3">';
            foreach ($value as $i => $cond) {
                if (! is_array($cond)) {
                    continue;
                }
                $attr = e($cond['attribute'] ?? ($cond[0] ?? 'condition'));
                $val = $cond['attribute_val'] ?? ($cond[1] ?? '');
                $price = $cond['popup_price'] ?? ($cond[2] ?? '');
                if (is_array($val)) {
                    $val = implode(', ', $val);
                }
                if (is_array($price)) {
                    $price = implode(', ', $price);
                }
                $html .= '<li>If <strong>'.$attr.'</strong> over '.e((string) $val).' → add '.e((string) $price).'</li>';
            }
            $html .= '</ul>';

            return $html;
        }

        if (is_array($value) || is_object($value)) {
            return '<pre class="mb-0 small bg-light p-2 rounded">'.e(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)).'</pre>';
        }

        return e((string) $value);
    }
}
