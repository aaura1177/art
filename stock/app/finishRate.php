<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class finishRate extends Model
{
	protected $table = 'finishing_rates';
    protected $fillable = ['name','rate','old_rate'];

    
}
