<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class packagingPrice extends Model
{
    protected $table = 'packaging_pricing';
    protected $fillable =
    [
        // 'employee_id',  
        // 'invoices',
        // 'type',
        // 'quantity',
		'supplier_id',
		'3ply',
		'5ply',
		'7ply'
    ];
	
	public function supplier()
    {
        return $this->belongsTo('App\supplier');
    }

}
