<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InvoiceExportSales implements WithMultipleSheets
{
    use Exportable;

    public function __construct($fesd, $feed)
    {
        $this->fesd = $fesd;
        $this->feed = $feed;

    }

    public function sheets(): array
    {
        $sheets = [];

        
        $sheets[] = new InvoiceSalesExport($this->fesd, $this->feed);

        return $sheets;
    } 
}
