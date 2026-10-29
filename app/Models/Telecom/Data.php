<?php

namespace App\Models\Telecom;

use App\Models\UserLevel;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Shetabit\Visitor\Traits\Visitable;
class Data extends Model
{
    use HasFactory;

    protected $guarded = [''];
    protected $hidden = [
        'real_price'
    ];


    protected $appends = ['network', 'type'];

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'name'  => 'required',
                'code'     => '',
                'extra'     => '',
                'variation_code' => '',
                'network_id' => 'required',
                'data_type_id' => '',
                'promo_id' => '',
                'size' => 'required',
                'real_price' => 'required',
                'price' => 'required',
                'status'  => 'required',
            ],
            $merge);
    }

    public  function column()
    {
        $allNetwork = Network::whereStatus("1")->get();
        $allDataPlanType = DataType::whereStatus("1")->get();
        $allPromo = Promo::whereStatus("1")->get();
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
            "data_type_id" => [
                "name" => "Type",
                "description" => "Please Select Data Plan Type",
                "column_name" => "data_type_id",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "disabled" => "disabled",
                "ajaxClass" => "dataPlanType",
                "option" => $allDataPlanType,
            ],
            "name" => [
                "name" => "Plan Name",
                "description" => "E.g 1GB - 1 Month Validity",
                "column_name" => "name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "code" => [
                "name" => "Plan code",
                "description" => "Plan ID | Plan Code E.g 1,2,3,4,5",
                "column_name" => "code",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "editable" => true,
            ],
            "variation_code" => [
                "name" => "Variation code",
                "description" => "Variation Code from VTPass",
                "column_name" => "variation_code",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "editable" => true,
            ],
            "extra" => [
                "name" => "Plan Extra code",
                "description" => "USSD String E.g #SM#131***SMEC",
                "column_name" => "extra",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "editable" => true,
            ],
            "size" => [
                "name" => "Plan Size",
                "description" => "E.g 1MB, 2GB",
                "column_name" => "size",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
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
                "description" => "Default General selling Price E.g 100",
                "column_name" => "price",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "promo_id" => [
                "name" => "Promo",
                "description" => "Please Select",
                "column_name" => "promo_id",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "model" => "promo->name",
                "option" => $allPromo,
            ],
            "status" => [
                "name" => "status",
                "description" => "Please Select Network Status",
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

    public function networks(){
        return $this->belongsTo(Network::class, "network_id");
    }

    public function dataType(){
        return $this->belongsTo(DataType::class, "data_type_id");
    }

    public function getNormalPrice(){
    return $this->attributes['price'];
    }

    public function iSmartLevelPricing($level_id){
        $price = 0;
        $level = UserLevel::where("id", $level_id)->first();
        if($level){
            if ($level->data_rate == "FLAT") {
                $newP = $level->data_charge/100 * $this->attributes['price'];
                $price = $this->attributes['price'] - $newP;
            }else{
                $price = $this->attributes['price'] - $level->data_charge;
            }

        }
    return $price;

}

    public function levelPrice(){
        $allLevel = UserLevel::where("enable_api", "1")->get();
        $price = [];
        foreach ($allLevel as  $value) {
            // $newP = $value->api_charge/100 * $this->attributes['price'];
            // $price[$value->name] =  $this->attributes['price'] - $newP;
            if ($value->data_rate == "FLAT") {
                $newP = $value->data_charge/100 * $this->attributes['price'];
                $price[$value->name] = $this->attributes['price'] - $newP;
            }else{
                $price[$value->name] = $this->attributes['price'] - $value->data_charge;
            }
        }
        return $price;
    }

    public function calLevelPrice(){
        if(auth()->user()){
            if (auth()->user()->level->data_rate == "FLAT") {
                $newP = auth()->user()->level->data_charge/100 * $this->attributes['price'];
                $price = $this->attributes['price'] - $newP;
            }else{
                $price = $this->attributes['price'] - auth()->user()->level->data_charge;
            }

        }
        return  $price;
    }

    public function getPriceAttribute(){
        if(auth()->user() || auth()->guard('admin')->user()){
          return auth()->guard("admin")->check() ? $this->attributes['price'] : $this->calLevelPrice();
        }
        return  $this->levelPrice();
    }

    public function getNetworkAttribute(){
        return $this->networks->name ?? "";
    }

    public function getTypeAttribute(){
        return $this->dataType->name ?? "";
    }

}
