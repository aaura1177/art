<?php

namespace App\Exports;

use App\packaging;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PackagingCartonsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return packaging::with('product')
            ->whereHas('product', function ($q) {
                $q->whereNotNull('code')->where('code', '!=', '');
            })
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'code',
            'product_name',
            'number_of_boxes',
            'box1_height',
            'box1_width',
            'box1_depth',
            'box1_type',
            'box1_sqinch',
            'box1_ply',
            'box2_height',
            'box2_width',
            'box2_depth',
            'box2_type',
            'box2_sqinch',
            'box2_ply',
            'box_1_qty',
            'box_2_qty',
        ];
    }

    /**
     * @param  packaging  $row
     */
    public function map($row): array
    {
        $p = $row->product;

        return [
            $p ? $p->code : '',
            $p ? $p->name : '',
            $row->no_of_boxes,
            $row->box1_height,
            $row->box1_width,
            $row->box1_depth,
            $row->box1_type,
            $row->box1_sqinch,
            $row->box1_ply,
            $row->box2_height,
            $row->box2_width,
            $row->box2_depth,
            $row->box2_type,
            $row->box2_sqinch,
            $row->box2_ply,
            $row->box_1_qty,
            $row->box_2_qty,
        ];
    }
}
