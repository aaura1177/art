<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FulfillmentLogsModel extends Model
{
    protected $table = 'sku_fulfillment_logs';
    protected $fillable = [
        'sku',
        'order_id',
        'qty',
        'order_type',
        'site_access',
        'wp_customers_info_id',
        'remark',
        'remaining_stock'
    ];
}
