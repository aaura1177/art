<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomerModel extends Model
{
    protected $table = 'wp_customers_infos';
    protected $fillable = [
        'wp_customer_id',
        'wp_customer_name',
        'wp_customer_email',
        'site_access',
    ];
}
