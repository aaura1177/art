<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class supplier extends Model
{
    protected $table = 'suppliers';
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
        'gstpercent',
		'type',
        'short_name',
        'tdsledger',
        'tdsdate',
        'po_monthly_limit_furniture',
        'po_limit_start_date_furniture',
        'po_monthly_limit_consumable',
        'po_limit_start_date_consumable',
        'is_merged',
        'po_monthly_limit_merged',
        'po_limit_start_date_merged',
        'terms_accepted',
        'terms_accepted_at',
        'terms_accepted_terms_updated_at',
    ];

    protected $casts = [
        'terms_accepted' => 'boolean',
        'terms_accepted_at' => 'datetime',
        'terms_accepted_terms_updated_at' => 'datetime',
    ];

    public function purchaseOrder()
    {
        return $this->hasMany('App\purchaseOrder');
    }

    public function user()
    {
        return $this->hasMany('App\User');
    }

    public function rejectRepair()
    {
        return $this->hasMany('App\rejectRepair');
    }
    public function supplierProduct()
    {
        return $this->hasMany('App\supplierProduct','supplier_id');
    }

    
}