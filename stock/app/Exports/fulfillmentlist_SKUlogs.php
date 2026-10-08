<?php

namespace App\Exports;

use App\fullfillmentlogsModel;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromCollection;

class fulfillmentlist_SKUlogs implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    use Exportable;

    public function __construct($sku)
    {
        $this->sku = $sku;
    }
    public function collection()
    {
        $country = \Request::session()->get('country');
        $fullfillmentlist = fullfillmentlogsModel::join('wp_customers_infos as w', 'w.id', '=', 'sku_fulfillment_logs.wp_customers_info_id')
            ->where(['sku_fulfillment_logs.site_access' => $country, "sku_fulfillment_logs.order_type" => "fulfillment", "sku_fulfillment_logs.sku" => $this->sku])->get();
        return $fullfillmentlist;
    }

    public function headings(): array
    {
        return [
            "Sku", "Customer Name", "Customer EmailID",  "Order id", "Order Date", "Quantity", "orderType", "Type",
        ];
    }

    public function map($fullfillmentlist): array
    {
        return [
            $fullfillmentlist->sku,
            $fullfillmentlist->wp_customer_name,
            $fullfillmentlist->wp_customer_email,
            $fullfillmentlist->order_id,
            $fullfillmentlist->created_at,
            number_format($fullfillmentlist->qty),
            $fullfillmentlist->order_type,
            $fullfillmentlist->type,
        ];
    }
}
