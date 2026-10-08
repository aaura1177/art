<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\product;
use App\consumable;

class ProductCarton extends Model
{
   protected $table = 'product_carton';

    protected $fillable = [
        'product_id',
        'packaging_id',
        'quantity1',
        'quantity2',
        
    ];

        public function product()
    {
        return $this->belongsTo(product::class, 'product_id');
    }

   

   


}
