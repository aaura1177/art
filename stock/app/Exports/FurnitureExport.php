<?php

namespace App\Exports;

use App\product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class FurnitureExport implements FromCollection, WithHeadings ,WithStyles
{
    /**
     * Retrieve the collection of products to be exported.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $products = product::with('category')->select('id', 'code', 'name','gstslab','category_id')->get();

        $exportData = $products->map(function ($product) {
            return [
                'ID' => $product->id,
                'Code' => $product->code,
                'Name' => $product->name,
                'gstslab' => $product->gstslab,
                'Category Name' => $product->category ? $product->category->name : 'N/A',
                'Purchase Ledger' => $product->purchase_ledger ? $product->purchase_ledger : 'N/A',
            ];
        });

        return $exportData;
    }

    /**
     * Define the headings for the exported Excel file.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID', 'Code', 'Name','gstslab', 'Category Name' ,'Purchase Ledger'
        ];
    }



    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 13], 
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4CAF50']], 
            ],
            
        ];
    }
}
