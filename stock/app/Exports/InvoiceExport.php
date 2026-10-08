<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InvoiceExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct($fsd, $fed)
    {
        $this->fsd = $fsd;
        $this->fed = $fed;
    }

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new InvoiceProductsExport($this->fsd, $this->fed);
        $sheets[] = new InvoiceTableExport($this->fsd, $this->fed);

        return $sheets;
    } 
}
