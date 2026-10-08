<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class packingList extends Model
{
    protected $table = 'packinglist';
    protected $fillable =
    [
        'buyer_order_no',
        'totalbox',
        'tquantity',
        'totalwt',
        'grosswt',
    ];
}
