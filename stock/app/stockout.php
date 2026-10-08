<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class stockout extends Model
{
    protected $table = 'stockout';
    protected $fillable =
    [
        'invoice_id',
        'buyer_ref_no',
        'swap_id',

    ];

    public function invoice()
    {
        return $this->belongsTo('App\invoice');
    }

    public function product()
    {
        return $this->belongsTo('App\product');
    }

    public function stockoutTable()
    {
        return $this->hasMany('App\stockoutTable','stock_id', 'id' );
    }

    public function stockoutTableConsumable()
    {
        return $this->hasMany('App\stockoutTableConsumable', 'stock_id', 'id');
    }

    public function stockoutTableCarton()
    {
        return $this->hasMany(StockOutTableCarton::class, 'stock_id', 'id');
    }

    public function stockoutRequestBackup()
    {
        return $this->hasOne('App\StockoutRequestBackup', 'invoice_id', 'invoice_id');
    }
}
