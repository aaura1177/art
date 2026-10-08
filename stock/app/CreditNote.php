<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    protected $table = 'credit_notes';
    protected $fillable =
    [
        'num',
        'invoice_id',
        'total_quantity',
        'amount',
    	'total_amount',
    	'total_tax'
    ];

    public function invoice()
    {
        return $this->belongsTo('App\invoice');
    }
}