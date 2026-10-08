<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContainersAllocation extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'containers_allocations';   
    protected $guarded = [];

    public function containerAllocationDetails()
    {
        return $this->hasMany(ContainersAllocationDetail::class, 'container_allocation_id');
    }
    public function containerAllocationItems()
    {
        return $this->hasMany(ContainersAllocationItem::class, 'container_allocation_id');
    }
}
