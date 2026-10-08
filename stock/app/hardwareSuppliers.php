<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class hardwareSuppliers extends Model
{
    protected $table = 'hardware_suppliers';
    protected $fillable =
    [
    	'name'
    ];
}
