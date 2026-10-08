<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class purchaseBill extends Model
{
    protected $table = 'purchase_bill';
    protected $fillable =
    [
        'purchaseOrder_id',
        'ewaybill',
        'subtotal',
        'supp_inv_no',
        'supplier_id',
        'supp_inv_date',
        'gst',
        'freight',
        'total',
        'roundoff',
        'tdsTotal',
        'quantity',
        'swap_id',
                'totaldiscount',
'mulitple_po_purchaseBill',
'getinserailno',
        'supplier_invoice_id',
        'invoice_status',

        'is_checked'
    ];

    public function pbTable()
    {
        return $this->hasMany('App\pbTable','purchasebill_id', 'id' );
    }

    public function purchaseOrder()
    {
        return $this->belongsTo('App\purchaseOrder', 'purchaseOrder_id', 'id' );
    }
       public function supplier()
    {
        return $this->belongsTo('App\supplier', 'supplier_id', 'id' );
    }
public function supplierInvoice()
{
    return $this->belongsTo(\App\supplierInvoice::class, 'purchaseOrder_id', 'purchase_order_id');
}
}