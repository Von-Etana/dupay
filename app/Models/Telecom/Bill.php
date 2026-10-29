<?php

namespace App\Models\Telecom;

use App\Models\UserLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Shetabit\Visitor\Traits\Visitable;

class Bill extends Model
{
    use HasFactory;


    protected $guarded = [''];

    protected $hidden = [
        'api_connection_id',
        'verification_api_connection_id',
        'variation_code',
        'code',
        'order_delivery',
        'status',
        'created_at',
        'updated_at',
        'network',
        'real_price'
    ];


    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'disco'  => 'required|unique:bills,disco' . ($id ? ",$id" : ''),
                'name' => 'required',
                'code' => 'required',
                'api_connection_id' => 'required',
                'verification_api_connection_id' => '',
                'variation_code' => 'required',
                'order_delivery' => 'required',
                'real_price' => 'required',
                'price_rate' => 'required',
                'status'  => 'required',
            ],
            $merge);
    }



    public  function column()
    {

        $allApiConnections = ApiConnection::whereStatus("1")->get();
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
        $column = [
            "disco" => [
                "name" => "Disco",
                "description" => "Enter Disco",
                "column_name" => "disco",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "name" => [
                "name" => "Disco Description",
                "description" => "Ibadan Electricity Distribution",
                "column_name" => "name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "variation_code" => [
                "name" => "Service Code",
                "description" => "ikeja-elec",
                "column_name" => "variation_code",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "code" => [
                "name" => "Code",
                "description" => "E.g 1,2,6,8",
                "column_name" => "code",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "real_price" => [
                "name" => "Buying Price Rate",
                "description" => "Buying Price Rate in %",
                "column_name" => "real_price",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "price_rate" => [
                "name" => "Selling Price Rate",
                "description" => "Enter Default Price Rate in %",
                "column_name" => "price_rate",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
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
                "use_switch" => true,
                "option" => $option,
            ]


        ];
        return json_decode(json_encode($column));
    }

    public function apiConnection(){
        return $this->belongsTo(ApiConnection::class);
    }


    public function apiVerificationConnection(){
        return $this->belongsTo(ApiConnection::class, "verification_api_connection_id");
    }

    public function iSmartLevelPricing($level_id){
        $price = 0;
        $level = UserLevel::where("id", $level_id)->first();
        if($level){
            if ($level->bill_rate == "FLAT") {
                $newP = $level->bill_charge/100 * $this->attributes['price_rate'];
                $price = $this->attributes['price_rate'] - $newP;
            }else{
                $price = $this->attributes['price_rate'] - $level->bill_charge;
            }

        }
    return $price;

}


public function levelPrice(){
    $allLevel = UserLevel::where("enable_api", "1")->get();
    $price = [];
    foreach ($allLevel as  $value) {
        if ($value->data_rate == "FLAT") {
            $newP = $value->bill_charge/100 * $this->attributes['price_rate'];
            $price[$value->name] = $this->attributes['price_rate'] - $newP;
        }else{
            $price[$value->name] = $this->attributes['price_rate'] - $value->bill_charge;
        }
    }
    return $price;
}


public function calLevelPrice(){
    if(auth()->user()){
        if (auth()->user()->level->bill_rate == "FLAT") {
            $newP = auth()->user()->level->bill_charge/100 * $this->attributes['price_rate'];
            $price = $this->attributes['price_rate'] - $newP;
        }else{
            $price = $this->attributes['price_rate'] - auth()->user()->level->bill_charge;
        }

    }
    return  $price;
}

public function calLevelAmount($amount){
    if(auth()->user()){
        if (auth()->user()->level->bill_rate == "FLAT") {
            $newP =auth()->user()->level->bill_charge/100 * $amount;
            $price = $amount - $newP;
        }else{
            $price = $amount - auth()->user()->level->bill_charge;
        }

    }
    return  $price;
}

public function getPriceRateAttribute(){
    if(auth()->user() || auth()->guard('admin')->user()){
      return auth()->guard("admin")->check() ? $this->attributes['price_rate'] : $this->calLevelPrice();
    }
    return  $this->levelPrice();
}





}
