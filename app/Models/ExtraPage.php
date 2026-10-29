<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExtraPage extends Model
{
    use HasFactory;
    public $guarded=[];

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [

                'title' => 'required',
                'page_content' => 'required',
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
            ];

        $column = [
            "title" => [
                "name" => "Title",
                "description" => "Enter title",
                "column_name" => "title",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "page_content" => [
                "name" => "Content",
                "description" => "Enter Content",
                "column_name" => "page_content",
                "id" => "page_content_wysiwyg",
                "input_type" => "3",
                "value" => "",
                "required" => "required",
                "view" => false,
                "wysiwyg" => true,
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
        $data["slug"] = strtolower(Str::random(6));
        $data["url"] = preg_replace('/\W+/', '-', strtolower(trim($data["title"])));
      $item =  $this->create($data);
      $ac['activity'] = "Created new extra page with title ".$data['title'];
      $ac['actions'] =  "create";
      $ac['module'] = "ExtraPage";
      $ac['user_type'] = "2";
      $ac["extra"] = ["id" =>  $item->id, "request" => $data];
      addSiteActitvity(auth()->guard('admin')->user(), $ac);
            return true;
    }

    public function updateMethod($id,$data){
        $page= $this->findOrFail($id);
        $data["url"] = preg_replace('/\W+/', '-', strtolower(trim($data["title"])));
        $page->update($data);
        $changedAttributes = $page->getDirty();

      $ac['activity'] = "Updated extra page title ".$data['title'];
      $ac['actions'] =  "update";
      $ac['module'] = "ExtraPage";
      $ac['user_type'] = "2";
      $ac["extra"] = ["id" =>  $page->id, "request" => $data, "change_column" => $changedAttributes];
    }
}
