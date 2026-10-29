<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\Visitable;
class AdminSideBar extends Model
{
    use HasFactory;
    use Visitable;
    public $guarded=[];

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [

                'name' => 'required',
                'icon' => '',
                'image' => '',
                'model' => '',
                'route' => '',
                'order' => '',
                'parent_id' => '',
                'status'  => 'required',

            ],
            $merge);
    }

    public  function column()
    {
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
        $isMenu  =
            [
                [
                    "name" => "Yes",
                    "id" => "1"
                ],
                [
                    "name" => "No",
                    "id" => "0"
                ]
            ]
        ;
        $sideBar = AdminSideBar::all();


        $column = [
            "name" => [
                "name" => "Name",
                "description" => "Enter name",
                "column_name" => "name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable"=> true
            ],
            "other_name" => [
                "name" => "Other Name",
                "description" => "Enter other name",
                "column_name" => "other_name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                'view' => false
            ],
            "icon" => [
                "name" => "Icon",
                "description" => "Enter Icon",
                "column_name" => "icon",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                'view' => false
            ],
            "image" => [
                "name" => "Image",
                "description" => "Enter Image",
                "column_name" => "image",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                'view' => false
            ],
            "model" => [
                "name" => "Model",
                "description" => "Enter model",
                "column_name" => "model",
                "input_type" => "0",
                "value" => "",
                "required" => "",
            ],
            "rule" => [
                "name" => "Rule",
                "description" => "Enter model",
                "column_name" => "rule",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "editable"=> true

            ],
            "route" => [
                "name" => "Route",
                "description" => "Enter route",
                "column_name" => "route",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                'view' => false
            ],
            "route_parameter" => [
                "name" => "Route Param",
                "description" => "Enter route Param",
                "column_name" => "route_parameter",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                'view' => false
            ],
            "use_route" => [
                "name" => "Use Route",
                "description" => "Please Select ",
                "column_name" => "use_route",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $isMenu,
                'view' => false
            ],
            "order" => [
                "name" => "Order Id",
                "description" => "Enter Order Id",
                "column_name" => "order",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "editable"=> true
            ],
            "parent_id" => [
                "name" => "Parent",
                "description" => "Please Select",
                "column_name" => "parent_id",
                "id" => "parent_id",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                //"model" => "type->name",
                "option" => $sideBar,
                "dataColumn" => ["id" => "id", "name" => "name"]
            ],
            "is_menu" => [
                "name" => "Menu",
                "description" => "Please Select ",
                "column_name" => "is_menu",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $isMenu,

            ],
            "can_view" => [
                "name" => "Can View",
                "description" => "Please Select ",
                "column_name" => "can_view",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $isMenu,
                'view' => false
            ],
            "can_add" => [
                "name" => "Can Add",
                "description" => "Please Select ",
                "column_name" => "can_add",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $isMenu,
                'view' => false
            ],
            "status" => [
                "name" => "status",
                "description" => "Please Select ",
                "column_name" => "status",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $option,
            ]
        ];
        return json_decode(json_encode($column));
    }

    public function children(){
        return $this->hasMany(AdminSideBar::class, 'parent_id');
    }
}
