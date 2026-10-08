<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class employee extends Model
{

	protected $connection = 'mysql2';
    protected $table = 'employees';
    protected $fillable =
    [
        'given_name'
    ];

   
}
