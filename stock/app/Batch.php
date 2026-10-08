<?php
 
namespace App;
 
use Illuminate\Database\Eloquent\Model;
 
class Batch extends Model
{
    protected $table = 'batch';
 
    protected $fillable = [
        'batch_no',
        'quantity',
        'supplier_id',
        'date',
    ];

        public function supplier()
    {
        return $this->belongsTo(supplier::class, 'supplier_id');
    }

}