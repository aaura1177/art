<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderVersion extends Model
{
    protected $table = 'purchase_order_versions';

    public $timestamps = false;

    protected $fillable = [
        'purchase_order_id',
        'pono',
        'version',
        'snapshot_json',
        'before_snapshot_json',
        'changed_fields_json',
        'change_summary',
        'changed_by',
        'changed_by_label',
        'supplier_accepted_at',
        'supplier_accepted_by',
        'supplier_accepted_by_label',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'supplier_accepted_at' => 'datetime',
    ];

    public function getSnapshotAttribute(): array
    {
        $decoded = json_decode((string) $this->snapshot_json, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function getBeforeSnapshotAttribute(): array
    {
        $decoded = json_decode((string) $this->before_snapshot_json, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function getChangedFieldsAttribute(): array
    {
        $decoded = json_decode((string) $this->changed_fields_json, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function isAccepted(): bool
    {
        return $this->supplier_accepted_at !== null;
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(purchaseOrder::class, 'purchase_order_id');
    }
}
