<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseBillCarton extends Model
{
    protected $table = 'purchase_bill_carton';
    protected $fillable =
    [
        'purchaseOrder_id',  
        'ewaybill',
        'subtotal',
        'supp_inv_no',
        'supp_inv_date',
        'gst',
        'freight',
        'roundoff',
        'total',
        'tdsTotal',
        'quantity',
        'swap_id',
        'is_checked',
         'orderqty2',
    'receiveqty2',
    'remainingqty2',
    'supplier_id',
    'supplier_invoice_id',
    'invoice_status',
    'mulitple_po_purchaseBill',
    ];
    
    public function pbTable()
    {
        return $this->hasMany('App\PbTableCorton','purchasebill_id', 'id' );
    }

    public function purchaseOrder()
    {
        return $this->belongsTo('App\purchaseOrderConsumable', 'purchaseOrder_id', 'id' );
    }

    public function supplier()
    {
        return $this->belongsTo('App\supplier', 'supplier_id', 'id');
    }
}
