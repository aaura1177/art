<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class SustainabilityStage1 extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'sustainability_stage1';
    protected $guarded = [];

    public function supplierInfo()
    {
        return $this->belongsTo(supplier::class,'supplier_id','id');
    }
}
