<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class rejectRepair extends Model
{
     protected $table = 'reject_repair';
    protected $fillable =
    [
    	'product_id',
        'supplier_id',
        'supplier_inv_no',
    	'status',
    	'quantity',
    	'date',
    	'quantity',
        'supplier_invoice_id',
    	'remarks',
        'send_to_supplier',
        'purchase_bill_id',
        'outward_challan_no'
    ];

    public function product()
    {
        return $this->belongsTo('App\product');
    }

    public function supplier()
    {
        return $this->belongsTo('App\supplier');
    }
    
}
