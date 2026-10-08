<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\ErpUsSheet;

class ErpUsHistory extends Model
{
    protected $table = 'erp_us_history';
    protected $fillable =
    [
        'sheet_id',  
        'sku',
        'quantity',
        'type',
        'date',
        'stock',
        'remark'
    ];

    public function sheet()
    {
        return $this->belongsTo('App\ErpUsSheet');
    }

}
