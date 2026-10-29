<?php

namespace App\Models\Telecom;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\Visitable;
class Network extends Model
{
    use HasFactory;

    public $guarded=[];
    protected $hidden = [
        'code',
        'status',
        'created_at',
        'updated_at',
    ];

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'name'  => 'required',
                'other_name'  => 'required',
                'code'     => 'required',
                'image' =>  '',
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
            "image" => [
                "name" => "Network logo",
                "description" => "Choose Network Logo",
                "column_name" => "image",
                "input_type" => "7",
                "value" => "",
                "required" => "",
                

            ],
            "name" => [
                "name" => "name",
                "description" => "E.g MTN, GLO, 9MOBILE, AIRTEL",
                "column_name" => "name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "other_name" => [
                "name" => "Short Name",
                "description" => "E.g mtn, etisalat",
                "column_name" => "other_name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "code" => [
                "name" => "Network Code",
                "description" => "E.g 1,2,3,4,5",
                "column_name" => "code",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "extra_id" => [
                "name" => "Network Extra Id",
                "description" => "E.g 1,2,3,4,5",
                "column_name" => "extra_id",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "editable" => true
            ],
            "status" => [
                "name" => "status",
                "description" => "Please Select Network Status",
                "column_name" => "status",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $option,
            ]

        ];
        return json_decode(json_encode($column));
    }
    public function getImageAttribute()
{
    return  url("/")."/storage/".$this->attributes['image'];
}

    public function dataType(){
        return $this->hasMany(DataType::class)->where("status", "1")->with("plan");
    }

    public function dataPlan(){
        return $this->hasMany(Data::class)->where("status", "1");
    }

    public function airtimeType(){
        return $this->hasMany(AirtimeType::class)->where("status", "1");
    }

    public function airtimeCashType(){
        return $this->hasMany(AirtimeToCash::class)->where("status", "1");
    }


    public function dataCardPlan(){
        return $this->hasMany(DataCard::class)->where("status", "1");
    }

}
