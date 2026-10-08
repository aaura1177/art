<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChallanProduct extends Model
{
    protected $table = 'challan_products';

    protected $fillable = [
        'challan_id',
        'product_id',
        'consumable_id',
        'quantity',
        'returned_qty',
        'total',
        'rate',
        'purchase_order_id',
        'amount',
        'gst',
        'gstslab',
        'quantity2',
        'return_qty2',
    ];

    public function supplierInvoiceTable()
    {
        return $this->belongsTo('App\supplierInvoice', 'supplier_invoice_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo('App\product');
    }
    public function consumable()
    {
        return $this->belongsTo('App\consumable');
    }
}
