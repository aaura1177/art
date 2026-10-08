<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierCertificate extends Model
{
    use HasFactory;
  
    protected $fillable = ['user_id','name','file_path'];

    // Define relationship with User model (if needed)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
