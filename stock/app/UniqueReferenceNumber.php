<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UniqueReferenceNumber extends Model
{
    protected $table = 'unique_referencenumber';

    protected $fillable = [
        'ref_no',
        'batch_id',
        'supplier_id',
        'supplier_invoice_id',
        'original_qty',
        'remqty',
        'is_old',
    ];

    /**
     * Relation: Has many products
     */
    public function products()
    {
        return $this->hasMany(UniqueReferenceNumberProduct::class, 'unique_referencenumber_id');
    }

    /**
     * Optional: Belongs to Batch
     */
    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    /**
     * Optional: Belongs to Supplier Invoice
     */
    public function supplierInvoice()
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    /**
     * Supplier master (name via suppliers.c_name when needed).
     */
    public function supplier()
    {
        return $this->belongsTo(supplier::class, 'supplier_id');
    }
}