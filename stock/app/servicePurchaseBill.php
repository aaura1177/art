<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class servicePurchaseBill extends Model
{
    protected $table = 'service_purchase_bill';
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
        'tdsTotal',
        'quantity',
        'swap_id',
        'is_checked',
        'supplier_invoice_id',
        'invoice_status',
    ];

    public function supplierInvoice()
    {
        return $this->belongsTo(supplierServiceInvoice::class, 'supplier_invoice_id', 'id');
    }
    
    public function ServicePbTable()
    {
        return $this->hasMany('App\ServicePbTable','purchasebill_id', 'id' );
    }

    public function purchaseOrder()
    {
        return $this->belongsTo('App\Service', 'purchaseOrder_id', 'id' );
    }
}
