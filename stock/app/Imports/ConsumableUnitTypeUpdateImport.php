<?php

namespace App\Imports;

use App\consumable;
use App\supplier;
use App\UnitType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;

class ConsumableUnitTypeUpdateImport implements ToCollection, WithHeadingRow
{
    use Importable;

    protected $updated = 0;
    protected $skipped = 0;
    protected $warnings = [];

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get consumable's supplier id(s) as array (handles both JSON array and old single-id format).
     */
    private function getConsumableSupplierIds($consumable): array
    {
        $raw = $consumable->supplier;
        if ($raw === null || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_map('intval', array_filter($decoded));
        }
        // Old format: single integer (stored as string "5" or number)
        if (is_numeric($raw)) {
            return [(int) $raw];
        }
        if (is_numeric($decoded)) {
            return [(int) $decoded];
        }
        return [];
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            // CSV may have "Unit Type ID " or "unit_type_id" etc.
            $unitTypeValue = trim((string) (
                $row['unit_type_id'] ??
                $row['unit_type_id_'] ??
                $row['unit_type_id '] ??
                ''
            ));

            if ($unitTypeValue === '') {
                $this->warnings[] = "Row '{$name}': Unit Type is empty. Skipped.";
                $this->skipped++;
                continue;
            }

            // Find or create UnitType in unit_type table (create if not exists)
            $unitType = UnitType::firstOrCreate(
                ['name' => $unitTypeValue],
                ['name' => $unitTypeValue]
            );

            // Supplier from Excel: match by name + supplier when supplier column has value
            $supplierName = trim((string) (
                $row['supplier'] ??
                $row['Supplier'] ??
                ''
            ));
            $supplierIds = [];
            if ($supplierName !== '') {
                $supplierNames = array_map('trim', explode(',', $supplierName));
                $supplierIds = supplier::whereIn('c_name', $supplierNames)->pluck('id')->toArray();
                if (empty($supplierIds)) {
                    $this->warnings[] = "Row '{$name}': Supplier '{$supplierName}' not found in suppliers table. Skipped.";
                    $this->skipped++;
                    continue;
                }
            }

            // Base: match by name
            $query = consumable::where('is_deleted', '0')
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)]);

            // When Excel has supplier, also match by supplier_id (consumable.supplier = single id or JSON array)
            if (!empty($supplierIds)) {
                $query->where(function ($q) use ($supplierIds) {
                    foreach ($supplierIds as $sid) {
                        $q->orWhere(function ($q2) use ($sid) {
                            $q2->where('supplier', $sid)  // old format: single id
                                ->orWhereRaw("JSON_CONTAINS(supplier, ?, '$')", [json_encode($sid)]);  // new format: JSON array
                        });
                    }
                });
            }

            $consumable = $query->first();

            if (!$consumable) {
                $msg = !empty($supplierIds)
                    ? "Row '{$name}': No consumable with name + supplier '{$supplierName}'. Skipped."
                    : "Row '{$name}': Consumable not found. Skipped.";
                $this->warnings[] = $msg;
                $this->skipped++;
                continue;
            }

            $consumable->unit_type_id = $unitType->id;
            $consumable->save();
            $this->updated++;
        }
    }
}
