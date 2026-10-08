<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistributionEmission extends Model
{
    use HasFactory;
    protected $fillable = ['no_of_parcels', 'total_emission', 'month'];

}
