<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\Visitable;

class BasicSetting extends Model
{
    use HasFactory;
    use Visitable;
    public $guarded=[];

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'name'  => 'required',
                'value'  => 'required'
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
            "description" => [
                "name" => "Description",
                "description" => "Enter description",
                "column_name" => "description",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
                "view" => false
            ],
            "input_type" => [
                "name" => "Input Type",
                "description" => "0,1,2,3,4,5,6,7",
                "column_name" => "input_type",
                "input_type" => "0",
                "required" => "",
                "value" => "",
                "editable" =>true,
                "view" => false
            ],
            "column_name" => [
                "name" => "Column Name",
                "description" => "Enter column name",
                "column_name" => "column_name",
                "input_type" => "0",
                "required" => "",
                "value" => "",
                "editable" =>true,

            ],
            "rule" => [
                "name" => "Rule",
                "description" => "Enter rule",
                "column_name" => "rule",
                "input_type" => "0",
                "required" => "",
                "value" => "",
                "editable" =>true,
                "view" => false
            ],
            "model" => [
                "name" => "Model",
                "description" => "Enter model",
                "column_name" => "model",
                "input_type" => "0",
                "required" => "",
                "value" => "",
                "editable" =>true,

            ],"value" => [
                "name" => "Value",
                "description" => "Enter Value",
                "column_name" => "value",
                "input_type" => "0",
                "required" => "",
                "value" => "",
                "editable" =>true,
            ],
            "input_option" => [
                "name" => "Input Option",
                "description" => "Enter input option",
                "column_name" => "input_option",
                "input_type" => "0",
                "required" => "",
                "value" => "",
                "editable" =>true,

            ],
        ];
        return json_decode(json_encode($column));
    }
}
