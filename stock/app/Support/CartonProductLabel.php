<?php

namespace App\Support;

final class CartonProductLabel
{
    public static function format(
        ?string $code,
        ?string $name,
        $box1Height = null,
        $box1Width = null,
        $box1Depth = null,
        ?int $productId = null,
        $box2Height = null,
        $box2Width = null,
        $box2Depth = null
    ): string {
        $code = trim((string) ($code ?? ''));
        $name = trim((string) ($name ?? ''));
        if ($name === '' && $productId) {
            $name = 'Product #' . $productId;
        }

        $lines = [$code !== '' ? "{$code} - {$name}" : $name];

        $box1Line = self::sizeLine('Box 1', $box1Height, $box1Width, $box1Depth);
        if ($box1Line !== null) {
            $lines[] = $box1Line;
        }

        $box2Line = self::sizeLine('Box 2', $box2Height, $box2Width, $box2Depth);
        if ($box2Line !== null) {
            $lines[] = $box2Line;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  object|null  $row  popTable row (box dimensions + optional product relation)
     * @param  object|null  $product  optional product when not eager-loaded on $row
     */
    public static function fromPopTable($row, $product = null): string
    {
        if (! $row) {
            return '';
        }

        $product = $product ?? ($row->product ?? null);
        $productId = (int) ($row->product_id ?? ($product->id ?? 0));

        return self::format(
            $product->code ?? null,
            $product->name ?? null,
            $row->box1_height ?? null,
            $row->box1_width ?? null,
            $row->box1_depth ?? null,
            $productId ?: null,
            $row->box2_height ?? null,
            $row->box2_width ?? null,
            $row->box2_depth ?? null
        );
    }

    private static function sizeLine(string $prefix, $height, $width, $depth): ?string
    {
        $h = trim((string) ($height ?? ''));
        $w = trim((string) ($width ?? ''));
        $d = trim((string) ($depth ?? ''));
        if ($h === '' && $w === '' && $d === '') {
            return null;
        }

        return $prefix . ': ' . $h . ' X ' . $w . ' X ' . $d;
    }
}
