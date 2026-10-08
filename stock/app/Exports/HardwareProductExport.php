<?php

namespace App\Exports;

use App\product;
use App\hardwares;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class HardwareProductExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $products = product::all();
        $rows = collect();
        $i = 1; // ← serial number counter

        foreach ($products as $p) {

            $hardwareIds = [
                $p->hardware1,
                $p->hardware2,
                $p->hardware3,
                $p->hardware4,
                $p->hardware5,
            ];

            $quantities = [
                $p->hardware1_quantity,
                $p->hardware2_quantity,
                $p->hardware3_quantity,
                $p->hardware4_quantity,
                $p->hardware5_quantity,
            ];

            foreach ($hardwareIds as $index => $hid) {

                if (!$hid) continue;

                $hardware = hardwares::find($hid);
                if (!$hardware) continue;

                $quantity = $quantities[$index] ?? 0;

                $rows->push([
                    'id'       => $i++,  
                    'code'     => $p->code,
                    'hardware' => $hardware->name,
                    'quantity' => $quantity,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'ID',          
            'Product Code',
            'Consumable Name',
            'Quantity',
        ];
    }
}
