<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class sample extends Model
{
    protected $table = 'samples';
    protected $fillable =
    [
        'category_id',
        'subcategory_id',
        'imageURL',
    	'code',
    	'name',
    	'finishing',
    	'width',
    	'height',
    	'depth',
        'boxwidth',
        'boxheight',
        'boxdepth',
        'wholesalevolume',
        'dropshipvolume',
    	'volume',
        'hardware1',
        'hardware2',
        'hardware3',
        'hardware4',
    	'hardware5',
		'hardware1_quantity',
        'hardware2_quantity',
        'hardware3_quantity',
        'hardware4_quantity',
    	'hardware5_quantity',
    	'addons',
    	'remarks',
        'quantity',
        'upholstry',
        'corner',
    	'lhardware'
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