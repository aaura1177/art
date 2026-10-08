<?php

namespace App\Exports;

use App\fullfillmentModel;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromCollection;

class FulFillmentsListExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    use Exportable;

    public function __construct($data)
    {
        $this->data = $data;
    }
    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            "sku", "Customer Name", "Customer EmailID", "qty"
        ];
    }

    public function map($fulFillmentList): array
    {
        return [
            $fulFillmentList->sku,
            $fulFillmentList->wp_customer_name,
            $fulFillmentList->wp_customer_email,
            number_format($fulFillmentList->qty),
        ];
    }
}
