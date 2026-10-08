<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class subCategory extends Model
{
    protected $table = 'product_subcategory';
    protected $fillable = ['name'];

    public function product()
    {
        return $this->hasMany('App\product', 'subcategory_id', 'id');
    }
}
