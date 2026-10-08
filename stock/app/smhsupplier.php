<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class smhsupplier extends Model
{
    protected $table = 'smallhardware_suppliers';
    protected $fillable =
    [
    	'c_name',	
    	'name',
    	'pan',
    	'address1',
    	'address2',
    	'city',
    	'state',
    	'country',
    	'postcode',
    	'gst',
    	'gstin',
    	'state_code',
        'email',
        'phone1',
        'phone2',
        'tds',
        'tdspercent',
        'gstpercent'
    ];

	public function smallhardware()
    {
        return $this->hasMany('App\smallhardware','supplier_id');
    }
}