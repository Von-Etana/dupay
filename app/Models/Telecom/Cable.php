<?php

namespace App\Models\Telecom;

use App\Models\UserLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Shetabit\Visitor\Traits\Visitable;
class Cable extends Model
{
    use HasFactory;


    protected $guarded = [''];

    protected $hidden = [
        'code',
        'plan_code',
        'cable_type_id',
        'cableType',
        'status',
        'created_at',
        'updated_at',
        'real_price'
    ];

    protected $appends = ['cable'];

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'name'  => 'required|unique:cables,name' . ($id ? ",$id" : ''),
                'cable_type_id' => 'required',
                'code' => 'required',
                'plan_code' => 'required',
                'real_price' => 'required',
                'amount' => 'required',
                'status'  => 'required',
            ],
            $merge);
    }

    public  function column()
    {

        $allCableType = CableType::whereStatus("1")->get();
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
            "cable_type_id" => [
                "name" => "Cable Tv",
                "description" => "Please Select Tv",
                "column_name" => "cable_type_id",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $allCableType,
            ],
            "name" => [
                "name" => "Plan Name",
                "description" => "Enter Plan Name",
                "column_name" => "name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "plan_code" => [
                "name" => "Plan Code",
                "description" => "dstv-padi",
                "column_name" => "plan_code",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "code" => [
                "name" => "Plan Id",
                "description" => "E.g 1,2,6,8",
                "column_name" => "code",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "real_price" => [
                "name" => "Buying Amount",
                "description" => "Enter Buying Amount",
                "column_name" => "real_price",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "amount" => [
                "name" => "Selling Amount",
                "description" => "Enter Default Amount",
                "column_name" => "amount",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
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


    public function cableType(){
        return $this->belongsTo(CableType::class, "cable_type_id",);
    }

    public function getCableAttribute(){
        $cable["name"] = $this->cableType->name;
        $cable["id"] = $this->cableType->id;
        return $cable;
    }


    public function iSmartLevelPricing($level_id){
        $price = 0;
        $level = UserLevel::where("id", $level_id)->first();
        if($level){
            if ($level->cable_rate == "FLAT") {
                $newP = $level->cable_charge/100 * $this->attributes['amount'];
                $price = $this->attributes['amount'] - $newP;
            }else{
                $price = $this->attributes['amount'] - $level->cable_charge;
            }

        }
    return $price;

}


public function levelPrice(){
    $allLevel = UserLevel::where("enable_api", "1")->get();
    $price = [];
    foreach ($allLevel as  $value) {
        if ($value->cable_rate == "FLAT") {
            $newP = $value->cable_charge/100 * $this->attributes['amount'];
            $price[$value->name] = $this->attributes['amount'] - $newP;
        }else{
            $price[$value->name] = $this->attributes['amount'] - $value->cable_charge;
        }
    }
    return $price;
}

public function calLevelPrice(){
    if(auth()->user()){
        if (auth()->user()->level->cable_rate == "FLAT") {
            $newP = auth()->user()->level->cable_charge/100 * $this->attributes['amount'];
            $price = $this->attributes['amount'] - $newP;
        }else{
            $price = $this->attributes['amount'] - auth()->user()->level->cable_charge;
        }

    }
    return  $price;
}

public function getAmountAttribute(){
    if(auth()->user() || auth()->guard('admin')->user()){
      return auth()->guard("admin")->check() ? $this->attributes['amount'] : $this->calLevelPrice();
    }
    return  $this->levelPrice();
}


}
