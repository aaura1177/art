<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogisticsEmission extends Model
{
    use HasFactory;
    protected $fillable = ['logistics_partner_id', 'number_of_containers', 'month', 'calculated_emission'];

    public function logisticsPartner()
    {
        return $this->belongsTo(LogisticPartener::class);
    }
}
