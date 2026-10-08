<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UnitType  extends Model
{
    protected $table = 'unit_type';
   protected $fillable = [
    'name',
    'type',
    'data_type'
];

   


}
