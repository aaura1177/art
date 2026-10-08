<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class shipping extends Model
{
    protected $table = 'shipping';
    protected $fillable =
    [
    	'month',
    	'shipping_line_id',
    	'container',
        'reference',
        'thc',
        'bl',
        'incidental_charges',
        'transit_time',
        'type',
        'ocean_freight',
        'conversion_rate',
        'thc_uk',
        'handling_charges',
        'other',
        'total_india',
        'total_uk',
        'magnus',
        'magnus_date',
        'magnus_tracking_number',
        'eta_at_port',
    ];

    public function shippingLines()
    {
       return $this->hasOne('App\shippingLines','id' ,'shipping_line_id');
    }
}
