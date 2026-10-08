<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employeedetail extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'vehicle_type', 'fuel_type', 'distance_from_office'];
    public function expenses()
{
    return $this->hasMany(EmployeeExpense::class);
}

}
