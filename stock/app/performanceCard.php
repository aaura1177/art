<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class performanceCard extends Model
{
    protected $table = 'performance_cards';
    protected $fillable =
    [
        'contractor_id',
		'date',
		'job',
    ];
	
	public function performanceCardProduct()
    {
        return $this->hasMany('App\performanceCardProduct');
    }
    public function contractor(){
		return $this->belongsTo('App\contractor');
	}
    
}
