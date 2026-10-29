<?php

namespace App\Models\Telecom;

use App\Models\UserLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\Visitable;
class Exam extends Model
{
    use HasFactory;


    protected $guarded = [''];
    protected $hidden = [
        'code',
        'status',
        'created_at',
        'updated_at',
    ];



    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'name'  => 'required|unique:exams,name' . ($id ? ",$id" : ''),
                'code' => 'required|unique:exams,code' . ($id ? ",$id" : ''),
                'amount' => 'required',
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
            "name" => [
                "name" => "Exam Name",
                "description" => "E.g Waec, Neco",
                "column_name" => "name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "code" => [
                "name" => "Code",
                "description" => "E.g 1,4,6,8",
                "column_name" => "code",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "amount" => [
                "name" => "Amount",
                "description" => "Enter Default Amount",
                "column_name" => "amount",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
            ],
            "status" => [
                "name" => "status",
                "description" => "Please Select Status",
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



    public function iSmartLevelPricing($level_id){
        $price = 0;
        $level = UserLevel::where("id", $level_id)->first();
        if($level){
            $newP = $level->api_charge/100 * $this->attributes['amount'];
            $price = $this->attributes['amount'] - $newP;
        }
    return $price;
}

    public function levelPrice(){
        $allLevel = UserLevel::where("enable_api", "1")->get();
        $price = [];
        foreach ($allLevel as  $value) {
            $newP = $value->api_charge/100 * $this->attributes['amount'];
            $price[$value->name] =  $this->attributes['amount'] - $newP;
        }
        return $price;
    }

    public function calLevelPrice(){
        if(auth()->guard("user")){
            $newP = auth()->guard("user")->user()->level->api_charge/100 * $this->attributes['amount'];
            return  $this->attributes['amount'] - $newP;
        }
    }

      public function getAmountAttribute(){
        if(auth()->guard("admin") || auth()->guard("user")){
          return auth()->guard("admin")->check()  ? $this->attributes['amount'] : $this->calLevelPrice();
        }
        return  $this->levelPrice();
    }


}
