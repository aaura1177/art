<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class consumable extends Model
{
    protected $table = 'consumables';

    protected $casts = [
        'container_quantity' => 'float',
        'is_moq' => 'boolean',
        'moq_qty' => 'decimal:2',
    ];

    protected $fillable =
    [
    	'name',
        'description',
        'is_moq',
        'moq_qty',
    	'unit',
    	'rate',
    	'EAN',
    	'supplier',
    	'payment_terms',
    	'quantity',
        'unit_type_id',
    	'monthEndpo_supplier',
        'monthEndpo_buyer',
        'hardware_monthend_po_product',
        'is_container',
        'container_quantity',
        'gst',
        'SKU'
    ];
	
	public function supp()
    {
        return $this->belongsTo('App\supplier','supplier');
    }
    		public function unitType()
{
    return $this->belongsTo('App\UnitType', 'unit_type_id');
}

    /**
     * Product consumable dropdown: "SKU (name)" when SKU is set, otherwise name only.
     */
    public function getSkuOptionLabelAttribute(): string
    {
        $name = (string) ($this->name ?? '');
        $sku = trim((string) ($this->SKU ?? ''));

        return $sku !== '' ? $sku . ' (' . $name . ')' : $name;
    }
}
