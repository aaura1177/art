<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SupplierTerm extends Model
{
    public const SINGLETON_ID = 1;

    protected $table = 'supplier_terms';

    protected $fillable = [
        'title',
        'body',
        'updated_by_user_id',
    ];

    public static function singleton(): self
    {
        $row = static::query()->where('id', self::SINGLETON_ID)->first();
        if ($row) {
            return $row;
        }

        $row = new static();
        $row->id = self::SINGLETON_ID;
        $row->title = 'Supplier terms and conditions';
        $row->body = '';
        $row->updated_by_user_id = null;
        $row->save();

        return $row;
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
