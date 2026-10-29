<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteTheme extends Model
{
    use HasFactory;
    public $guarded=[];

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'typography' => 'required',
                'version' => 'required',
                'layout' => 'required',
                'headerBg' => 'required',
                'primary' => 'required',
                'navheaderBg' => 'required',
                'sidebarBg' => 'required',
                'sidebarStyle' => 'required',
                'sidebarPosition' => 'required',
                'headerPosition' => 'required',
                'containerLayout' => 'required',
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
        $typography = ["roboto","poppins","opensans", "HelveticaNeue"];
        $column = [
            "typography" => [
                "name" => "typography",
                "description" => "Please Select",
                "column_name" => "typography",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $typography,
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
