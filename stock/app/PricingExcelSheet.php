<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PricingExcelSheet  extends Model
{
    protected $table = 'pricing_excel_sheet'; 

    protected $fillable = [
        'product_sku',
        'buying_cost',
    ];
     public $timestamps = true;
}
