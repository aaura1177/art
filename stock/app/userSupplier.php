<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class userSupplier extends Model
{
    protected $table = 'users';
    protected $fillable =
    [
    	'firstname',	
    	'lastname',
    	'email',
    	'role',
    	'email_verified_at',
    	'password',
    	'remember_token',
    	'api_token',
    	'supplier_id'
    	
    ];

    

    public function supplierUser()
    {
        return $this->hasMany('App\supplierUser');
    }

    
}