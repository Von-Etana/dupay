<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\Visitable;

class Profile extends Model
{
    use HasFactory;
    use Visitable;

    protected $fillable = [
        'account_type',
        'username',
        'first_name',
        'last_name',
        'profile_img',
        'profile_bg',
        'commission',
        'ref_id',
        'api_key',
        'user_key',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function getProfileImage(){
        if($this->profile_img == null){
            return url('/')."/assets/images/avatar/1.png";
        }
        return url('/')."/storage/".$this->profile_img;
    }

    public function getName(){
        return $this->first_name." ".$this->last_name;
    }

}
