<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InwardSupplyExport implements WithMultipleSheets
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

        $sheets[] = new InwardSupplyProductsExport($this->fsd, $this->fed);
        $sheets[] = new InwardSupplyTableExport($this->fsd, $this->fed);

        return $sheets;
   }
}
