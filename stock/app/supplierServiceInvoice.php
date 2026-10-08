<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class supplierServiceInvoice extends Model
{   
    protected $table = 'supplier_service_invoices';
    protected $fillable =
    [
        'purchase_order_id',
        'supplier_invoice_number',
        'internal_invoice_number',
        'eway_bill_no',
        'eway_bill_pdf',
        'vehicle_no',
        'tquantity',
        'tgst',
        'subTotal',
        'tamount',
        'status',
        'user_id',
        'purchase_order_type',
        'supplier_invoice_id',
        'invoice_date',
        'tdsTotal',
        'batch_no'
    ];

    public function supplier()
    {
        return $this->belongsTo('App\supplier');
    }  

    public function purchaseOrder()
    {
        return $this->belongsTo('App\Service', 'purchase_order_id', 'id' );
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
        return $this->hasMany('App\serviceProductTable','supplier_invoice_id','id');
    }

    
}