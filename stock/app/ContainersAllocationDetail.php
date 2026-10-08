<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContainersAllocationDetail extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'containers_allocation_details';   
    protected $guarded = [];

    public function containerAllocationInfo()
    {
        return $this->belongsTo(ContainersAllocation::class, 'container_allocation_id');
    }

    public function supplierInfo()
    {
        return $this->belongsTo(supplier::class, 'supplier_id');
    }
    public function productInfo()
    {
        return $this->belongsTo(product::class, 'product_id');
    }
    public function purchaseOrderInfo()
    {
        return $this->belongsTo(purchaseOrder::class, 'purchase_order_id');
    }


    
}
