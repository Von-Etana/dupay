<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\Visitable;

class RolePermission extends Model
{
    use HasFactory;
    use Visitable;
    public $guarded=[];

    public function role()
    {
        return $this->belongsTo(Role::class,'role_id','id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class,'permission_id','id');
    }
}
