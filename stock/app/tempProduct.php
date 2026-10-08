<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class tempProduct extends Model
{
    protected $table = 'temp_product_table';
    protected $fillable =
    [
        'category_id',
        'subcategory_id',
        'imageURL',
    	'code',
    	'EAN',
    	'HSN',
    	'name',
    	'finishing',
    	'gstslab',
    	'width',
    	'height',
    	'depth',
        'boxwidth',
        'boxheight',
        'boxdepth',
        'wholesalevolume',
        'dropshipvolume',
    	'volume',
    	'hardware',
    	'addons',
    	'remarks',
    	'quantity',
    ];

    public function category()
    {
        return $this->belongsTo('App\category');
    }

    public function subcategory()
    {
        return $this->belongsTo('App\subCategory');
    }
}
