<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLevel extends Model
{
    use HasFactory;
    public $guarded=[];


    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'name'  => 'required|min:3|max:12|unique:user_levels,name' . ($id ? ",$id" : ''),
                'amount' => 'required',
                'rank' => 'required|integer|unique:user_levels,rank' . ($id ? ",$id" : ''),
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
        $apiOption  =
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
            "name" => [
                "name" => "Level Name",
                "description" => "E.g End User, Smart Earner, Retailer",
                "column_name" => "name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "rank" => [
                "name" => "Rank",
                "description" => "Rank 1,2,3,4,5,6",
                "column_name" => "rank",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "amount" => [
                "name" => "Amount",
                "description" => "Amount to charge during upgrading",
                "column_name" => "amount",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "wallet_minimum" => [
                "name" => "Wallet Minimum",
                "description" => "Minimum Amount that must remain in user wallet",
                "column_name" => "wallet_minimum",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "daily_transaction_limit" => [
                "name" => "Daily Transaction Limit",
                "description" => "daily transaction limit",
                "column_name" => "daily_transaction_limit",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "verified_daily_transaction_limit" => [
                "name" => "Daily Transaction Limit (KYC Verified)",
                "description" => "Daily limit for users whose wallet KYC is approved. Enter - for no limit.",
                "column_name" => "verified_daily_transaction_limit",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "min_withdrawal" => [
                "name" => "Minimum Withdrawal",
                "description" => "Minimum Withdrawal",
                "column_name" => "min_withdrawal",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "max_withdrawal" => [
                "name" => "Max Withdrawal",
                "description" => "Max Withdrawal",
                "column_name" => "max_withdrawal",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "min_transfer" => [
                "name" => "Minimum Transfer",
                "description" => "Minimum Transfer",
                "column_name" => "min_transfer",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "max_transfer" => [
                "name" => "Max Transfer",
                "description" => "Max Transfer",
                "column_name" => "max_transfer",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],

            "min_transaction" => [
                "name" => "Minimum Transaction",
                "description" => "Minimum Transaction",
                "column_name" => "min_transaction",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "max_transaction" => [
                "name" => "Max Transaction",
                "description" => "Max Transaction",
                "column_name" => "max_transaction",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "deposit_charge" => [
                "name" => "Transfer Charge",
                "description" => "Enter transfer charge",
                "column_name" => "transfer_charge",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "transfer_charge" => [
                "name" => "Transfer Charge",
                "description" => "Enter transfer charge",
                "column_name" => "transfer_charge",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "withdrawal_charge" => [
                "name" => "Withdrawal Charge",
                "description" => "Enter withdrawal charge",
                "column_name" => "withdrawal_charge",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "web_charge" => [
                "name" => "Reduction On Website",
                "description" => "Percentage to reduce on Website Service",
                "column_name" => "web_charge",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "api_charge" => [
                "name" => "Reduction On Api",
                "description" => "% to reduce on Mobile App / Api Service",
                "column_name" => "api_charge",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "mobile_charge" => [
                "name" => "Reduction On Api",
                "description" => "% to reduce on Mobile App / Api Service",
                "column_name" => "api_charge",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "enable_api" => [
                "name" => "Enable Api Service",
                "description" => "Enable the use of Api on External Server",
                "column_name" => "enable_api",
                "input_type" => "4",
                "value" => "",
                "option" => $apiOption,
                "required" => "required",
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

    public function user(){
        return $this->hasMany(User::class);
    }
}
