<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use App\ErpHistory;
use App\ErpSheet;
use App\ErpProduct;
use Exception;

class ErpDateWiseExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct($fsd)
    {
        $this->fsd = $fsd;
    }

    public function query()
    {

        $location = \Request::session()->get('country');
        $fsd1 = strtotime($this->fsd);
        $newfsd = date('Y-m-d', $fsd1);

        // $erp_history = ErpHistory::orderBy('created_at','asc')->get();
        // $i=8439;
        // foreach ($erp_history as $key => $value) {
        //     $value->id = $i;
        //     $value->save();
        //     $i++;
        // }
        // die;

        $products = ErpHistory::select('sku', 'stock')
            ->where('site_access', $location)
            ->whereDate('date', '<=', $newfsd)
            ->whereRaw('id IN (select MAX(id) FROM erp_history WHERE site_access = "' . $location . '" and date <= "' . $newfsd . '" GROUP BY sku)')
            ->orderBy('created_at', 'desc');

        return $products;
    }

    public function headings(): array
    {
        try {
            $location = \Request::session()->get('country');
            $csvNames = [
                'us' => 'Fairview',
                'eu' => 'Magdeburg',
                'canada' => 'Canada',
                'california' => 'California'
            ];
            
            $csvName = $csvNames[$location] ?? 'IP';
            $fsd1 = strtotime($this->fsd);
            $newfsd = date('Y-m-d', $fsd1);
            $ErpSheetLessW = ErpSheet::whereDate('date', '<=', $newfsd)->where('type', 'less')->where('site_access', $location)->orderby('id', 'DESC')->where('name', 'LIKE', '%Wayfair%')->first();
            $ErpSheetLessI = ErpSheet::whereDate('date', '<=', $newfsd)->where('type', 'less')->where('site_access', $location)->orderby('id', 'DESC')->where('name', 'LIKE', '%'.$csvName.'%')->first();
            $ErpSheetAdd = ErpSheet::whereDate('date', '<=', $newfsd)->where('type', 'add')->where('site_access', $location)->orderby('id', 'DESC')->first();
            $container_name = 'NA';

            ////////CHANGES DONE HERE;
            if (isset($ErpSheetAdd->name)) {
                $name = str_replace('.xlsx', '', $ErpSheetAdd->name);
                $container_name = str_replace('invoiceTable ', '', $name);
            }

            $wayfair_name  = str_replace('.csv', '', $ErpSheetLessW->name);
            $wayfair_name  = str_replace('Wayfair-', '', $wayfair_name);

            $ip_name  = str_replace('.csv', '', $ErpSheetLessI->name);
            $ip_name  = str_replace('IP-03 CSV-', '', $ip_name);
            $h = 'Stock as on ' . date('d-m-Y', strtotime($newfsd)) . ' (with container ' . $container_name . ' and upto csv #' . $wayfair_name . ' & #' . $ip_name . ')';
            $heading = ["SKU", "$h"];

            return $heading;
        } catch (Exception $e) {
            return ['error' => 'An error occurred: ' . $e->getMessage()];
        }
    }

    public function title(): string
    {
        return 'Erp Table Products';
    }

    public function map($product): array
    {
        return [
            $product->sku,
            number_format($product->stock)
        ];
    }
}
