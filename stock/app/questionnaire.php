<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class questionnaire extends Model
{
    protected $table = 'questionnaire';
    protected $fillable =
    [
    	'full_name',
    	'designation',
    	'work_location'
    ];

    public function smallhardwares()
    {
        return $this->belongsTo('App\smallhardwares', 'small_hardware_id', 'id');
    }
}
