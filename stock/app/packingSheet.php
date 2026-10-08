<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class packingSheet extends Model
{
    protected $table = 'packingsheet';
    protected $fillable =
    [
        'invoice_id',  
        'totalbox',
        'netwt',
        'quantity',
        'grosswt',
    ];
    
    public function psTable()
    {
        return $this->hasMany('App\psTable');
    }

    public function invoice()
    {
        return $this->belongsTo('App\invoice', 'invoice_id', 'id' );
    }
}
