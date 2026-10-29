<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteBank extends Model
{
    use HasFactory;
    public $guarded=[];

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'bank_name' => 'required',
                'account_name' => 'required',
                'account_number' => 'required',
                'status' => 'required',
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
            "bank_name" => [
                "name" => "Bank Name",
                "description" => "Enter Bank Name",
                "column_name" => "bank_name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "account_name" => [
                "name" => "Account Name",
                "description" => "Enter Account Name",
                "column_name" => "account_name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "account_number" => [
                "name" => "Account Number",
                "description" => "Enter Account Number",
                "column_name" => "account_number",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
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

}
