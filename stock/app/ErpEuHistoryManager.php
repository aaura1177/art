<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ErpEuHistoryManager extends Model
{
    protected $table = 'erp_eu_history_manager';
    protected $fillable =
    [
        'sheet_id',  
        'sku',
        'quantity',
        'type',
        'date',
        'stock'
    ];

    public function sheet()
    {
        return $this->belongsTo('App\ErpEuSheetManager');
    }

}
