<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class cartonSwapping extends Model
{
    protected $table = 'carton_swapping';

    protected $fillable = [
        'invoice_no',
        'packaging_out_id',
        'packaging_in_id',
        'carton_type',
        'quantity',
    ];

    public function packagingOut()
    {
        return $this->belongsTo('App\packaging', 'packaging_out_id');
    }

    public function packagingIn()
    {
        return $this->belongsTo('App\packaging', 'packaging_in_id');
    }
}
