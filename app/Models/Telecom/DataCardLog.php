<?php

namespace App\Models\Telecom;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataCardLog extends Model
{
    use HasFactory;

    public $guarded=[];

    public function user(){
        return $this->belongsTo(User::class);
    }


    public function transaction(){
        return $this->hasOne(Transaction::class,  "reference");
    }


    public  function column()
    {
       
        $column = [
            "username" => [
                "name" => "User",
                "description" => "Please Select",
                "column_name" => "username",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "model" => "user->profile->username",
                "link" => route('admin.user.show', '@@this@@')
            ],
            "pin" => [
                "name" => "Pin",
                "description" => "Enter Pin",
                "column_name" => "pin",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "name" => [
                "name" => "Name on card",
                "description" => "Enter Name on card",
                "column_name" => "name",
                "input_type" => "0",
                "required" => "required",
                "value" => "",

            ],
            "reference" => [
                "name" => "Reference",
                "description" => "Enter reference",
                "column_name" => "reference",
                "input_type" => "0",
                "value" => "",
                "editable" => true,
                "required" => "required"
            ],
            "date" => [
                "name" => "Date",
                "description" => "Enter Date",
                "column_name" => "created_at",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "create" => false,
                "edit" => false
            ],
        ];
        return json_decode(json_encode($column));
    }


}
