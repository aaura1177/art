<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class serviceProductTable extends Model
{
    protected $table = 'supplier_service_invoice_products';
    
    protected $fillable =
    [
        'product_id',  
        'quantity',
        'supplier_invoice_id',
        'purchase_order_id',
        'amount',
        'unit',
        'gst',
        'total',
        'percentage',
    ];

    public function supplierInvoiceTable()
    {
        return $this->belongsTo('App\supplierServiceInvoice', 'supplier_invoice_id', 'id');
    }

    
    public function sub_service_product()
    {
        return $this->hasMany('App\SubServiceProductTable', 'supplier_invoice_product_id', 'id');
    }

    public function ServiceProduct()
    {
        return $this->belongsTo('App\ServiceProduct','product_id','id');
    }
}
