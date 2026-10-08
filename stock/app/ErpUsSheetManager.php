<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ErpUsSheetManager extends Model
{
    protected $table = 'erp_us_sheets_manager';
    protected $fillable =
    [
        'name',  
        'date',  
        'type'
    ];

}
