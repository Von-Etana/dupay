<?php

namespace App\Models\Telecom;

use App\Models\ServiceType;
use App\Models\UserLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    use HasFactory;

    protected $guarded = [''];


    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'name' => 'required',
                'description' => 'required',
                'amount' => '',
                'start_at' => 'required',
                'end_at' => 'required',
                'times' => 'required',
                'min_amount' => 'required',
                'min_amount_type' => 'required',
                'wallet_id' => 'required',
                'user_level_id' => 'required',
                'service_type_id' => 'required',
                'platform' => 'required',
                'status'  => 'required',
            ],
            $merge);
    }


    public  function column()
    {
        $userLevel = UserLevel::whereStatus("1")->get();
        $serviceType = ServiceType::whereStatus("1")->get();
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
        $platformOption  =
        [
            [
                "name" => "All",
                "id" => "1"
            ],
            [
                "name" => "Website",
                "id" => "2"
            ],
            [
                "name" => "Mobile App",
                "id" => "3"
            ],
        ];
        $walletOption  =
        [
            [
                "name" => "Main Wallet",
                "id" => "1"
            ],
            [
                "name" => "Commission Wallet",
                "id" => "2"
            ],
            [
                "name" => "Bonus Wallet",
                "id" => "3"
            ]
        ];

        $min_amount_type  =
        [
            [
                "name" => "Wallet",
                "id" => "1"
            ],
            [
                "name" => "Deposit",
                "id" => "2"
            ],
            [
                "name" => "Transaction",
                "id" => "3"
            ]
        ];

        $column = [
            "name" => [
                "name" => "Title",
                "description" => "Enter Promo Title",
                "column_name" => "name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "description" => [
                "name" => "description",
                "description" => "Enter description",
                "column_name" => "description",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "amount" => [
                "name" => "amount",
                "description" => "Enter amount",
                "column_name" => "amount",
                "input_type" => "0",
                "value" => "",
                "required" => "",
            ],
            "start_at" => [
                "name" => "start_at",
                "description" => "Enter start at",
                "column_name" => "start_at",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                //"class" => "clockpicker",
                //"inputClass" => "datepicker-default"
            ],
            "end_at" => [
                "name" => "end_at",
                "description" => "Enter end at",
                "column_name" => "end_at",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                //"class" => "clockpicker",
                //"inputClass" => "datepicker-default"
            ],

            "times" => [
                "name" => "times",
                "description" => "Number of times user can use the promo",
                "column_name" => "times",
                "input_type" => "0",
                "value" => "",
                "required" => "",
            ],
            "min_amount" => [
                "name" => "Min amount",
                "description" => "Enter minimum amount",
                "column_name" => "min_amount",
                "input_type" => "0",
                "value" => "",
                "required" => "",
            ],
            "min_amount_type" => [
                "name" => "Min amount type",
                "description" => "Please Select",
                "column_name" => "min_amount_type",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "option" => $min_amount_type,
            ],
            "wallet_id" => [
                "name" => "Wallet",
                "description" => "Please Select",
                "column_name" => "wallet_id",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "option" => $walletOption,
            ],
            "user_level_id" => [
                "name" => "User Level",
                "description" => "Please Select",
                "column_name" => "user_level_id",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "option" => $userLevel,
            ],
            "platform" => [
                "name" => "Platform",
                "description" => "Please Select",
                "column_name" => "platform",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $platformOption,
            ],
            "service_type_id" => [
                "name" => "Service Type",
                "description" => "Please Select",
                "column_name" => "service_type_id",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $serviceType,
                "dataColumn" => ["id" => "id", "name" => "title"]
            ],
            "status" => [
                "name" => "status",
                "description" => "Please Select Network Status",
                "column_name" => "status",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $option,
            ]

        ];
        return json_decode(json_encode($column));
    }
}
