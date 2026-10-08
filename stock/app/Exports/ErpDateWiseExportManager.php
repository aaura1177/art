<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use App\ErpHistoryManager;
use App\ErpSheetManager;
use App\ErpProduct;

class ErpDateWiseExportManager implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $fsd;
    protected $location;

    public function __construct($fsd)
    {
        $this->fsd = $fsd;
        $this->location = \Request::session()->get('country'); // Store location
    }

    public function query()
    {
        $fsd1 = strtotime($this->fsd);
        $newfsd = date('Y-m-d', $fsd1);

        return ErpHistoryManager::select('sku', 'stock')
            ->whereDate('date', '<=', $newfsd)
            ->whereIn('id', function($query) {
                $query->selectRaw('MAX(id)')
                      ->from('erp_history')
                      ->groupBy('sku');
            })
            ->where('site_access', $this->location)
            ->orderBy('created_at', 'desc');
    }

    public function headings(): array 
    {
        $fsd1 = strtotime($this->fsd);
        $newfsd = date('Y-m-d', $fsd1);

        $ErpSheetAdd = ErpSheetManager::whereDate('date', '<=', $newfsd)
            ->where('type', 'sheet')
            ->where('site_access', $this->location)
            ->orderby('id', 'DESC')->first();

        $csvName = $ErpSheetAdd->name ?? 'N/A'; // Avoid null error

        $h = 'Stock as on ' . date('d-m-Y', strtotime($newfsd)) . ' (upto csv ' . $csvName . ')';

        return ["SKU", $h];
    }

    public function title(): string
    {
        return 'Erp Table Products';
    }

    public function map($product): array
    {
        return [
            $product->sku,
            $product->stock
        ];
    }
}
