<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class interestedCompany extends Model
{
    protected $table = 'interested_company';
    protected $fillable =
    [
        'company_name',
        'establishment_date',
        'contact_name',
        'email',
        'phone',
        'website_link',
        'location',
        'work_with_companies_name',
        'product_type'
    ]; 
}