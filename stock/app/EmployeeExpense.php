<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeExpense extends Model
{
    use HasFactory;
    protected $fillable = [
        'employeedetail_id',
        'days_present',
        'distance_from_office',
        'total_kms',
        'month',
    ];
    public function employeedetail()
{
    return $this->belongsTo(Employeedetail::class);
}

}
