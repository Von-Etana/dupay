<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\Visitable;
class ShortCode extends Model
{
    use HasFactory;
    use Visitable;
    public $guarded=[];

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [

                'name' => 'required',
                'value'  => 'required',
                'description'  => 'required',
                'model' => '',
                'status'  => 'required',
            ],
            $merge);
    }


    public  function column()
    {
        $module = Module::all();
        $option  =
            [
                [
                    "name" => "Available",
                    "id" => "1"
                ],
                [
                    "name" => "Unavailable",
                    "id" => "0"
                ]
            ]
        ;

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
            "value" => [
                "name" => "Value",
                "description" => "Enter value",
                "column_name" => "value",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "description" => [
                "name" => "Description",
                "description" => "Enter description",
                "column_name" => "description",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "model" => [
                "name" => "Module",
                "description" => "Please Select",
                "column_name" => "model",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "model" => "module->id",
                "option" => $module,

                "dataColumn" => ["id" => "id", "name" => "name"]
            ],
            "status" => [
                "name" => "status",
                "description" => "Please Select ",
                "column_name" => "status",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "use_switch" => true,
                "option" => $option,
            ]


        ];
        return json_decode(json_encode($column));
    }
}
