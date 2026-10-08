<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class questionnaireResponse extends Model
{
    protected $table = 'questionnaire_responses';
    protected $fillable =
    [
    	'questionnaire_id',
    	'question_no',
        'most_likely',
    	'least_likely'
    ];

    public function smallhardwares()
    {
        return $this->belongsTo('App\smallhardwares', 'small_hardware_id', 'id');
    }
}
