<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportLog extends Model
{
    use HasFactory;
    protected $table="transport_logs";
    protected $fillable = [
        'supplier_user_id',
        'distance_travelled',
        'times_transport_occurred',
        'transport_date',
        'vehicle_type'
    ];

    // Assuming you have a User model, define the relationship
    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_user_id');
    }
}
