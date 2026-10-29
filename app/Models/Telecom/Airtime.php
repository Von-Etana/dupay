<?php

namespace App\Models\Telecom;

use App\Models\UserLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Shetabit\Visitor\Traits\Visitable;

class Airtime extends Model
{
    use HasFactory;


    protected $guarded = [''];
    protected $hidden = [
        'real_price'
    ];

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [

                'network_id' => 'required',
                'airtime_type_id' => 'required',
                'usd' => '',
                'price' => 'required',
                'status'  => 'required',
            ],
            $merge);
    }


    public  function column()
    {
        $allNetwork = Network::whereStatus("1")->get();
        $allAirtimePlanType = AirtimeType::whereStatus("1")->get();
        $promo = Promo::whereStatus("1")->get();
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
                "ajaxClass" => "airtimeNetwork",
                "disabled" => "disabled",
                "option" => $allNetwork,
            ],
            "airtime_type_id" => [
                "name" => "Type",
                "description" => "Please Select Airtime Type",
                "column_name" => "airtime_type_id",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "disabled" => "disabled",
                "ajaxClass" => "airtimeType",
                "option" => $allAirtimePlanType,
            ],
            "real_price" => [
                "name" => "Buying Price Rate",
                "description" => "Enter Buying Price Rate in %",
                "column_name" => "real_price",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "price" => [
                "name" => "Selling Price Rate",
                "description" => "Enter Default Price Rate in %",
                "column_name" => "price",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "usd" => [
                "name" => "USD Format",
                "description" => "Enter usd Format e.g *606*n*",
                "column_name" => "usd",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "editable" => true
            ],
            "promo_id" => [
                "name" => "Promo",
                "description" => "Please Select",
                "column_name" => "promo_id",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "option" => $promo,
            ],
            "status" => [
                "name" => "status",
                "description" => "Please Select Network Status",
                "column_name" => "status",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $option,
                "use_switch" => true,
            ]

        ];
        return json_decode(json_encode($column));
    }


    public function networks(){
        return $this->belongsTo(Network::class, "network_id",);
    }

    public function airtimeType(){
        return $this->belongsTo(AirtimeType::class, "airtime_type_id",);
    }

    public function getNetworkAttribute(){
        return $this->networks->name;
    }

    public function getTypeAttribute(){
        return $this->airtimeType->name;
    }

    public function iSmartLevelPricing($level_id){
        $price = 0;
        $level = UserLevel::where("id", $level_id)->first();
        if($level){
            if ($level->airtime_rate == "FLAT") {
                $newP = $level->airtime_charge/100 * $this->attributes['price'];
                $price = $this->attributes['price'] - $newP;
            }else{
                $price = $this->attributes['price'] - $level->airtime_charge;
            }

        }
    return $price;

}


public function levelPrice(){
    $allLevel = UserLevel::where("enable_api", "1")->get();
    $price = [];
    foreach ($allLevel as  $value) {
        if ($value->airtime_rate == "FLAT") {
            $newP = $value->airtime_charge/100 * $this->attributes['price'];
            $price[$value->name] = $this->attributes['price'] - $newP;
        }else{
            $price[$value->name] = $this->attributes['price'] - $value->airtime_charge;
        }
    }
    return $price;
}

public function calLevelPrice(){
    if(auth()->user()){
        if (auth()->user()->level->airtime_rate == "FLAT") {
            $newP = auth()->user()->level->airtime_charge/100 * $this->attributes['price'];
            $price = $this->attributes['price'] - $newP;
        }else{
            $price = $this->attributes['price'] - auth()->user()->level->airtime_charge;
        }

    }
    return  $price;
}

public function calLevelAmount($amount){
    if(auth()->user()){
        if (auth()->user()->level->airtime_rate == "FLAT") {
            $newP = auth()->user()->level->airtime_charge/100 * $amount;
            $price = $amount - $newP;
        }else{
            $price = $amount - auth()->user()->level->airtime_charge;
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



}
