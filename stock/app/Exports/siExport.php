<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class siExport implements WithMultipleSheets
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

        //$sheets[] = new ProductsExport($this->fsd, $this->fed);
        $sheets[] = new SiTableExport($this->fsd, $this->fed);

        return $sheets;
    }

}
