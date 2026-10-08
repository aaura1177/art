<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ErpSheet extends Model
{
    protected $fillable =
    [
        'name',  
        'date',  
        'type',
        'site_access',
        'erp_sheet_type',
        'reverted'
    ];

}
