<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $fillable =
    [
		'user_id',
		'notification',
		'is_read'
    ];
	
	public function user()
    {
        return $this->belongsTo('App\User');
    }

}
