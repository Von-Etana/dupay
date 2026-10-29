<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'title'  => 'required|unique:faqs,title' . ($id ? ",$id" : ''),
                'description' => 'required',
                'homepage' => 'required',
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
        $OptionYes  =
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
            "title" => [
                "name" => "Title",
                "description" => "Enter Faq Question Title",
                "column_name" => "title",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "description" => [
                "name" => "Answer",
                "description" => "Enter Answer Description",
                "column_name" => "description",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "homepage" => [
                "name" => "Show at Homepage",
                "description" => "Please Select",
                "column_name" => "homepage",
                "input_type" => "4",
                "value" => "",
                "option" => $OptionYes,
                "required" => "required",
                "use_switch" => true
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
