<?php
namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class PermissionManager extends Model
{
    use HasFactory;
    protected $table = 'permissions';
    protected $guarded = [];
}