<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelExpense extends Model
{
    use HasFactory;
    protected $table="travel_expenses";
    protected $fillable = ['departure', 'destination', 'date', 'amount', 'attachment','distance','vehicle_type'];
}
