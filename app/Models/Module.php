<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\Visitable;

class Module extends Model
{
    use HasFactory;
    use Visitable;
    public $guarded=[];

    public function permissions()
    {
        return $this->hasMany(Permission::class,'module_id','id');
    }

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'name'  => 'required',
                'module'  => 'required'
            ],
            $merge);
    }


    public  function column()
    {

        $option  =
            [
                [
                    "name" => "Yes",
                    "id" => "1"
                ],
                [
                    "name" => "No",
                    "id" => "0"
                ]
            ];

        $column = [
            "name" => [
                "name" => "Name",
                "description" => "Enter name",
                "column_name" => "name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "module" => [
                "name" => "Module",
                "description" => "Enter Module",
                "column_name" => "module",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "general_view" => [
                "name" => "General View",
                "description" => "Please Select ",
                "column_name" => "general_view",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "option" => $option,
            ]
        ];
        return json_decode(json_encode($column));
    }
}
