<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class allocation extends Model
{
    protected $table = 'allocation';
    protected $fillable =
    [
        'contractor_id',	
    	'product_id',
    	'quantity',
    	'finish',
    	'refno',
    	'ucost',
    	'tcost',
        'vol_unit',
    	'tvol',
    	'remarks'
    ];

    public function contractor()
    {
        return $this->belongsTo('App\contractor');
    }

    public function product()
    {
        return $this->belongsTo('App\product');
    }
}