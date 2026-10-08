<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\ErpSheet;

class ErpHistory extends Model
{
    protected $table = 'erp_history';
    protected $fillable =
    [
        'sheet_id',  
        'sku',
        'quantity',
        'type',
        'date',
        'stock',
        'remark',
        'site_access'
    ];

    public function sheet()
    {
        return $this->belongsTo('App\ErpSheet');
    }

}
