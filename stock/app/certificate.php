<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class certificate extends Model
{
    protected $table = 'certificate';
    protected $fillable =
    [
    	'name',
    	'description',
    	
    ];
}
