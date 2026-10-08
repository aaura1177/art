<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ErpEuSheetManager extends Model
{
    protected $table = 'erp_eu_sheets_manager';
    protected $fillable =
    [
        'name',  
        'date',  
        'type'
    ];

}
