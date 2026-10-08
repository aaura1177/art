<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderActivityLog extends Model
{
    protected $table = 'purchase_order_activity_logs';

    public $timestamps = false;

    protected $fillable = [
        'purchase_order_id',
        'pono',
        'event_type',
        'from_value',
        'to_value',
        'summary',
        'changed_by',
        'changed_by_label',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(purchaseOrder::class, 'purchase_order_id');
    }
}
