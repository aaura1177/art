<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class supplierInvoiceReturnProduct extends Model
{
    protected $table = 'supplier_invoice_return_products';
    protected $fillable =
    [
        
        'supplier_invoice_id',
        'supplier_invoice_return_id',
        'product_id',
        'qty'
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
