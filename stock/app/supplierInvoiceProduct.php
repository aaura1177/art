<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class supplierInvoiceProduct extends Model
{
    protected $table = 'supplier_invoice_products';
    protected $fillable =
    [
        'product_id',  
        'supplier_invoice_id',  
        'poc_table_id',
        'quantity',
         'quantity2',
        'total',
        'purchase_order_id',
        'amount',
          'discount',
        'discount_type',
        'gst'
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
    return $this->belongsTo('App\consumable', 'product_id', 'id');
}

      public function mulitple_po()
    {
        return $this->belongsTo('App\purchaseOrder','purchase_order_id');
    }
}
