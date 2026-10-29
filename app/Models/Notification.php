<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Jobs\GeneralJob;
use App\Jobs\SendEmail;
use Illuminate\Support\Facades\Bus;

class Notification extends Model
{
    use HasFactory;
    public $guarded=[];

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'subject'  => 'required',
                'page_content'  => 'required'
            ],
            $merge);
    }

    public  function column()
    {

        $users = User::where("email", "!=", null)->get();

        $option  =
        [
            [
                "name" => "Send & Save",
                "id" => "1"
            ],
            [
                "name" => "Save",
                "id" => "0"
            ]

        ];
        $typeoption  =
            [
                [
                    "name" => "Moving",
                    "id" => "1"
                ],
                [
                    "name" => "Pop Up",
                    "id" => "2"
                ],
                [
                    "name" => "Image",
                    "id" => "3"
                ]
            ];
            $platformoption  =
            [
                [
                    "name" => "Website",
                    "id" => "1"
                ],
                [
                    "name" => "Mobile",
                    "id" => "2"
                ],
                [
                    "name" => "Email",
                    "id" => "3"
                ]
            ];

        $column = [
            // "id" => [
            //     "name" => "To",
            //     "description" => "Please Select",
            //     "column_name" => "id",
            //     "id" => "to",
            //     "input_type" => "4-2",
            //     "multiple" => ["id" => "id", "name" => "email"],
            //     "required" => "required",
            //     "value" => "",
            //     "option" => $users,
            //     "dataColumn" => ["id" => "id", "name" => "email"]
            // ],
            "subject" => [
                "name" => "Title",
                "description" => "Enter subject",
                "column_name" => "subject",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "page_content" => [
                "name" => "Content",
                "description" => "Enter content",
                "column_name" => "page_content",
                "id" => "page_content_wysiwyg",
                "input_type" => "3",
                "value" => "",
                "required" => "required",
                "wysiwyg" => true,
                "view" => false

            ],
            "type" => [
                "name" => "Type",
                "description" => "Please Select ",
                "column_name" => "type",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "option" => $typeoption,
            ],
            "platform" => [
                "name" => "Platform",
                "description" => "Please Select ",
                "column_name" => "platform",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "option" => $platformoption,
            ],
            "status" => [
                "name" => "Status",
                "description" => "Please Select ",
                "column_name" => "status",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "option" => $option,
            ]
        ];
        return json_decode(json_encode($column));
    }


    public function insertMethod($data){
        $data["admin_id"] = auth()->guard('admin')->user()->id;
        $type = $data['platform'] == "1" ? "Website" : ($data['platform'] == "2" ? "Mobile" : "Email");
        $data["type"] = $data["platform"];
        $title = $data["subject"];
        $content = $data["page_content"];
        $item =  $this->create($data);
        $sta = $data["status"] == "1" ? "Saved and Sent" : "Saved";
        $ac['activity'] = "$sta new $type notification title $title";
        $ac['actions'] = $data["status"] == "0"? "create":"send";
        $ac['module'] = "Notification";
        $ac['user_type'] = "2";
        $ac["extra"] = json_encode(["id" =>  $item->id]);
        addSiteActitvity(auth()->guard('admin')->user(), $ac);
        if($data["status"] == "1" && $data["platform"] == "3"){
                $batch  = Bus::batch([])->dispatch();
                $batch->add(new SendEmail(null, $title, $content));
        }
            return true;
    }


}
