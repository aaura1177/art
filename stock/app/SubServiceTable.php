<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class SubServiceTable extends Model
{   
    protected $table = 'sub_ServiceTable';
    protected $fillable = [
        'po_id',
        'product_id',
        'serviceTable_id',
        'sub_name',
        'sub_rate',
        'sub_quantity',
        'sub_remqty',
        'sub_amount',
        'sub_gstslab',
        'sub_gstamount',
        'sub_remaining_amount',
        'sub_remaining_percentage',
    ];
    public function serviceTable()
    {
        return $this->belongsTo('App\serviceTable', 'serviceTable_id', 'id');
    }
    
}