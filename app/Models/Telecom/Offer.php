<?php

namespace App\Models\Telecom;

use App\Models\UserLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;
    public $guarded=[];

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'service_type'  => 'required',
                'plan_id' => 'required',
                'user_level_id' => 'required',
                'amount' => 'required',
                'status'  => 'required',
            ],
            $merge);
    }

    public  function column()
    {

        $userLevel = UserLevel::whereStatus("1")->get();
        $serviceOption  =
        [
            [
                "name" => "Data",
                "id" => "DATA"
            ],
            [
                "name" => "Airtime",
                "id" => "AIRTIME"
            ],
            [
                "name" => "Cable Subscription",
                "id" => "CABLE"
            ],
            [
                "name" => "Bill Payment",
                "id" => "BILL"
            ]
        ];
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
            "service_type" => [
                "name" => "Service Type",
                "description" => "Please Select ",
                "column_name" => "service_type",
                "input_type" => "4",
                "required" => "required",
                "ajaxClass" => "offerServiceTypeData",
                "disabled" => "disabled",
                "value" => "",
                "option" => $serviceOption,
            ],
            "plan_name" => [
                "name" => "Plan",
                "description" => "Plan",
                "column_name" => "plan_name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => false,
                "create" => false,
                "edit" => false
            ],
            "plan_id" => [
                "name" => "Type",
                "description" => "Please Select Type",
                "column_name" => "plan_id",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "disabled" => "disabled",
                "ajaxClass" => "offerPlanType",
                "option" => [],
                "view" => false,
                "edit" => false
            ],
            "user_level_id" => [
                "name" => "User Level",
                "description" => "Please select",
                "column_name" => "user_level_id",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $userLevel,
            ],
            "amount" => [
                "name" => "Amount",
                "description" => "Amount",
                "column_name" => "amount",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "status" => [
                "name" => "status",
                "description" => "Please Select",
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





    public function modifyColumnMethod($data,$column_name, $value){
        if($column_name == "plan_name"){
            if($data->service_type == "DATA"){
             $item =   Data::Where('id', $data->plan_id)->first();
             if($item){
                return "$item->network $item->type $item->name - N$item->price";
             }
            }else if($data->service_type == "AIRTIME"){
                $item =   Airtime::Where('id', $data->plan_id)->first();
                if($item){
                   return "$item->network $item->type $item->name - $item->price%";
                }
            }else if($data->service_type == "CABLE"){
                $item =   Cable::Where('id', $data->plan_id)->first();
                if($item){
                   return "{$item->cableType->name}  $item->name - N$item->amount";
                }
            }
            else if($data->service_type == "BILL"){
                $item =   Bill::Where('id', $data->plan_id)->first();
                if($item){
                   return "$item->name - $item->price_rate%";
                }
            }
        }

        return $value;
    }


}


// DELETE FROM `transactions`
// WHERE (CAST(created_at AS DATETIME)
// BETWEEN CAST('2023-07-01 10:15:55' AS DATETIME) AND CAST('2023-07-30 14:15:55' AS DATETIME));
