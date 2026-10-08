<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArtisanEmission extends Model
{
    use HasFactory;
    protected $table="artisan_emissions";
    protected $fillable = [
        'electricity_consumed',
        'factory_electricity',
        'uk_electricity',
        'office_electricity',
        'carbon_emissions',
        'month',
    ];

    // Optionally, if you want to format the date when working with it:
    protected $dates = ['month'];

    /**
     * Accessor to format the 'month' field for display.
     */
    public function getMonthFormattedAttribute()
    {
        return $this->month->format('F Y');
    }
}
