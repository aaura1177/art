<?php

namespace App\Exports;

use App\fullfillmentlogsModel;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromCollection;


class FulfillmentlistLogs implements FromCollection, WithHeadings, WithMapping
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
            "Sku", "Customer Name", "Customer EmailID",  "Order id", "Order Date", "Quantity", "orderType", "Type",
        ];
    }

    public function map($fulFillmentList): array
    {
        return [
            $fulFillmentList->sku,
            $fulFillmentList->wp_customer_name,
            $fulFillmentList->wp_customer_email,
            $fulFillmentList->order_id,
            $fulFillmentList->created_at,
            number_format($fulFillmentList->qty),
            $fulFillmentList->order_type,
            $fulFillmentList->type,
        ];
    }
}
