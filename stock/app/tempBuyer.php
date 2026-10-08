<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class tempBuyer extends Model
{
    protected $table = 'temp_buyer_table';
    protected $fillable =
    [
    	'code',
    	'c_name',
    	'name',
    	'address1',
    	'address2',
    	'city',
    	'state',
    	'country',
    	'postcode',
        'profit',
        'final_price_percent',
        'cost_adjustments',
        'courierType',
        'admin_profit',
        'tariff_solid_wood_percent',
        'tariff_upholstered_percent',
    ];

    public function pricingTable()
    {
        return $this->hasMany('App\pricingTable');
    }

    public function invoiceBuyers()
    {
        return $this->hasMany('App\buyer', 'temp_buyer_id', 'id');
    }
}
