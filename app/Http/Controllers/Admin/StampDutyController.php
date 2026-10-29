<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\StampDutySetting;

class StampDutyController extends Controller
{
    public function index()
    {
        $data["page_title"] = "Stamp Duty Charges";
        $data["page_sub_title"] = "Stamp Duty Charges";
        $data["breadcrumb"] = [
            [
                "title" => "Home",
                "url" => route('admin.index')
            ],
            [
                "title" => $data["page_sub_title"],
                "url" => null
            ],
        ];
        
        $fetch = StampDutySetting::first();
        return view('admin.stampduty.index', compact('fetch'), $data);
    }
    
    public function update(Request $request)
    {
        $request->validate([
            'charge' => 'required|numeric|min:0',
            'start' => 'required|numeric|min:0',
        ]);
    
        try {
            $stamp = StampDutySetting::first();
            if (!$stamp) {
                $stamp = new StampDutySetting();
            }
    
            $stamp->charge = $request->charge;
            $stamp->start = $request->start;
            $stamp->save();
    
            // Success flash
            Session::flash('message', 'Stamp duty settings updated successfully.');
            Session::flash('type', 'success');
            Session::flash('title', 'Success');
    
        } catch (\Exception $e) {
            // Error flash
            Session::flash('message', 'Failed to update Stamp duty settings: '.$e->getMessage());
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
        }
    
        return redirect()->back();
    }
}
