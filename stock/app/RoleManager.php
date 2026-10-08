<?php
namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoleManager extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'roles';
    protected $guarded = [];

    public function permissions()
    {
        return $this->hasMany('App\RoleHasPermission','role_id');
    }
}