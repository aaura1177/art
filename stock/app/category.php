<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class category extends Model
{
	protected $table = 'product_category';
    protected $fillable = ['name'];

    public function product()
    {
        return $this->hasMany('App\product');
    }
}
