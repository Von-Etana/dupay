<?php

namespace App\Models\Telecom;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DataCard extends Model
{
    use HasFactory;
    protected $guarded = [''];
    protected $hidden = [
        'dump_pin',
        'total',
        'real_price',
        'size'
    ];

    protected $appends = ['network'];

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'plan'  => 'required',
                'dump_pin'  => 'required',
                'price'     => 'required|integer',
                'real_price'     => 'required|integer',
                'load_code'     => '',
                'network_id' => 'required',
                'total' => '',
                'status'  => 'required',
            ],
            $merge);
    }


    public function networks(){
        return $this->belongsTo(Network::class, "network_id",);
    }

    public function getNetworkAttribute(){
        return $this->networks->name;
    }


    public  function column()
    {
        $allNetwork = Network::whereStatus("1")->get();

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
                "description" => "Please Select Network",
                "column_name" => "network_id",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "ajaxClass" => "networkData",
                "disabled" => "disabled",
                "option" => $allNetwork,
            ],
            "plan" => [
                "name" => "Plan Name",
                "description" => "E.g 1GB - 1 Month Validity",
                "column_name" => "plan",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "size" => [
                "name" => "Plan Size",
                "description" => "E.g 1000 for 1GB",
                "column_name" => "size",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "dump_pin" => [
                "name" => "Dump Pin",
                "description" => "Enter Dump Pin",
                "column_name" => "dump_pin",
                "input_type" => "3",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "total" => [
                "name" => "Total",
                "description" => "Enter total pin",
                "column_name" => "total",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "create" => false,
                "edit" => false,
                "editable" => true,
            ],
            "load_code" => [
                "name" => "Load Code",
                "description" => "Enter load code",
                "column_name" => "load_code",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "editable" => true,
            ],
            "real_price" => [
                "name" => "Buying Price",
                "description" => "Buying Price",
                "column_name" => "real_price",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "price" => [
                "name" => "Selling Price",
                "description" => "Default selling Price E.g 100",
                "column_name" => "price",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "status" => [
                "name" => "status",
                "description" => "Please Select",
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


    public function insertMethod($data){
        $total = Str::of($data["dump_pin"])->explode("\n")->map(function ($item) {
            return trim($item);
           })->toArray();
           $data["total"] = count($total);
        $this->create($data);
        return true;
    }


    public function updateMethod($id,$data){
        $total = Str::of($data["dump_pin"])->explode("\n")->map(function ($item) {
            return trim($item);
           })->toArray();
           $data["total"] = count($total);
           $dataCard = $this->findOrFail($id);
           $dataCard->fill($data)->save();
        return true;
    }
}
