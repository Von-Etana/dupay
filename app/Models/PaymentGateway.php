<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;
    public $guarded=[];

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'name'  => 'required|unique:payment_gateways,name' . ($id ? ",$id" : ''),
                'value_key'     => '',
                'other' =>  '',
                'rate' =>  '',
                'charge' =>  '',
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
        $column = [
            "name" => [
                "name" => "Name",
                "description" => "E.g Paystack, Monnify",
                "column_name" => "name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "value_key" => [
                "name" => "Gateway Properties",
                "description" => "Enter Gateway Properties",
                "column_name" => "value_key",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "view" => false,
                "addMore" => [
                    "available" =>  true,
                    "hasValue" => true,
                    "toJson" => true,
                    "valueColumn" => "value_key_value",
                    "valueName" => "Gateway Props Value",
                    "valueDescription" => "enter Gateway Props Value",
                ],
            ],
            "other" => [
                "name" => "Other Details",
                "description" => "Enter other details",
                "column_name" => "other",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "view" => false
            ],

            "rate" => [
                "name" => "Rate",
                "description" => "Enter Rate",
                "column_name" => "rate",
                "input_type" => "0",
                "value" => "",
                "required" => "",
            ],
            "charge" => [
                "name" => "Charge",
                "description" => "Enter Charge",
                "column_name" => "charge",
                "input_type" => "0",
                "value" => "",
                "required" => "",
            ],
            "status" => [
                "name" => "status",
                "description" => "Please Select",
                "column_name" => "status",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $option,
            ]

        ];
        return json_decode(json_encode($column));
    }

    public function payment(){
        return $this->hasMany(Payment::class);
    }
}
