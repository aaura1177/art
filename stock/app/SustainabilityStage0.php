<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SustainabilityStage0 extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'sustainability_stage0';
    protected $guarded = [];
}
