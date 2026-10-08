<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ProductLedger extends Model
{
    protected $table = 'product_ledgers';
    protected $fillable = [
        'name',
        'status',
    ];

    public function product()
    {
        return $this->hasMany('App\product');
    }
}