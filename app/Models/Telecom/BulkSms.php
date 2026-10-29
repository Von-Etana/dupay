<?php

namespace App\Models\Telecom;

use App\Models\UserLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\Visitable;
class BulkSms extends Model
{
    use HasFactory;


    protected $guarded = [''];


    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'sms_charge'  => 'required',
                'dnd_unit' => 'required',
                'api_connection_id' => 'required',
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
            "sms_charge" => [
                "name" => "SMS Charge Per / Unit (₦)",
                "description" => "Enter Sms Charge",
                "column_name" => "sms_charge",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "dnd_unit" => [
                "name" => "Unit Per DND Number (unit)",
                "description" => "Enter unit you wish to charge per dnd number",
                "column_name" => "dnd_unit",
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
            "status" => [
                "name" => "status",
                "description" => "Please Select Status",
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

    public function apiConnection(){
        return $this->belongsTo(ApiConnection::class);
    }


    public function iSmartLevelPricing($level_id){
        $price = 0;
        $level = UserLevel::where("id", $level_id)->first();
        if($level){
            $newP = $level->api_charge/100 * $this->attributes['sms_charge'];
            $price = $this->attributes['sms_charge'] - $newP;
        }
    return $price;
}


    public function levelPrice(){
        $allLevel = UserLevel::where("enable_api", "1")->get();
        $price = [];
        foreach ($allLevel as  $value) {
            $newP = $value->api_charge/100 * $this->attributes['sms_charge'];
            $price[$value->name] =  $this->attributes['sms_charge'] - $newP;
        }
        return $price;
    }


    public function calLevelPrice(){
        if(auth()->user()){
            $newP = auth()->user()->level->api_charge/100 * $this->attributes['sms_charge'];
            return  $this->attributes['sms_charge'] - $newP;
        }
    }

    public function getSmsChargeAttribute(){
        if(auth()->user()){
          return auth()->guard("admin")->check() ? $this->attributes['sms_charge'] : $this->calLevelPrice();
        }
        return  $this->levelPrice();
    }





}
