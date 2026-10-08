<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContainersAllocationItem extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'containers_allocation_items';   
    protected $guarded = [];

    public function containerAllocationInfo()
    {
        return $this->belongsTo(ContainersAllocation::class, 'container_allocation_id');
    }


    public function productInfo()
    {
        return $this->belongsTo(product::class, 'product_id');
    }
}
