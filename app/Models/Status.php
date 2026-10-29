<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;
    public $guarded=[];

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [

                'name' => 'required'
            ],
            $merge);
    }

    public  function column()
    {
        $column = [
            "name" => [
                "name" => "Name",
                "description" => "Enter name",
                "column_name" => "name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
        ];
        return json_decode(json_encode($column));
    }
}
