<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RejectRepairPtable extends Model
{
    protected $table = 'reject_repair_ptable';
    protected $fillable =
    [
    	'reject_repair_id',
        'product_id',
        'receiveqty',
    	'status',
    	'quantity',
    	'batch_no',
    	'po_no',
    	'remarks'
    ];

    public function RejectRepair()
    {
        return $this->belongsTo('App\rejectRepair', 'reject_repair_ptable', 'id');
    }

    public function product()
    {
        return $this->belongsTo('App\product');
    }
    public function sent_to_supplier()
    {
        return $this->belongsTo('App\supplier','send_to_supplier');
    }
}
