<?php

namespace App\Models\Telecom;

use App\Models\UserLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\Visitable;

class AirtimeToCash extends Model
{
    use HasFactory;


    protected $fillable = [
        'network_id',
        'phone_number',
        'price_rate',
        'status',
    ];


    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'network_id'  => 'required|unique:airtime_to_cashes,network_id' . ($id ? ",$id" : ''),
                'phone_number' => 'required',
                'price_rate',
                'status'  => 'required',
            ],
            $merge);
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
                "option" => $allNetwork,
            ],
            "phone_number" => [
                "name" => "Phone Number",
                "description" => "Enter Phone Number",
                "column_name" => "phone_number",
                "input_type" => "0",
                "value" => "",
                "required" => "",
            ],
            "price_rate" => [
                "name" => "Price Rate",
                "description" => "Enter Default Price Rate in %",
                "column_name" => "price_rate",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "status" => [
                "name" => "status",
                "description" => "Please Select Plan Type Status",
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

    public function network(){
        return $this->belongsTo(Network::class);
    }


    public function iSmartLevelPricing($level_id){
        $price = 0;
        $level = UserLevel::where("id", $level_id)->first();
        if($level){
            $newP = $level->api_charge/100 * $this->attributes['price_rate'];
            $price = $this->attributes['price_rate'] - $newP;
        }
    return $price;
}

    public function levelPrice(){
        $allLevel = UserLevel::where("enable_api", "1")->get();
        $price = [];
        foreach ($allLevel as  $value) {
            $newP = $value->api_charge/100 * $this->attributes['price_rate'];
            $price[$value->name] =  $this->attributes['price_rate'] - $newP;
        }
        return $price;
    }

    public function calLevelPrice(){
        if(auth()->user()){
            $newP = auth()->user()->level->api_charge/100 * $this->attributes['price_rate'];
            return  $this->attributes['price_rate'] - $newP;
        }
    }

    public function getPriceRateAttribute(){
        if(auth()->user()){
          return auth()->guard("admin")->check() ? $this->attributes['price_rate'] : $this->calLevelPrice();
        }
        return  $this->levelPrice();
    }


}
