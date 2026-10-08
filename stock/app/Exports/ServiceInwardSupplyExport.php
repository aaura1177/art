<?php

namespace App\Exports;

use App\servicePurchaseBill;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable; // Add this import

class ServiceInwardSupplyExport implements FromQuery, WithHeadings, WithTitle, WithMapping
{
    use Exportable; // Use the Exportable trait to enable the download method

    protected $fsd;
    protected $fed;

    // Constructor to accept the date range
    public function __construct($fsd, $fed)
    {
        $this->fsd = $fsd;
        $this->fed = $fed;
    }

    // Query method to fetch data based on date range
    public function query()
    {
        // Convert the provided date range to 'Y-m-d' format
        $fsd1 = strtotime($this->fsd);
        $newfsd = date('Y-m-d', $fsd1);

        $fed1 = strtotime($this->fed);
        $newfed = date('Y-m-d', $fed1);

        // Get the data from the servicePurchaseBill model based on date range
        return servicePurchaseBill::whereBetween('supp_inv_date', [$newfsd, $newfed])
            ->select('purchaseOrder_id', 'supp_inv_no', 'supp_inv_date', 'ewaybill', 'quantity', 'subtotal', 'gst', 'freight', 'total')
            ->orderBy('supp_inv_date', 'ASC');
    }

    // Headings for the Excel export
    public function headings(): array
    {
        return [
            "Purchase Order No.",
            "Supplier Invoice No.",
            "Supplier Invoice Date",
            "E-way Bill No.",
            "Quantity",
            "SubTotal",
            "GST",
            "Freight",
            "Total Amount"
        ];
    }

    // Title of the sheet
    public function title(): string
    {
        return 'Inward Supply';
    }

    // Mapping function to format each row in the export
    public function map($purchaseBill): array
    {
        // Check if the purchaseOrder exists and is related
        $purchaseOrder = $purchaseBill->purchaseOrder;

        return [
            $purchaseOrder ? $purchaseOrder->pono : 'N/A', // Fallback to 'N/A' if purchaseOrder is not available
            $purchaseBill->supp_inv_no,
            $purchaseBill->supp_inv_date,
            $purchaseBill->ewaybill,
            $purchaseBill->quantity,
            $purchaseBill->subtotal,
            $purchaseBill->gst,
            $purchaseBill->freight,
            $purchaseBill->total
        ];
    }
}
