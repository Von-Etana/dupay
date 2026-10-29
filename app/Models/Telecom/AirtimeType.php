<?php

namespace App\Models\Telecom;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\Visitable;
class AirtimeType extends Model
{
    use HasFactory;


    protected $fillable = [
        'name',
        'network_id',
        'api_connection_id',
        'order_delivery',
        'status',
    ];
    protected $hidden = [
        'api_connection_id',
        'order_delivery',
        'status',
        'created_at',
        'updated_at',
    ];


    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'name'  => 'required',
                'network_id' => 'required',
                'api_connection_id' => 'required',
                'order_delivery' => 'required',
                'status'  => 'required',
            ],
            $merge);
    }

    public  function column()
    {
        $allApiConnections = ApiConnection::whereStatus("1")->get();
        $allNetwork = Network::whereStatus("1")->get();
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
            "network_id" => [
                "name" => "Network",
                "description" => "Please Select ",
                "column_name" => "network_id",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $allNetwork,
            ],
            "name" => [
                "name" => "Aitime Type",
                "description" => "E.g VTU,SNS",
                "column_name" => "name",
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

    public function apiConnection(){
        return $this->belongsTo(ApiConnection::class);
    }

    public function network(){
        return $this->belongsTo(Network::class);
    }



}
