<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class allocated_backorder extends Model
{
    use HasFactory;
    protected $table = 'allocated_backorder';
    protected $fillable =
    [
        'order_id',
        'sku',
        'quantity',
        'fulfilled',
        'status'
    ];
}
