<?php

namespace App\Imports;

use App\consumable;
use App\supplier;
use App\UnitType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;

class ConsumableImport implements ToCollection, WithHeadingRow
{
    use Importable;

    protected bool $skuOnly = false;
    protected bool $hasSkuColumn = false;
    protected $warnings = [];
    protected int $updated = 0;
    protected int $skipped = 0;
    protected int $fullyUpdated = 0;
    protected int $partiallyUpdated = 0;
    protected int $notUpdated = 0;
    protected array $reportRows = [];

    public function __construct(bool $skuOnly = false)
    {
        $this->skuOnly = $skuOnly;
        $this->hasSkuColumn = Schema::hasColumn('consumables', 'SKU');
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    public function getSummary(): array
    {
        return [
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'fully_updated' => $this->fullyUpdated,
            'partially_updated' => $this->partiallyUpdated,
            'not_updated' => $this->notUpdated,
        ];
    }

    public function getReportRows(): array
    {
        return $this->reportRows;
    }

    protected function parseGstValue($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace('%', '', $normalized);
        $normalized = trim($normalized);

        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = (int) $index + 2; // account for heading row
            $rawRow = $row->toArray();

            if (empty($row['name'])) {
                continue;
            }

            try {
                $itemName = $row['name'];

                // WithHeadingRow normalizes headings into snake_case keys (e.g. "SKU" -> "sku").
                $skuValue = trim((string) (
                    $row['sku'] ??
                    $row['SKU'] ??
                    $row['Sku'] ??
                    $row['sku_'] ??
                    ''
                ));

                // SKU-only mode: update only `consumables.SKU` and skip all other validations/stock logic.
                if ($this->skuOnly) {
                    if (!$this->hasSkuColumn) {
                        $this->warnings[] = "consumables.SKU column not found. Please add SKU column to import/export.";
                        break;
                    }

                    if ($skuValue === '') {
                        $this->warnings[] = "Row Item: {$itemName} → SKU is empty. Skipped.";
                        $this->skipped++;
                        continue;
                    }

                    $consumable = consumable::whereRaw(
                        'LOWER(TRIM(name)) = ?',
                        [strtolower(trim($itemName))]
                    )->first();

                    if (!$consumable) {
                        $this->warnings[] = "Row Item: {$itemName} → Consumable not found. Skipped.";
                        $this->skipped++;
                        continue;
                    }

                    $skuUpper = strtoupper($skuValue);
                    $duplicate = consumable::where('SKU', $skuUpper)
                        ->where('id', '!=', $consumable->id)
                        ->first();
                    if ($duplicate) {
                        $this->warnings[] = "Row Item: {$itemName} → Duplicate SKU '{$skuUpper}' already used by '{$duplicate->name}' (ID: {$duplicate->id}). Skipped.";
                        $this->skipped++;
                        continue;
                    }

                    $consumable->SKU = $skuUpper;
                    $consumable->save();

                    $this->updated++;
                    continue;
                }

                $csvId = $row['id'] ?? null;
                $report = [
                    'row_number' => $rowNumber,
                    'id' => $csvId,
                    'name' => $itemName,
                    'status' => 'not_updated',
                    'updated_fields' => [],
                    'failed_fields' => [],
                    'errors' => [],
                    'warnings' => [],
                    'raw_row' => $rawRow,
                ];

                if (!$csvId || !is_numeric($csvId)) {
                    $msg = "Row {$rowNumber}: Invalid or missing Id. Row not updated.";
                    $this->warnings[] = $msg;
                    $report['errors'][] = $msg;
                    $this->notUpdated++;
                    $this->skipped++;
                    $this->reportRows[] = $report;
                    continue;
                }

                $consumable = consumable::find((int) $csvId);
                if (!$consumable) {
                    $msg = "Row {$rowNumber}: Consumable not found for Id {$csvId}. Row not updated.";
                    $this->warnings[] = $msg;
                    $report['errors'][] = $msg;
                    $this->notUpdated++;
                    $this->skipped++;
                    $this->reportRows[] = $report;
                    continue;
                }

                $data = [];

                // ------------------------- NAME -------------------------
                if (trim((string) $itemName) !== '') {
                    $data['name'] = trim((string) $itemName);
                    $report['updated_fields'][] = 'name';
                } else {
                    $report['failed_fields'][] = 'name: empty value';
                }

                // ------------------------ PRICE -------------------------
                if (isset($row['price']) && $row['price'] !== '' && is_numeric($row['price'])) {
                    $data['rate'] = (float) $row['price'];
                    $report['updated_fields'][] = 'rate';
                } elseif (isset($row['price']) && $row['price'] !== '') {
                    $report['failed_fields'][] = 'rate: invalid numeric value';
                }

                // ------------------ PAYMENT TERMS -----------------------
                if (array_key_exists('payment_terms', $rawRow)) {
                    $data['payment_terms'] = $row['payment_terms'] !== '' ? $row['payment_terms'] : null;
                    $report['updated_fields'][] = 'payment_terms';
                }

                // ---------------------- QUANTITY ------------------------
                if (isset($row['quantity']) && $row['quantity'] !== '' && is_numeric($row['quantity'])) {
                    $data['quantity'] = (float) $row['quantity'];
                    $report['updated_fields'][] = 'quantity';
                } elseif (isset($row['quantity']) && $row['quantity'] !== '') {
                    $report['failed_fields'][] = 'quantity: invalid numeric value';
                }

                // ------------------------- GST --------------------------
                if (array_key_exists('gst', $rawRow)) {
                    $parsedGst = $this->parseGstValue($row['gst']);
                    if ($parsedGst !== null) {
                        $data['gst'] = $parsedGst;
                        $report['updated_fields'][] = 'gst';
                    } else {
                        $report['failed_fields'][] = 'gst: invalid value';
                    }
                }

                // --------------------- SUPPLIER -------------------------
                $supplierRaw = trim((string) ($row['supplier'] ?? ''));
                if ($supplierRaw !== '') {
                    $supplierNames = array_filter(array_map('trim', explode(',', $supplierRaw)));
                    $supplierIds = [];
                    $unmatchedSuppliers = [];

                    foreach ($supplierNames as $supplierName) {
                        $supplierModel = supplier::where('c_name', $supplierName)->first();
                        if ($supplierModel) {
                            $supplierIds[] = $supplierModel->id;
                        } else {
                            $unmatchedSuppliers[] = $supplierName;
                        }
                    }

                    if (!empty($supplierIds)) {
                        $data['supplier'] = json_encode(array_values(array_unique($supplierIds)));
                        $report['updated_fields'][] = 'supplier';
                    } else {
                        $report['failed_fields'][] = 'supplier: no supplier names matched';
                    }

                    if (!empty($unmatchedSuppliers)) {
                        $msg = "supplier unmatched: " . implode(', ', $unmatchedSuppliers);
                        $report['failed_fields'][] = $msg;
                        $this->warnings[] = "Row {$rowNumber} (ID {$csvId}) → {$msg}";
                    }
                }

                // --------------- MONTH END PO SUPPLIER ------------------
                if (array_key_exists('monthendpo_supplier', $rawRow)) {
                    $monthEndRaw = trim((string) ($row['monthendpo_supplier'] ?? ''));
                    if ($monthEndRaw === '') {
                        $data['monthEndpo_supplier'] = null;
                        $report['updated_fields'][] = 'monthEndpo_supplier';
                    } else {
                        $monthEndSupplier = supplier::where('c_name', $monthEndRaw)->first();
                        if ($monthEndSupplier) {
                            $data['monthEndpo_supplier'] = $monthEndSupplier->id;
                            $report['updated_fields'][] = 'monthEndpo_supplier';
                        } else {
                            $msg = "monthEndpo_supplier unmatched: {$monthEndRaw}";
                            $report['failed_fields'][] = $msg;
                            $this->warnings[] = "Row {$rowNumber} (ID {$csvId}) → {$msg}";
                        }
                    }
                }

                // ---------------------- UNIT TYPE -----------------------
                if (array_key_exists('unit_type_id', $rawRow)) {
                    $unitTypeName = trim((string) ($row['unit_type_id'] ?? ''));
                    if ($unitTypeName !== '') {
                        $unitType = UnitType::whereRaw('BINARY `name` = ?', [$unitTypeName])->first();
                        if (!$unitType) {
                            $unitType = UnitType::create([
                                'name' => $unitTypeName,
                            ]);
                        }
                        $data['unit_type_id'] = $unitType->id;
                        $report['updated_fields'][] = 'unit_type_id';
                    }
                }

                // Optional SKU update (only if DB column exists).
                if ($this->hasSkuColumn) {
                    if ($skuValue !== '') {
                        $skuUpper = strtoupper($skuValue);
                        $duplicate = consumable::where('SKU', $skuUpper)
                            ->when($consumable, function ($q) use ($consumable) {
                                return $q->where('id', '!=', $consumable->id);
                            })
                            ->first();

                        if ($duplicate) {
                            $msg = "Duplicate SKU '{$skuUpper}' already used by '{$duplicate->name}' (ID: {$duplicate->id}). SKU not updated.";
                            $this->warnings[] = "Row {$rowNumber} (ID {$csvId}) → {$msg}";
                            $report['failed_fields'][] = "SKU: {$msg}";
                        } else {
                            $data['SKU'] = $skuUpper;
                            $report['updated_fields'][] = 'SKU';
                        }
                    }
                }

                // ----------------------- UPDATE -------------------------
                if (!empty($data)) {
                    $consumable->update($data);
                }

                if (count($report['updated_fields']) > 0 && count($report['failed_fields']) === 0) {
                    $report['status'] = 'fully_updated';
                    $this->fullyUpdated++;
                    $this->updated++;
                } elseif (count($report['updated_fields']) > 0) {
                    $report['status'] = 'partially_updated';
                    $this->partiallyUpdated++;
                    $this->updated++;
                } else {
                    $report['status'] = 'not_updated';
                    $this->notUpdated++;
                    $this->skipped++;
                }

                $this->reportRows[] = $report;

            } catch (\Exception $e) {
                $msg = "Error importing row {$rowNumber}: {$e->getMessage()}";
                $this->warnings[] = $msg;
                $this->skipped++;
                $this->notUpdated++;
                $this->reportRows[] = [
                    'row_number' => $rowNumber,
                    'id' => $row['id'] ?? null,
                    'name' => $row['name'] ?? null,
                    'status' => 'not_updated',
                    'updated_fields' => [],
                    'failed_fields' => [],
                    'errors' => [$msg],
                    'warnings' => [],
                    'raw_row' => $rawRow,
                ];
            }
        }
    }
}
