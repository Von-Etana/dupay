<?php

namespace App\Models\Fintech;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
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
            ] ;

            $Yesoption  =
            [
                [
                    "name" => "Yes",
                    "id" => "1"
                ],
                [
                    "name" => "No",
                    "id" => "0"
                ]
            ] ;

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
            "bank_name" => [
                "name" => "Bank",
                "description" => "Enter BankName",
                "column_name" => "bank_name",
                "input_type" => "0",
                "value" => "",
                "editable" => true,
                "required" => "required",

            ],
            "account_name" => [
                "name" => "Account Name",
                "description" => "Enter account name",
                "column_name" => "account_name",
                "input_type" => "0",
                "required" => "required",
                "editable" => true,
                "value" => "",

            ],
            "account_number" => [
                "name" => "Account Number",
                "description" => "Account Number",
                "column_name" => "account_name",
                "input_type" => "0",
                "editable" => true,
                "value" => "",
                "required" => "required",
            ],
            "default" => [
                "name" => "Default",
                "description" => "Select Status",
                "column_name" => "default",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $Yesoption,
            ],
            "verify" => [
                "name" => "Verify",
                "description" => "Select Status",
                "column_name" => "verify",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $Yesoption,
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
