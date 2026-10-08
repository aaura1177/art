<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SupplierProductPriceLog extends Model
{
    protected $table = 'supplier_product_price_logs';

    public $timestamps = false;

    protected $fillable = [
        'supplier_product_id',
        'product_id',
        'supplier_id',
        'event_type',
        'source',
        'snapshot_json',
        'changed_fields_json',
        'change_summary',
        'changed_by',
        'changed_by_label',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function getSnapshotAttribute(): array
    {
        $decoded = json_decode((string) $this->snapshot_json, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function getChangedFieldsAttribute(): array
    {
        $decoded = json_decode((string) $this->changed_fields_json, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function product()
    {
        return $this->belongsTo(product::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(supplier::class, 'supplier_id');
    }
}
