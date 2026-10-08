<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class supplierInvoiceReturn extends Model
{
    protected $table = 'supplier_invoice_returns';
    protected $fillable =
    [
        
        'supplier_invoice_id',
        'status',
        'type'
    ];

    public function supplierInvoiceTable()
    {
        return $this->belongsTo('App\supplierInvoice', 'supplier_invoice_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo('App\product');
    }
}
