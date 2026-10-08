<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class SubServiceProductTable extends Model
{   
    protected $table = 'sub_supplier_service_invoice_products';
    protected $fillable = [
        'supplier_invoice_id',
        'product_id',
        'supplier_invoice_product_id',
        'sub_quantity',
        'sub_amount',
        'sub_gstamount',
        'sub_name',
        'sub_rate',
        'sub_percentage',
        'sub_remqty',
        'sub_remaining_percentage',
        'sub_remaining_amount',
    ];
    public function serviceProductTableIN()
    {
        return $this->belongsTo('App\serviceProductTable', 'supplier_invoice_product_id', 'id');
    }
    public function supplierInvoice()
{
    return $this->belongsTo('App\supplierServiceInvoice', 'supplier_invoice_id', 'id');
}
    
}