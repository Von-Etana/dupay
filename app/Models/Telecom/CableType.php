<?php

namespace App\Models\Telecom;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\Visitable;
class CableType extends Model
{
    use HasFactory;


    protected $guarded = [''];
    protected $hidden = [
        'api_connection_id',
        'verification_api_connection_id',
        'code',
        'order_delivery',
        'status',
        'created_at',
        'updated_at',
    ];


    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'name'  => 'required|max:12|unique:cable_types,name' . ($id ? ",$id" : ''),
                'code' => 'required',
                'api_connection_id' => 'required',
                'verification_api_connection_id' => '',
                'order_delivery' => 'required',
                'status'  => 'required',
            ],
            $merge);
    }


    public  function column()
    {
        $allApiConnections = ApiConnection::whereStatus("1")->get();
        $deliveryOption  =
            [
                [
                    "name" => "Automatic",
                    "id" => "1"
                ],
                [
                    "name" => "Manual",
                    "id" => "0"
                ]
            ];
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
                "name" => "TV logo",
                "description" => "Choose tv Logo",
                "column_name" => "image",
                "input_type" => "7",
                "value" => "",
                "required" => "",
                

            ],
            "name" => [
                "name" => "Tv Name",
                "description" => "E.g GOTV, DSTV",
                "column_name" => "name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "code" => [
                "name" => "Code",
                "description" => "E.g 1,4,6,8",
                "column_name" => "code",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "apiConnection" => [
                "name" => "Api Connection",
                "description" => "Please Select Api Connection Type",
                "column_name" => "api_connection_id",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $allApiConnections,
            ],
            "verification_api_connection_id" => [
                "name" => "Verification Api Connection",
                "description" => "Please Select verification Api Connection Type",
                "column_name" => "verification_api_connection_id",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "option" => $allApiConnections,
            ],
            "order_delivery" => [
                "name" => "Method",
                "description" => "Please Select ",
                "column_name" => "order_delivery",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $deliveryOption,
            ],
            "status" => [
                "name" => "status",
                "description" => "Please Select Plan Type Status",
                "column_name" => "status",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $option,
                "use_switch" => true
            ]

        ];
        return json_decode(json_encode($column));
    }


  public function getImageAttribute()
{
    return  url("/")."/storage/".$this->attributes['image'];
}

    public function apiConnection(){
        return $this->belongsTo(ApiConnection::class);
    }


    public function apiVerificationConnection(){
        return $this->belongsTo(ApiConnection::class, "verification_api_connection_id");
    }


    public function cableList(){
        return $this->hasMany(Cable::class);
    }





}
