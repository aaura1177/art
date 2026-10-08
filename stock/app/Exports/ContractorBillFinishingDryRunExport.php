<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ContractorBillFinishingDryRunExport implements FromCollection, WithHeadings
{
    /**
     * @param  \Illuminate\Support\Collection<int, array<int, mixed>>  $rows  each row: ordered values matching headings()
     */
    public function __construct(
        private readonly \Illuminate\Support\Collection $rows
    ) {}

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'contractor_bill_id',
            'invoice_id',
            'invoice_no',
            'invoice_date',
            'contractor_bill_created_at',
            'product_id',
            'product_code',
            'contractor_id',
            'finishing_name_on_bill',
            'quantity',
            'volume_cbm',
            'stored_finishing_rate',
            'stored_amount',
            'corrected_finishing_rate_old_formula',
            'corrected_amount_old_formula',
            'diff_corrected_minus_stored_rate',
            'diff_corrected_minus_stored_amount',
            'master_old_rate',
            'master_current_rate',
        ];
    }
}
