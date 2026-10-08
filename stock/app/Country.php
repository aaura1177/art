<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'code'];

    public function logisticsPartners()
    {
        return $this->hasMany(LogisticsPartner::class);
    }
}
