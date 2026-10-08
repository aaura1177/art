<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class courier extends Model
{
    protected $table = 'courier';
    protected $fillable =
    [
    	'name',
    	'rate',
		'fixed_rate_weight',
		'rate_per_kg',
		'fuel_charge_percent',
		'country',
		'is_default',
		'custom_condition',
    ];

    // Included in JSON (e.g. check_courier_country) so pricing JS can read bands.
    protected $appends = ['weight_rate_tiers'];

    public function weightRateTiers()
    {
        return $this->hasMany(CourierWeightRateTier::class, 'courier_id')
            ->where('is_active', 1)
            ->orderBy('weight_from');
    }

    /**
     * Active weight bands ({from, to, rate}); falls back to the legacy
     * fixed_rate_weight / rate_per_kg pair as a single open-ended band.
     */
    public function getWeightRateTiersAttribute(): array
    {
        return \App\Support\CourierWeightRateBands::forCourier($this);
    }
}
