<?php

namespace App\Exports;

use App\sample;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SampleExport implements FromCollection, WithHeadings
{
    /**
     * Retrieve the collection of samples to be exported.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
       
        $samples = sample::with('category')->select('id', 'code', 'name', 'prod_code', 'category_id')->get();

   
        $data = $samples->map(function ($sample) {
            return [
                'id' => $sample->id,
                'code' => $sample->code,
                'name' => $sample->name,
                'prod_code' => $sample->prod_code,
                'category_name' => $sample->category ? $sample->category->name : 'N/A',
            ];
        });

        return $data;
    }

    /**
     * Define the headings for the exported Excel file.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID', 'Code', 'Name', 'Product Code', 'Category Name'
        ];
    }
}
