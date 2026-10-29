<?php

namespace App\Models\Telecom;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualAccount extends Model
{
    use HasFactory;
    public $guarded=[];

    public function user()
    {
     return $this->belongsTo(User::class);
    }

    public function type(){
        return $this->belongsTo(VirtualAccountType::class, "virtual_account_type_id");
    }


    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'name'  => 'required',
                'short_name'  => 'required',
                'code'     => 'required',
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
                ],

            ]
        ;
        $column = [
            "username" => [
                "name" => "User",
                "description" => "Please Select",
                "column_name" => "username",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "model" => "user->profile->username",
                "link" => route('admin.user.show', '@@this@@')
            ],
            "account_name" => [
                "name" => "Name",
                "description" => "Account Name",
                "column_name" => "account_name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",

            ],
            "account_number" => [
                "name" => "Account Number",
                "description" => "Enter  Account Number",
                "column_name" => "account_number",
                "input_type" => "0",
                "required" => "required",
                "value" => "",

            ],
            "bank_name" => [
                "name" => "Bank Name",
                "description" => "Bank Name",
                "column_name" => "bank_name",
                "input_type" => "0",
                "editable" => true,
                "value" => "",
                "required" => "required",
            ],


            "status" => [
                "name" => "status",
                "description" => "Select Status",
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
