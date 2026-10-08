<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\product;
use App\consumable;

class WfConsumable extends Model
{
   protected $table = 'wf_consumable';
   public $timestamps = false;


    protected $fillable = [
        'product_id',
        'consumables_id',
        'consumable_id',
        'unit_type_id',
        'unit_type_name',
        'qty',
        'rate',
    ];

        public function product()
    {
        return $this->belongsTo(product::class, 'product_id');
    }

    public function consumable()
    {
        return $this->belongsTo(consumable::class, 'consumables_id');
    }

    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id');
    }
}
