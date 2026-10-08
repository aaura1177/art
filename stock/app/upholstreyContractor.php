<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class upholstreyContractor extends Model
{
    protected $table = 'upholestry_contractors';
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
}
