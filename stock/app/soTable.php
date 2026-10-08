<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class soTable extends Model
{
    protected $table = 'supplier_invoice_products';
    protected $fillable =
    [
        'product_id',
        'quantity',
        'quantity2',
        'supplier_invoice_id',
        'purchase_order_id',
        'poc_table_id',
        'amount',
        'mulitple_po',
        'amount',
        'gst',
        'discount',
        'discount_type',
        'total',
        'unit',
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
