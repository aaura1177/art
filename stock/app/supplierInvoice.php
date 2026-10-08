<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class supplierInvoice extends Model
{   
    protected $table = 'supplier_invoices';
    protected $fillable =
    [
        'purchase_order_id',
         'supplier_id',
        'supplier_invoice_number',
        'internal_invoice_number',
        'eway_bill_no',
        'mulitple_po',
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
                'totaldiscount',
        'invoice_date',
        'tdsTotal',
        'roundoff',
         'reference_number',
        'batch_no'
    ];

    public function supplier()
    {
        return $this->belongsTo('App\supplier','user_id');
    }  

       public function supplierData()
    {
        return $this->belongsTo('App\supplier','supplier_id');
    }  

    public function purchaseOrder()
    {
        return $this->belongsTo('App\purchaseOrder', 'purchase_order_id', 'id' );
    }  
    public function purchaseOrderConsumable()
    {
        return $this->belongsTo('App\purchaseOrderConsumable', 'purchase_order_id', 'id' );
    }  

    public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
    }

    public function consumablePurchaseBill()
    {
        return $this->hasOne('App\purchaseBillConsumable', 'supplier_invoice_id', 'id');
    }

    public function cartonPurchaseBill()
    {
        return $this->hasOne('App\PurchaseBillCarton', 'supplier_invoice_id', 'id');
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