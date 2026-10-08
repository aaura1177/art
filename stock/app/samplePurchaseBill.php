<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class samplePurchaseBill extends Model
{
    protected $table = 'sample_purchase_bills';
    protected $fillable =
    [
        'purchaseOrder_id',  
        'ewaybill',
        'subtotal',
        'supp_inv_no',
        'supp_inv_date',
        'gst',
        'freight',
        'total',
        'quantity',
        'swap_id',
        'is_checked',
        'supplier_invoice_id',
    ];
    
    public function spbTable()
    {
        return $this->hasMany('App\spbTable','sample_purchasebill_id', 'id' );
    }

    public function samplePurchaseOrder()
    {
        return $this->belongsTo('App\samplePurchaseOrder', 'purchaseOrder_id', 'id' );
    }

    public function sampleSupplierInvoice()
    {
        return $this->belongsTo('App\sampleSupplierInvoice', 'supplier_invoice_id', 'id');
    }
}
