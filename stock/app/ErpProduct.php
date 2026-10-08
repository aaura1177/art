<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ErpProduct extends Model
{
    protected $fillable =
    [
        'sku',
        'quantity',
        'zone_name',
        'zone_serial',
        'site_access',
        'warehouse_quantity',
    ];

    public function history()
    {
        return $this->hasMany('App\ErpHistory', 'sku');
    }
}
