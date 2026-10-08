<?php

namespace App\Exports;

use App\pricingTable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PricingBuyingCostExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $ids;

    public function __construct($ids = '')
    {
        $this->ids = $ids;
    }

    public function query()
    {
        $pricing_ids = array_values(array_filter(array_map('intval', explode(',', (string) $this->ids))));

        if (empty($pricing_ids)) {
            $pricing_ids = pricingTable::listRowIds();
        }

        return pricingTable::whereIn('id', $pricing_ids)
            ->select('id', 'product_id', 'productType', 'buyingCost')
            ->orderBy('id', 'asc');
    }

    public function headings(): array
    {
        return [
            'product_sku',
            'buying_cost',
        ];
    }

    public function map($pricingTable): array
    {
        if ($pricingTable->productType == 1) {
            $sku = $pricingTable->product->code ?? '';
        } else {
            $sku = $pricingTable->tempProduct->code ?? '';
        }

        return [
            $sku,
            $pricingTable->buyingCost,
        ];
    }
}
