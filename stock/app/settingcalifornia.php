<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class settingcalifornia extends Model
{
    protected $table = 'settingscalifornia';
    protected $fillable =
    [
    	'c_name',
    	'pan',
    	'gstin',
    	'address1',
    	'address2',
    	'city',
    	'state',
    	'country',
    	'postcode',
    	'iec',
    	'rbi',
    	'gsp',
        'lut',
        'website',
    	'email',
    	'phone1',
    	'phone2',
    	'logourl',
    	'volWt',
    	'shippingCost2',
    	'StorageCost',
		'corner_rate',
		'packaging_per_sqinch_rate',
		'factory_address'
    ];
}
