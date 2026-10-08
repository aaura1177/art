<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class stockoutTableConsumable extends Model
{
    protected $table = 'stockouttable_consumable';
    protected $fillable =
    [
        'invoice_id',
        'stock_id',
        'consumable_id',
        'ean',
        'orderqty',
        'receiveqty',
        'remainingqty',
		'location',
		'supp_inv_no',
       
    ];

    public function consumable()
    {
        return $this->belongsTo('App\consumable');
    }

    public function invoice()
    {
        return $this->belongsTo('App\invoice');
    }

    public function stockout()
    {
        return $this->belongsTo('App\stockout','stock_id', 'id' );
    }
}
