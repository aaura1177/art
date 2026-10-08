<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\SoftDeletes;

class DischargePort extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'discharge_ports';
    protected $guarded = [];
}
