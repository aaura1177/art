<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ErpUsProduct extends Model
{
    protected $fillable =
    [
        'sku',  
        'quantity',  
        'zone_name',
        'zone_serial'
    ];

    public function history()
    {
        return $this->hasMany('App\ErpUsHistory','sku');
    }

}
