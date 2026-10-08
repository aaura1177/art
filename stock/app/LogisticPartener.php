<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogisticPartener extends Model
{
    use HasFactory;
    protected $table="logistics_partners";
    protected $fillable = ['name', 'country_id', 'carbon_emission_value'];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    public function carbonEmissionRecords()
{
    return $this->hasMany(LogisticsEmission::class);
}
public function invoices()
    {
        // Relate the logistic partner name with the discharge field of the invoices
        return $this->hasMany(Invoice::class, 'discharge', 'name');
    }
}
