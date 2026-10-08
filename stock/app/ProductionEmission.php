<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionEmission extends Model
{
    use HasFactory;

    protected $table = "production_emissions";

    protected $fillable = [
        'supplier_user_id',
        'date',
        'percentage', // Replace artisan_products and total_products with percentage
        'electricity_unit',
        'distance',
        'electricity_attachement',
        'eway_attachement',
        'distance_factor',
        'electricity_factor',
        'vehicle_type'
    ];

    /**
     * Get the supplier associated with this production emission.
     */
    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_user_id');
    }
}
