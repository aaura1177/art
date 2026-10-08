<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class employeeSalary extends Model
{

	protected $connection = 'mysql2';
    protected $table = 'employees_salaries';
    protected $fillable =
    [
        'total'
    ];

   
}
