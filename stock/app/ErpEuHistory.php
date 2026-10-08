<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\ErpEuSheet;

class ErpEuHistory extends Model
{
    protected $table = 'erp_eu_history';
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
        return $this->belongsTo('App\ErpEuSheet');
    }

}
