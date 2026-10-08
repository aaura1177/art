<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceProduct extends Model
{
    use HasFactory;

    protected $table = 'service_products';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'service_category_id', 'imageURL', 'hsc_code', 'name', 'gstslab', 'height', 
        'depth', 'remarks', 'quantity'
    ];

    public function serviceCategory()
    {
        return $this->belongsTo(serviceCategories::class, 'service_category_id');
    }
}
