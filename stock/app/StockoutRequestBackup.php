<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockoutRequestBackup extends Model
{
    protected $table = 'stockout_request_backup';
        public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'buyer_order_no',
        'error_message',
    ];

    // One backup has many products
    public function products()
    {
        return $this->hasMany(StockoutRequestProduct::class, 'backup_id', 'id');
    }
}
