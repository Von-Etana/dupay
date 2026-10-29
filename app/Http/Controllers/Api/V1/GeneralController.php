<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Telecom\AirtimeType;
use App\Models\Telecom\Bill;
use App\Models\Telecom\Cable;
use App\Models\Telecom\CableType;
use App\Models\Telecom\Data;
use App\Models\Telecom\DataType;
use App\Models\Telecom\Exam;
use App\Models\Telecom\Network;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class GeneralController extends Controller
{

    //get all available data
    public function getData($type){
        $item = [];
        if($type == "network")
        {
            $item = Network::where("status", "1")->get();
        }else if($type == "dataType"){
            $item = DataType::where("status", "1")->get();
       }
        else if($type == "data"){
            $id = 1;
             $item = Data::where("status", "1")->whereHas('networks', function (Builder $query) {
                $query->where('status', "1");
            })->whereHas('dataType', function (Builder $query) {
                $query->where('status', "1");
            })->get();
        }else if($type == "airtimeType"){
            $item = AirtimeType::where("status", "1")->whereHas('network', function (Builder $query) {
                $query->where('status', "1");
            })->get();
       }else if($type == "cableType"){
        $item = CableType::where("status", "1")->get();
       }else if($type == "cablePlan"){
        $item = Cable::where("status", "1")->whereHas('cableType', function (Builder $query) {
            $query->where('status', "1");
        })->get();
       }else if($type == "billType"){
        $item = Bill::where("status", "1")->get();
       }else if($type == "examType"){
        $item = Exam::where("status", "1")->get();
       }



        if(count($item) < 1){
            return response(['success' => false, 'message' => "No available $type"]);
        }

        return "<pre>".json_encode($item, JSON_PRETTY_PRINT)."</pre>";
    }


    public function getItem($type){
        $item = [];
        if($type == "network")
        {
            $item = Network::where("status", "1")->get();
        }else if($type == "data"){
             $item = Data::where("status", "1")->whereHas('networks', function (Builder $query) {
                $query->where('status', "1");
            })->whereHas('dataType', function (Builder $query) {
                $query->where('status', "1");
            })->get();
        }
        else if($type == "bill"){
            $item = Bill::where("status", "1")->get();
       }
       else if($type == "cable"){
        $item = Cable::where("status", "1")->get();
         }
         else if($type == "exam"){
            $item = Exam::where("status", "1")->get();
             }
        if(count($item) < 1){
            return response(['success' => false, 'message' => "No available $type"]);
        }

        return response(['success' => true, $type => $item]);
    }



}
