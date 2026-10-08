<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CourierWeightRateTier extends Model
{
    protected $table = 'courier_weight_rate_tiers';

    protected $fillable = [
        'courier_id',
        'weight_from',
        'weight_to',
        'rate_per_unit',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'weight_from' => 'float',
        'weight_to' => 'float',
        'rate_per_unit' => 'float',
        'is_active' => 'boolean',
    ];
}
