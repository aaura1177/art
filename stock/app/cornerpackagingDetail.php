<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class cornerpackagingDetail extends Model
{
    protected $table = 'cornerpackaging_details';
    protected $fillable =
    [
        'cornerpackaging_id',
        'employee_id',
        'corner_quantity',
        'l_quantity',
		'amount'
    ];
	
	public function employee()
    {
        return $this->belongsTo('App\employee');
    }

}