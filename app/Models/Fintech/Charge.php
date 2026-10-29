<?php

namespace App\Models\Fintech;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Charge extends Model
{
    use HasFactory;
    protected $guarded = [''];
    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'user_id' => 'required',
                'bank_name' => 'required',
                'account_name' => 'required',
                'account_number' => '',
                'verify' => '',
                'default' => '',
                'status' => '',
            ],
            $merge);
    }


    public function user(){
        return $this->belongsTo(User::class);
    }

    public function withdraw(){
        return $this->hasMany(WithdrawalLog::class, "bank_id");
    }

    public  function column()
    {

        $typeOption  =
            [
                [
                    "name" => "Deposit",
                    "id" => "0"
                ],
                [
                    "name" => "Transfer",
                    "id" => "1"
                ],
                [
                    "name" => "Withdraw",
                    "id" => "2"
                ]
            ];
            $modeOption  =
            [
                [
                    "name" => "FLAT",
                    "id" => "0"
                ],
                [
                    "name" => "VAT",
                    "id" => "1"
                ]
            ]
        ;
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
        $defaultOption  =
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
            "amount_from" => [
                "name" => "Amount From",
                "description" => "Enter Amount From",
                "column_name" => "amount_from",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "amount_to" => [
                "name" => "Amount To",
                "description" => "Enter Amount To",
                "column_name" => "amount_to",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "fee" => [
                "name" => "Fee",
                "description" => "Enter Fee",
                "column_name" => "fee",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "mode" => [
                "name" => "Charge Mode",
                "description" => "Please Select",
                "column_name" => "mode",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "option" => $modeOption,
            ],
            "type" => [
                "name" => "Charge Type",
                "description" => "please select",
                "column_name" => "type",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $typeOption,
            ],
            "default" => [
                "name" => "Default",
                "description" => "Please Select",
                "column_name" => "default",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $defaultOption,
            ],
            "status" => [
                "name" => "status",
                "description" => "Please Select Plan Type Status",
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
