<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class invexport extends Model
{
    protected $table = 'invoice_export';
    protected $fillable =
    [
        'realisation_date',  
        'realisation_fc',
        'rate',
        'bank_reference',
        'invoice_id',
        'fbc'
    ];

    public function invoice()
    {
        return $this->belongsTo('App\invoice');
    }
}
