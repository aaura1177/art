<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CartonSupplierInvoiceReversal extends Model
{
    protected $table = 'carton_supplier_invoice_reversals';

    protected $fillable = [
        'supplier_invoice_id',
        'purchase_bill_carton_id',
        'purchase_order_id',
        'reversed_by',
        'reason',
        'lines_snapshot',
    ];

    protected $casts = [
        'lines_snapshot' => 'array',
    ];
}
