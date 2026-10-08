<?php

namespace App\Exports;

use App\invoice;
use App\invexport;
use App\invoiceTable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InvoiceExportSalesRegister implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    public function __construct($fersd, $ferd)
    {

        $this->fersd = $fersd;
        $this->ferd = $ferd;
    }

    public function styles(Worksheet $sheet)
    {   
            $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000 '],
                ],
            ],
        ];

        $sheet->getStyle('A1:U100')->applyFromArray($styleArray)->getAlignment()->setWrapText(true);
        $sheet->getDefaultRowDimension()->setRowHeight(40);
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(8);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(6);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(17);
        $sheet->getColumnDimension('J')->setWidth(17);
        $sheet->getColumnDimension('K')->setWidth(11);
        $sheet->getColumnDimension('L')->setWidth(10);
        $sheet->getColumnDimension('M')->setWidth(10);
        $sheet->getColumnDimension('N')->setWidth(20);
        $sheet->getColumnDimension('O')->setWidth(8);
        $sheet->getColumnDimension('P')->setWidth(15);
        $sheet->getColumnDimension('Q')->setWidth(14);
        $sheet->getColumnDimension('R')->setWidth(10);
        $sheet->getColumnDimension('S')->setWidth(5);
        $sheet->getColumnDimension('T')->setWidth(5);
        $sheet->getColumnDimension('U')->setWidth(10);
    }

    public function query()
    {
        $invoice = invoice::whereBetween('date', [$this->fersd, $this->ferd])
            ->select('id')
            ->orderBy('date', 'ASC');
            $invoiceTable = invexport::whereIn('invoice_id', $invoice)->select('invoice_id', 'realisation_date', 'realisation_fc', 'rate', 'bank_reference')->orderby('invoice_id', 'ASC')->orderby('invoice_id', 'DESC');
        //echo '<pre>';print_r($invoiceTable);die;
        return $invoiceTable;
    }

    public function headings(): array
    {
        return [
            "INV NO", "INV DATE", "Invoice Value", "Buyer Code", "IGST Amount", "# of Pkgs",
            "BL # & Date", "Shipping Bill # & Date ", "Container #", "EGM # & Date",
            "Agent's Name", "ETD", "ETA", "Bank Ref #", "Booking Rate", "Date of Realisation", "Invoice Amt. Realised", "INR Amount", "FBC","Rate","Comm/Int."

        ];
    }

    public function map($invoiceTable): array
    {
        $bl_date = ($invoiceTable->invoice->bl_date)?date('d-M-y', strtotime($invoiceTable->invoice->bl_date)):'';
        $shipping_bill_date = ($invoiceTable->invoice->shipping_bill_date)?date('d-M-y', strtotime($invoiceTable->invoice->shipping_bill_date)):'';
        $egm_date = ($invoiceTable->invoice->egm_date)?date('d-M-y', strtotime($invoiceTable->invoice->egm_date)):'';
        $main_arr = [
            $invoiceTable->invoice->invoiceno,
            date('d-M-y', strtotime($invoiceTable->invoice->date)),

            $invoiceTable->invoice->totalamount,
            $invoiceTable->invoice->buyerorderno,

            $invoiceTable->invoice->totalgst,
            $invoiceTable->invoice->totalbox,
            //$valIncGst,
            $invoiceTable->invoice->bl_no.' '.$bl_date,
            $invoiceTable->invoice->shipping_bill_no.' '.$shipping_bill_date,
            $invoiceTable->invoice->containerno,
            $invoiceTable->invoice->egm_no.' '.$egm_date,
            $invoiceTable->invoice->agent_name,
            ($invoiceTable->invoice->etd)?date('d-M-y', strtotime($invoiceTable->invoice->etd)):'',
            ($invoiceTable->invoice->eta)?date('d-M-y', strtotime($invoiceTable->invoice->eta)):'',
            $invoiceTable->bank_reference,
            $invoiceTable->rate,
            date('d-M-Y', strtotime($invoiceTable->realisation_date)),
            $invoiceTable->realisation_fc,
            $invoiceTable->realisation_fc * $invoiceTable->rate,
            $invoiceTable->invoice->fbc,
            $invoiceTable->invoice->conrate,
        ];
        if(\Request::session()->get('inv_no_s') == null) {
            \Request::session()->put('inv_no_s', $invoiceTable->invoice->invoiceno);
            return $main_arr;
        }else{
            $inv_no = \Request::session()->get('inv_no_s');
            \Request::session()->put('inv_no_s', $invoiceTable->invoice->invoiceno);
            if($inv_no == $invoiceTable->invoice->invoiceno){
                return[
                   '',
                    '',
                    "",
                    '',
                    "",
                    "",
                    "",
                    "",
                    "",
                    '',
                    "",
                    "",
                    "",
                    $invoiceTable->bank_reference,
                    $invoiceTable->rate,
                    date('d-M-Y', strtotime($invoiceTable->realisation_date)),
                    $invoiceTable->realisation_fc,
                    $invoiceTable->realisation_fc * $invoiceTable->rate,
                    $invoiceTable->invoice->fbc,
                    ''
                ];
            }else{
                $arr = $main_arr;
                return $arr;
            }
        }
        // return [
        //     $invoiceTable->invoiceno,
        //     date('d-M-y', strtotime($invoiceTable->date)),

        //     $invoiceTable->totalamount,
        //     $invoiceTable->buyerorderno,

        //     $invoiceTable->totalgst,
        //     $invoiceTable->totalbox,
        //     //$valIncGst,
        //     $invoiceTable->bl_no.' '.$bl_date,
        //     $invoiceTable->shipping_bill_no.' '.$shipping_bill_date,
        //     $invoiceTable->containerno,
        //     $invoiceTable->egm_no.' '.$egm_date,
        //     $invoiceTable->agent_name,
        //     ($invoiceTable->etd)?date('d-M-y', strtotime($invoiceTable->etd)):'',
        //     ($invoiceTable->eta)?date('d-M-y', strtotime($invoiceTable->eta)):'',
        // ];
    }
}
