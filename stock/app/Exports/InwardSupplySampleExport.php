<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InwardSupplySampleExport implements WithMultipleSheets
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

        $sheets[] = new InwardSupplySampleProductsExport($this->fsd, $this->fed);
        $sheets[] = new InwardSupplySampleTableExport($this->fsd, $this->fed);

        return $sheets;
   }
}
