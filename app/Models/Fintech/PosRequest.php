<?php

namespace App\Models\Fintech;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosRequest extends Model
{
    use HasFactory;
    protected $guarded = [''];

    public function user()
    {
     return $this->belongsTo(User::class);
    }


    public  function column()
    {
        $option  =
            [
                [
                    "name" => "Verified",
                    "id" => "1"
                ],
                [
                    "name" => "Pending",
                    "id" => "0"
                ],
                [
                    "name" => "Unverified",
                    "id" => "2"
                ]
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
            "business_address" => [
                "name" => "Business Address",
                "description" => "Enter Business Address",
                "column_name" => "business_address",
                "input_type" => "0",
                "value" => "",
                "required" => "required",

            ],
            "number_of_terminal" => [
                "name" => "Number of Terminal",
                "description" => "Enter Number of Terminal",
                "column_name" => "number_of_terminal",
                "input_type" => "0",
                "required" => "required",
                "value" => "",

            ],
            "transaction_volume" => [
                "name" => "Transaction Volume",
                "description" => "Enter Transaction Volume",
                "column_name" => "transaction_volume",
                "input_type" => "0",
                "editable" => true,
                "value" => "",
                "required" => "required",
            ],
            "file_one" => [
                "name" => "Office Image",
                "description" => "Upload Office Image",
                "column_name" => "file_one",
                "input_type" => "7",
                "value" => "",
                "required" => "required",
                "create" => false,
                "edit" => false
            ],
            "comment" => [
                "name" => "Comment",
                "description" => "Enter comment",
                "column_name" => "comment",
                "input_type" => "0",
                "value" => "",
                "editable" => true,
                "required" => "required",
                "view" => false,
            ],

            "date" => [
                "name" => "Date",
                "description" => "Enter Date",
                "column_name" => "created_at",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "create" => false,
                "edit" => false
            ],
            "status" => [
                "name" => "Status",
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
