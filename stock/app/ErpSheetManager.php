<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ErpSheetManager extends Model
{
    protected $table = 'erp_sheets_manager';
    protected $fillable =
    [
        'name',  
        'date',  
        'type'
    ];

}
