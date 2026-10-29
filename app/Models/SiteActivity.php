<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteActivity extends Model
{
    use HasFactory;
    public $guarded=[];

    public function user(){
        return $this->hasOne(Admin::class, "id", "user_id");
    }

    public  function column()
    {
        $column = [
            "username" => [
                "name" => "Username",
                "description" => "Enter Username",
                "column_name" => "username",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
                "model" => "user->username",
                "link" => route('admin.user.show', "user->username")
            ],
            "activity" => [
                "name" => "Activity",
                "description" => "Enter activity",
                "column_name" => "activity",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "module" => [
                "name" => "Module",
                "description" => "Enter module",
                "column_name" => "module",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "actions" => [
                "name" => "Action",
                "description" => "Enter action",
                "column_name" => "actions",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "ip" => [
                "name" => "IP",
                "description" => "Enter ip",
                "column_name" => "ip",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "browser" => [
                "name" => "Browser",
                "description" => "Enter browser",
                "column_name" => "browser",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
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
