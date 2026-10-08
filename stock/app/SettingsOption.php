<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SettingsOption extends Model
{
    protected $table = 'settings_option';
    protected $fillable =
    [
    	'setting_key',	
    	'setting_value',
    	
    ];
}
