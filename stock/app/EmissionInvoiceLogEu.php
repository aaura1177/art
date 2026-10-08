<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmissionInvoiceLogEu extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = "emission_invoice_logs_eu";
}
