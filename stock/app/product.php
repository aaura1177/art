<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class product extends Model
{
    protected $table = 'product_table';
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
    	'lhardware',
		'finishing_price',
        'gross_weight',
        'net_weight',
        'product_ledger_id',
        'finshing_extra_price_id'
    ];

    public function category()
    {
        return $this->belongsTo('App\category');
    }

    public function subcategory()
    {
        return $this->belongsTo('App\subCategory');
    }

    public function rejectRepair()
    {
        return $this->hasMany('App\rejectRepair');
    }

    public function poTable()
    {
        return $this->hasMany('App\poTable');
    }

    public function pbTable()
    {
        return $this->hasMany('App\pbTable','product_id');
    }

    public function invoiceTable()
    {
        return $this->hasMany('App\invoiceTable');
    }

    public function stockout()
    {
        return $this->hasMany('App\stockout');
    }

    public function stockoutTable()
    {
        return $this->hasMany('App\stockoutTable');
    }

    public function allocation()
    {
        return $this->hasMany('App\allocation');
    }
	
	public function productLocations()
    {
        return $this->hasMany('App\productLocations','product_id');
    }
	public function packaging()
    {
        return $this->hasOne('App\packaging','product_id');
    }

    public function productLedger()
    {
        return $this->belongsTo('App\ProductLedger', 'product_ledger_id');
    }    
              
}