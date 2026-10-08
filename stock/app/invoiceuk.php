<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class invoiceuk extends Model
{
    protected $table = 'invoice_uk';
    protected $fillable =
    [
        'consignee_id',	
    	'buyer_id',
    	'date',
    	'buyerorderno',
    	'containerno',
    	'vehicleno',
    	'totalbox',
    	'pkgs',
    	'currency',
        'fob',
        'payterms',
        'shipmentby',
        'desgoods',
    	'carriage',
    	'receipt',
    	'shipment',
    	'postloading',
    	'discharge',
    	'destination',
    	'totalgst',
    	'totalquantity',
    	'totalwt',
        'totalgrosswt',
    	'totalamount',
    	'conrate',
    	'rateamount',
        'status',
        'invoiceno',
        'invoicetype',
        'exportstatus',
        'ewaybillno',
        'declaration',
        'shipping_charges',
        'packing_charges',
        'discount',
        'additional_info',
        'bl_no',
        'bl_date',
        'irn',
        'ack_no',
        'ack_date',
        'einvoice_qr',
        'lr_rr_no',
        'bill_to',
        'ship_to',
        'deposit_date',
        'deposit',
        'delivery_term',
        'agent_name',
        'vat',
        'container_size',
        'oceanic_freight',
        'refund',
        'refund_statement'
    ];

    public function buyer()
    {
        return $this->belongsTo('App\buyer');
    }
    
    public function consignee()
    {
        return $this->belongsTo('App\buyer','consignee_id');
    }

    public function invoiceTable()
    {
        return $this->hasMany('App\invoiceTableUk','invoice_id');
    }

    public function stockoutTable()
    {
        return $this->hasMany('App\stockoutTable');
    }

    public function packingSheet()
    {
        return $this->hasOne('App\packingSheet');
    }

    public function stockout()
    {
        return $this->hasMany('App\stockout');
    }

    public function invexport()
    {
        return $this->hasMany('App\invexport');
    }

    public function portInfo()
    {
        // Relate the discharge field of the invoices with the Port name
        return $this->belongsTo(Port::class, 'discharge', 'name');
    }

    public function emissionLogs()
    {
        return $this->hasMany(EmissionInvoiceLogUk::class);
    }

}