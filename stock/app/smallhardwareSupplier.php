<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class smallhardwareSupplier extends Model
{
    protected $table = 'smallhardware_suppliers';
    protected $fillable =
    [
    	'name',
    	'c_name'
    ];
}
