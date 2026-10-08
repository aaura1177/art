<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class buyer extends Model
{
    protected $table = 'buyers';
    protected $fillable =
    [
    	'code',
    	'c_name',	
    	'name',
        'tally_name',
        'email',
    	'phone',
    	'address1',
    	'address2',
    	'city',
    	'state',
    	'country',
    	'postcode',
    	'buyertype',
    	'gstno',
    	'state_code',
        'temp_buyer_id',
    ];

    public function tempBuyer()
    {
        return $this->belongsTo('App\tempBuyer', 'temp_buyer_id', 'id');
    }

    public function invoice()
    {
        return $this->hasMany('App\invoice');
    }
	public function invoiceeu()
    {
        return $this->hasMany('App\invoiceeu');
    }
	public function invoiceuk()
    {
        return $this->hasMany('App\invoiceuk');
    }
	public function invoiceus()
    {
        return $this->hasMany('App\invoiceus');
    }
    public function invoicecanada()
    {
        return $this->hasMany('App\invoicecanada');
    }
    public function invoicecalifornia()
    {
        return $this->hasMany('App\invoicecalifornia');
    }
}
