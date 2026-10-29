<?php

namespace App\Models\Telecom;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualAccountType extends Model
{
    use HasFactory;
    public $guarded=[];
    public function virtualAccount()
    {
     return $this->hasMany(VirtualAccount::class);
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
                ]
            ]
        ;
        $column = [
            "name" => [
                "name" => "name",
                "description" => "Enter name",
                "column_name" => "name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "short_name" => [
                "name" => "Short Name",
                "description" => "Enter shortname,",
                "column_name" => "short_name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "code" => [
                "name" => "Code",
                "description" => "Enter code",
                "column_name" => "code",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],

            "status" => [
                "name" => "status",
                "description" => "Please Select Status",
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
