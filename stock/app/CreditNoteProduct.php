<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CreditNoteProduct extends Model
{
    protected $table = 'credit_note_products';
    protected $fillable =
    [
        'credit_note_id',
        'product_id',
        'rate',
    	'tax',
    	'quantity',
        'amount',
          'ref_source_snapshot',
    ];

        protected $casts = [
        'ref_source_snapshot' => 'array',
    ];

    public function credit_note()
    {
        return $this->belongsTo('App\CreditNote');
    }

    public function product()
    {
        return $this->belongsTo('App\product');
    }
}