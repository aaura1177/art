<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WholesaleShipment extends Model
{
    use SoftDeletes;

    protected $table = 'wholesale_shipments';

    protected $fillable = [
        'buyer_orderno',
        'planned_date',
        'delivery_date',
        'status',
        'excel_original_name',
        'excel_stored_path',
        'uploaded_by',
        'uploaded_at',
        'last_reminder_at',
        'reminder_count',
        'next_reminder_at',
        'notes',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'delivery_date' => 'date',
        'uploaded_at' => 'datetime',
        'last_reminder_at' => 'datetime',
        'next_reminder_at' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(WholesaleShipmentItem::class, 'wholesale_shipment_id');
    }

    public function allocations()
    {
        return $this->hasMany(WholesaleAllocation::class, 'wholesale_shipment_id');
    }

    public function logs()
    {
        return $this->hasMany(WholesaleShipmentLog::class, 'wholesale_shipment_id')->orderByDesc('id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(purchaseOrder::class, 'wholesale_shipment_id');
    }
}
