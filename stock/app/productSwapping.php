<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class productSwapping extends Model
{
    protected $table = 'product_swapping';
    protected $fillable =
    [
    	'invoice_no',
		'product_id',
    	'swapped_with',
		'quantity',
        		'ref_source_snapshot'

    ];

      protected $casts = [
        'ref_source_snapshot' => 'array',
    ];
    public function product()
    {
        return $this->belongsTo('App\product');
    }
	
	public function swappedWith()
    {
        return $this->belongsTo('App\product','swapped_with');
    }
}
