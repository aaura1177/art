<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class productGrouping extends Model
{
    protected $table = 'product_grouping';
    protected $fillable = [
		'parent_id',
		'child_id',
		'child_finish',
		'parent_finish'
	];
	
	public function parentproduct()
    {
        return $this->belongsTo('App\product','parent_id');
    }
	
	public function childProduct()
    {
        return $this->belongsTo('App\product','child_id');
    }

    
}
