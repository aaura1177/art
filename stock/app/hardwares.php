<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class hardwares extends Model
{
    protected $table = 'hardwares';
    protected $fillable =
    [
    	'name',
    	'rate',
    	'hardware_supplier',
        'consumable_id',
    ];
	
	
	public function hardwareSuppliers()
    {
       return $this->hasOne('App\hardwareSuppliers','id' ,'hardware_supplier');
    }

    public function consumable()
    {
        return $this->belongsTo('App\consumable', 'consumable_id');
    }
}
