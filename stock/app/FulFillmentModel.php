<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FulFillmentModel extends Model
{
    protected $table = 'sku_fulfillment_qtys';
    protected $fillable = [
        'wp_customers_info_id',
        'sku',
        'qty',
        'site_access',
    ];
}
