<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class cornerpackaging extends Model
{
    protected $table = 'cornerpackaging';
    protected $fillable =
    [
        'invoice_ids',
        'month',
        'po_no'
    ];
	
	public function cornerpackagingDetail()
    {
        return $this->hasMany('App\cornerpackagingDetail');
    }

}