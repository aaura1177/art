<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class sampleSupplierInvoice extends Model
{
    protected $table = 'sample_supplier_invoices';

    protected $fillable = [
        'purchase_order_id',
        'supplier_id',
        'sample_purchase_bill_id',
        'supplier_invoice_number',
        'eway_bill_no',
        'invoice_date',
        'tquantity',
        'tgst',
        'subTotal',
        'tamount',
        'freight',
        'status',
        'is_approved',
        'user_id',
        'batch_no',
        'reference_number',
    ];

    public function supplier()
    {
        return $this->belongsTo('App\supplier', 'supplier_id');
    }

    public function samplePurchaseOrder()
    {
        return $this->belongsTo('App\samplePurchaseOrder', 'purchase_order_id', 'id');
    }

    public function samplePurchaseBill()
    {
        return $this->belongsTo('App\samplePurchaseBill', 'sample_purchase_bill_id', 'id');
    }

    public function products()
    {
        return $this->hasMany('App\sampleSupplierInvoiceProduct', 'sample_supplier_invoice_id', 'id');
    }
}
