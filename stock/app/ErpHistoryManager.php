<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\ErpSheet;

class ErpHistoryManager extends Model
{
    protected $table = 'erp_history_manager';
    protected $fillable =
    [
        'sheet_id',  
        'sku',
        'quantity',
        'type',
        'date',
        'stock',
        'site_access',
    ];

    public function sheet()
    {
        return $this->belongsTo('App\ErpSheetManager');
    }

}
