<?php

namespace App\Exports;
use Illuminate\Http\Request;
use App\ContainersAllocation;
use App\supplierProduct;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings,WithMapping, WithStyles, WithEvents, WithCustomStartCell};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class NegativeItemListExport implements  FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomStartCell
{
    protected $id;
    protected $buyer_order_number;

    public function __construct($id,$buyer_order_number)
    {
        $this->id = $id;
        $this->buyer_order_number = $buyer_order_number;
    }

    public function collection()
    {
        $container = ContainersAllocation::with([
            'containerAllocationDetails.purchaseOrderInfo.poTable',
            'containerAllocationDetails.productInfo',
            'containerAllocationDetails.supplierInfo'
        ])->findOrFail($this->id);

        $groupedBySupplier = $container->containerAllocationDetails->groupBy('supplier_id');
        $result = [];

        foreach ($groupedBySupplier as $supplierId => $products) {
            $firstProduct = $products->first();
            $supplier = $firstProduct->supplierInfo;
            $poInfo = $firstProduct->purchaseOrderInfo;
            $poTable = collect($poInfo?->poTable);
            $totalRemQty = $poTable->sum('remqty');

            foreach ($products as $product) {
                $remQty = $poTable->firstWhere(fn ($po) =>
                    $po['poid'] == $product->purchase_order_id && $po['product_id'] == $product->product_id
                )['remqty'] ?? 0;

                if ($remQty == 0) continue;

                // $rate = supplierProduct::where([
                //     ['supplier_id', $supplierId],
                //     ['product_id', $product->product_id]])->value('rate') ?? '—';

                $result[] = [
                    'product_name'   => $product->productInfo?->name,
                    'product_sku'    => $product->productInfo?->code,
                    'supplier_name'  => $supplier?->c_name,
                    'po_date' => !empty($poInfo?->podate)? date('d M Y', strtotime($poInfo->podate)): null,
                    'delivery_date' => !empty($poInfo?->del_date)? date('d M Y', strtotime($poInfo->del_date)): null,
                    'asked_qty'      => $product->asked_quantity,
                    'delivered_qty'  => $product->asked_quantity - $remQty,
                    'remQty'         => $remQty,
                ];
            }
        }

        return collect($result); // if using Laravel Excel
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'CODE',
            'Supplier Name',
            'Purchase Order Date',
            'Delivery Date',
            'Asked Qty',
            'Delivered Qty',
            'Remaining Qty',
        ];
    }

    public function map($row): array
    {
        return [
            $row['product_name'],
            $row['product_sku'],
            $row['supplier_name'],
            $row['po_date'],
            $row['delivery_date'],
            $row['asked_qty'],
            $row['delivered_qty'],
            $row['remQty'],
        ];
    }


    public function startCell(): string
    {
        return 'A2'; // Data starts on row 2 (row 1 is title)
    }

    public function styles(Worksheet $sheet): array
    {

        return [
            2 => [ // Header row
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ]
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFD9D9D9'], // Light gray
                ],
            ]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge cells for the title
                $highestColumn = $sheet->getHighestColumn();
                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->setCellValue('A1', 'Negative Item List - '.$this->buyer_order_number);
                // Set row height
                $sheet->getRowDimension(1)->setRowHeight(28);

                // Style title
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Auto-size columns
                $columnCount = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                for ($i = 1; $i <= $columnCount; $i++) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
                }
            }
        ];
    }
}