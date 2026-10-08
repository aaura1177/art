<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class sampleSupplierInvoiceProduct extends Model
{
    protected $table = 'sample_supplier_invoice_products';

    protected $fillable = [
        'sample_supplier_invoice_id',
        'purchase_order_id',
        'sample_id',
        'product_id',
        'quantity',
        'amount',
        'gst',
        'total',
    ];

    public function sampleSupplierInvoice()
    {
        return $this->belongsTo('App\sampleSupplierInvoice', 'sample_supplier_invoice_id', 'id');
    }

    public function sample()
    {
        return $this->belongsTo('App\sample', 'sample_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo('App\product', 'product_id', 'id');
    }
}
