<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class smallhardwares extends Model
{
    protected $table = 'smallhardwares';
    protected $fillable =
    [
    	'name',
    	'rate',
    	'supplier',
    	'ratenuk',
    ];
	
	
	public function smallhardwareSupplier()
    {
       return $this->hasOne('App\supplier','id' ,'supplier');
    }
}
