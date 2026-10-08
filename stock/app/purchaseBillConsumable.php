<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class purchaseBillConsumable extends Model
{
    protected $table = 'purchase_bill_consumables';
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
        'supplier_id',
        'invoice_status',
        'supplier_invoice_id',
        'mulitple_po_purchaseBill',
    ];
    
    public function pbTable()
    {
        return $this->hasMany('App\pbTableConsumable','purchasebill_id', 'id' );
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
