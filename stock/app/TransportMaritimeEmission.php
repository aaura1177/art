<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportMaritimeEmission extends Model
{
    use HasFactory;
    protected $table = 'transport_maritime_emissions';

    protected $fillable = [
        'route',
        'distance_km',
        'total_emissions',
        'month',
    ];

    // Method to calculate emissions
    
}
