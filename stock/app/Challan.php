<?php

namespace App;

use Illuminate\Database\Eloquent\Model;



class Challan extends Model
{
    protected $table = 'challan';

    protected $fillable = [
        'purchase_order_id',
        'challan_number',
        'internal_invoice_number',
        'eway_bill_no',
        'eway_bill_pdf',
        'vehicle_no',
        'tquantity',
        'tgst',
        'subTotal',
        'tamount',
        'status',
        'supplier_id',
        'is_approved',
        'purchase_order_type',
        'challan_date',
        'batch_no',
        'tdsTotal',
    ];


    public function supplier()
    {
        return $this->belongsTo('App\supplier');
    }  

  
    public function purchaseOrder()
    {
        return $this->belongsTo('App\purchaseOrderConsumable', 'purchase_order_id', 'id' );
    }  

    public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
    }

    public function product()
    {
        return $this->hasMany('App\product');
    }

    public function supplierInvoiceProducts()
    {
        return $this->hasMany('App\soTable','supplier_invoice_id','id');
    }

    
}