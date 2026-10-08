<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class shippingLines extends Model
{
    protected $table = 'shipping_lines';
    protected $fillable =
    [
    	'name',
    	'agent_name',
        'agent_uk',
    ];
}
