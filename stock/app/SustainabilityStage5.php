<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class SustainabilityStage5 extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'sustainability_stage5';
    protected $guarded = [];

    public function portInfo()
    {
        return $this->belongsTo(Port::class, 'port_id');
    }
}
