<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CourierSnapshot extends Model
{
    protected $table = 'courier_snapshots';

    public $timestamps = false;

    protected $fillable = [
        'courier_id',
        'courier_name',
        'country',
        'snapshot_json',
        'changed_fields_json',
        'change_summary',
        'changed_by',
        'changed_by_label',
        'event_type',
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
}
