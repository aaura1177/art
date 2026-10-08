<?php

namespace App\Exports;

use App\smallhardwares;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SmallHardwareExport implements FromCollection, WithHeadings, WithMapping
{
    protected bool $hasSkuColumn;

    public function __construct()
    {
        $this->hasSkuColumn = Schema::hasColumn('consumables', 'SKU');
    }

    public function collection()
    {
        return smallhardwares::with('smallhardwareSupplier')->get();
    }

    /**
     * Same column layout as ConsumablesExport so the file can be imported via Consumable Inventory.
     * SKU / Unit Type / payment terms / gst are left blank where small hardware has no data — fill before import.
     */
    public function map($item): array
    {
        $supplierName = $item->smallhardwareSupplier ? $item->smallhardwareSupplier->c_name : '';

        $row = [
            '',
            $item->name ?? '',
        ];

        if ($this->hasSkuColumn) {
            $row[] = '';
        }

        return array_merge($row, [
            $supplierName,
            $item->rate ?? 0,
            '',
            '',
            0,
            $supplierName,
            0,
        ]);
    }

    public function headings(): array
    {
        $headings = [
            'Id',
            'Name',
        ];

        if ($this->hasSkuColumn) {
            $headings[] = 'SKU';
        }

        return array_merge($headings, [
            'Supplier',
            'Price',
            'Payment Terms',
            'Unit Type ID',
            'quantity',
            'MonthEndPO_Supplier',
            'gst',
        ]);
    }
}
