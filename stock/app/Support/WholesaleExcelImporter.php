<?php

namespace App\Support;

use App\product;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class WholesaleExcelImporter
{
    /**
     * Parse Excel/CSV into merged SKU => qty map.
     * Expected columns: SKU, Qty (header row required).
     *
     * @return array{rows: array<int, array{sku: string, qty: int}>, errors: string[]}
     */
    public static function parse(UploadedFile $file): array
    {
        $errors = [];
        $merged = [];

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            return [
                'rows' => [],
                'errors' => ['We could not read the Excel file. Please upload a valid .xlsx / .xls / .csv file.'],
            ];
        }

        if (count($rows) < 2) {
            return [
                'rows' => [],
                'errors' => ['The file is empty. Please add a header row (SKU, Qty) and at least one product line.'],
            ];
        }

        $header = array_map(function ($h) {
            return strtolower(trim((string) $h));
        }, $rows[0]);

        $skuIdx = self::findColumn($header, ['sku', 'product sku', 'product_sku', 'code', 'item code', 'item_code']);
        $qtyIdx = self::findColumn($header, ['qty', 'quantity', 'qty.', 'asked qty', 'asked_qty', 'order qty']);

        if ($skuIdx === null || $qtyIdx === null) {
            return [
                'rows' => [],
                'errors' => ['The file must have columns named SKU and Qty (or Quantity). Please download the sample template and try again.'],
            ];
        }

        $unknownSkus = [];
        $zeroQtyRows = [];
        $lineNo = 1;

        for ($i = 1; $i < count($rows); $i++) {
            $lineNo = $i + 1;
            $row = $rows[$i];
            $sku = trim((string) ($row[$skuIdx] ?? ''));
            $qtyRaw = $row[$qtyIdx] ?? null;

            if ($sku === '' && ($qtyRaw === null || $qtyRaw === '')) {
                continue;
            }

            if ($sku === '') {
                $errors[] = "Row {$lineNo}: SKU is missing.";
                continue;
            }

            if (! is_numeric($qtyRaw)) {
                $errors[] = "Row {$lineNo}: Quantity for SKU {$sku} must be a number.";
                continue;
            }

            $qty = (int) $qtyRaw;
            if ($qty <= 0) {
                $zeroQtyRows[] = "Row {$lineNo}: SKU {$sku} has quantity {$qty} (must be greater than 0).";
                continue;
            }

            $skuKey = strtoupper($sku);
            if (! isset($merged[$skuKey])) {
                $merged[$skuKey] = ['sku' => $skuKey, 'qty' => 0];
            }
            $merged[$skuKey]['qty'] += $qty;
        }

        if (! empty($zeroQtyRows)) {
            $errors = array_merge($errors, $zeroQtyRows);
        }

        if (empty($merged) && empty($errors)) {
            $errors[] = 'No valid product lines found in the file.';
        }

        // Resolve products
        $out = [];
        foreach ($merged as $skuKey => $data) {
            $product = product::whereRaw('UPPER(TRIM(code)) = ?', [$skuKey])->first();
            if (! $product) {
                $unknownSkus[] = $skuKey;
                continue;
            }
            $out[] = [
                'sku' => $skuKey,
                'product_id' => (int) $product->id,
                'qty' => (int) $data['qty'],
            ];
        }

        if (! empty($unknownSkus)) {
            $list = implode(', ', array_slice($unknownSkus, 0, 20));
            $more = count($unknownSkus) > 20 ? ' and ' . (count($unknownSkus) - 20) . ' more' : '';
            $errors[] = 'These SKUs were not found in the product master: ' . $list . $more . '. Please correct the file and upload again.';
        }

        if (! empty($errors)) {
            return ['rows' => [], 'errors' => $errors];
        }

        return ['rows' => $out, 'errors' => []];
    }

    protected static function findColumn(array $header, array $aliases): ?int
    {
        foreach ($header as $i => $name) {
            $name = preg_replace('/\s+/', ' ', $name);
            if (in_array($name, $aliases, true)) {
                return (int) $i;
            }
        }

        return null;
    }
}
