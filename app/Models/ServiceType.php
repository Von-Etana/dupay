<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    use HasFactory;

    protected $guarded = [''];


    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'title'  => 'required|unique:service_types,title' . ($id ? ",$id" : ''),
                'sub_title' => 'required',
                'icon' => 'required',
                'model' => 'required',
                'route' => '',
                'admin_route' => '',
                'description' => 'required',
                'route_parameter' => '',
                'order_id' => 'required|unique:service_types,order_id' . ($id ? ",$id" : ''),
                'visible_at_home_page' => 'required',
                'admin_side_bar' => 'required',
                'user_side_bar' => 'required',
                'status'  => 'required',
            ],
            $merge);
    }


    public  function column()
    {
        $allServiceType = ServiceType::whereStatus("1")->get();
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
            ];
        $optionYes  =
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
        $column = [
            "title" => [
                "name" => "Page Title",
                "description" => "Enter Title",
                "column_name" => "title",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "sub_title" => [
                "name" => "Page Sub Title",
                "description" => "Enter Sub Title",
                "column_name" => "sub_title",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "icon" => [
                "name" => "Icon",
                "description" => "Enter Icon",
                "column_name" => "icon",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "editable" => true
            ],
            "description" => [
                "name" => "Description",
                "description" => "Enter description",
                "column_name" => "description",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "editable" => true
            ],
            "model" => [
                "name" => "Model",
                "description" => "E.g User",
                "column_name" => "model",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "route" => [
                "name" => "Route",
                "description" => "Enter Route",
                "column_name" => "route",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "editable" => true
            ],
            "admin_route" => [
                "name" => "Admin Route",
                "description" => "Enter Admin Route",
                "column_name" => "admin_route",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "editable" => true
            ],
            "route_parameter" => [
                "name" => "Route Parameter",
                "description" => "Enter Route Parameter seperated by comma",
                "column_name" => "route_parameter",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "editable" => true
            ],
            "order_id" => [
                "name" => "Order Number",
                "description" => "Enter Order Number",
                "column_name" => "order_id",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "editable" => true
            ],
            "visible_at_home_page" => [
                "name" => "Show in Homepage",
                "description" => "Please Select",
                "column_name" => "visible_at_home_page",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $optionYes,
                "use_switch" => true
            ],
            "enable_main_wallet" => [
                "name" => "Enable Main Wallet",
                "description" => "Please Select",
                "column_name" => "enable_main_wallet",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $optionYes,
                "use_switch" => true
            ],
            "enable_bonus_wallet" => [
                "name" => "Enable Bonus Wallet",
                "description" => "Please Select",
                "column_name" => "enable_bonus_wallet",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $optionYes,
                "use_switch" => true
            ],
            "enable_commission_wallet" => [
                "name" => "Enable Commission Wallet",
                "description" => "Please Select",
                "column_name" => "enable_commission_wallet",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $optionYes,
                "use_switch" => true
            ],
            "admin_side_bar" => [
                "name" => "Show in Admin",
                "description" => "Please Select",
                "column_name" => "admin_side_bar",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $optionYes,
                "use_switch" => true
            ],
            "user_side_bar" => [
                "name" => "Show in User",
                "description" => "Please Select",
                "column_name" => "user_side_bar",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $optionYes,
                "use_switch" => true
            ],

            "status" => [
                "name" => "status",
                "description" => "Please Select ",
                "column_name" => "status",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $option,
                "use_switch" => true
            ]

        ];
        return json_decode(json_encode($column));
    }



}
