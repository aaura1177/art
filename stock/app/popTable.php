<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class popTable extends Model
{
    protected $table = 'popTable';
    protected $fillable =
    [
        'product_id',  
        'quantity',
        'unit',
        'sq_inches',
        'rate',
        'amount',
        'gstslab',
        'gstamount',
        'poid',
		'box1_height',
		'box1_width',
		'box1_depth',
		'box2_height',
		'box2_width',
		'box2_depth',
		'box1_sqinch',
		'box2_sqinch',
		'box1_ply',
		'box2_ply',
		'box1_rate',
		'box2_rate',
		'box1_amount',
		'box2_amount',
		'line_drawing',
        'remqty_box2',
		'remqty_box1',
        'box2_qty'
    ];

    public function purchaseOrderTable()
    {
        return $this->belongsTo('App\purchaseOrderConsumable', 'poid', 'id');
    }

    public function product()
    {
        return $this->belongsTo('App\product');
    }
}
