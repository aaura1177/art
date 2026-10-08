<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchProduct extends Model
{
    use HasFactory;

    protected $table = 'batch_product';

    protected $fillable = [
        'batch_id',
        'product_id',
        'supp_in_no',
        'type',
         'date',
        'quantity',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function product()
    {
        return $this->belongsTo(product::class, 'product_id');
    }
}
