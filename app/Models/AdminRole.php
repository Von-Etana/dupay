<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\Visitable;
class AdminRole extends Model
{
    use HasFactory;
    use Visitable;

    public $guarded=[];

    public function admin()
    {
        return $this->belongsTo(Admin::class,'admin_id','id')->withTrashed();
    }

    public function role()
    {
        return $this->belongsTo(Role::class,'role_id','id');
    }

}
