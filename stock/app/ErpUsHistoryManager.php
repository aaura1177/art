<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ErpUsHistoryManager extends Model
{
    protected $table = 'erp_us_history_manager';
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
        return $this->belongsTo('App\ErpUsSheetManager');
    }

}
