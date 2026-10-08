<?php

namespace App\Exports;

use App\consumable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Schema;

class ConsumablesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $hasSkuColumn = Schema::hasColumn('consumables', 'SKU');
        $hasDescription = Schema::hasColumn('consumables', 'description');
        $hasIsMoq = Schema::hasColumn('consumables', 'is_moq');
        $hasMoqQty = Schema::hasColumn('consumables', 'moq_qty');
        $hasMonthEndBuyer = Schema::hasColumn('consumables', 'monthEndpo_buyer');

        $data = consumable::with('supp', 'unitType')
            ->where('is_deleted', '0')
            ->select(
                'id',
                'name',
                'supplier',
                'rate',
                'payment_terms',
                'unit_type_id',
                'quantity',
                'gst',
                'monthEndpo_supplier'
            )
            ->when($hasSkuColumn, function ($q) {
                $q->addSelect('SKU');
            })
            ->when($hasDescription, function ($q) {
                $q->addSelect('description');
            })
            ->when($hasIsMoq, function ($q) {
                $q->addSelect('is_moq');
            })
            ->when($hasMoqQty, function ($q) {
                $q->addSelect('moq_qty');
            })
            ->when($hasMonthEndBuyer, function ($q) {
                $q->addSelect('monthEndpo_buyer');
            })
            ->get();

        return $data->map(function ($item) use ($hasSkuColumn, $hasDescription, $hasIsMoq, $hasMoqQty, $hasMonthEndBuyer) {
            $row = [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $hasDescription ? ($item->description ?? '') : '',
                'SKU' => $hasSkuColumn ? ($item->SKU ?? '') : '',
                'supplier' => $item->supplier
                    ? collect(json_decode($item->supplier, true))
                        ->map(function ($suppId) {
                            $supp = \App\supplier::find($suppId);

                            return $supp ? $supp->c_name : '';
                        })
                        ->filter()
                        ->implode(', ')
                    : '',
                'price' => $item->rate,
                'payment_terms' => $item->payment_terms,
                'unit_type_id' => $item->unitType ? $item->unitType->name : '',
                'quantity' => $item->quantity,
                'monthendpo_supplier' => $item->monthEndpo_supplier
                    ? optional(\App\supplier::find($item->monthEndpo_supplier))->c_name
                    : '',
            ];
            if ($hasMonthEndBuyer) {
                $labels = [1 => 'UK-18', 2 => 'Non UK-18', 3 => 'Common'];
                $raw = $item->getAttributes()['monthEndpo_buyer'] ?? $item->monthEndpo_buyer ?? null;
                $code = null;
                if (is_numeric($raw)) {
                    $code = (int) $raw;
                } elseif (is_string($raw) && $raw !== '') {
                    $dec = json_decode($raw, true);
                    if (is_array($dec) && $dec !== []) {
                        $code = (int) reset($dec);
                    }
                } elseif (is_int($raw)) {
                    $code = $raw;
                }
                $row['monthendpo_buyer'] = ($code !== null && in_array($code, [1, 2, 3], true))
                    ? ($labels[$code] ?? (string) $code)
                    : '';
            }
            $row['gst'] = $item->gst;
            $row['is_moq'] = $hasIsMoq ? (($item->is_moq ? 1 : 0)) : '';
            $row['moq_qty'] = $hasMoqQty ? ($item->moq_qty ?? '') : '';

            return $row;
        });
    }

    public function headings(): array
    {
        $headings = [
            'Id',
            'Name',
            'Description',
            'SKU',
            'Supplier',
            'Price',
            'Payment Terms',
            'Unit Type ID',
            'quantity',
            'MonthEndPO_Supplier',
        ];
        if (Schema::hasColumn('consumables', 'monthEndpo_buyer')) {
            $headings[] = 'MonthEndPO_Buyer';
        }
        $headings[] = 'gst';
        $headings[] = 'Is_Moq';
        $headings[] = 'Moq_Qty';

        return $headings;
    }
}
