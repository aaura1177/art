<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class suggestedProduct extends Model
{
    protected $table = 'suggested_products';
    protected $fillable =
    [
        'code',
		'last_month_sale',
		'current_price',
		'current_stock',
    ];

}
