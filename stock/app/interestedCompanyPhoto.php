<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class interestedCompanyPhoto extends Model
{
    protected $table = 'interested_company_photos';
    protected $fillable =
    [
        'interested_company_id',
        'photo'
    ]; 
}