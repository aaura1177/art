<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinshingExtraPrice extends Model
{
    use HasFactory;

    protected $table = 'finshingExtra_price';

    protected $fillable = [
        'price',
    ];
}
