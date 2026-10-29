<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use App\Models\User;
use App\Models\VirtualCardFundingCharge;

class FundingChargesController extends Controller
{
    public function index()
    {
        $data["page_title"] = "Deposit charges";
        $data["page_sub_title"] = "Deposit charges";
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
        
        $charges = VirtualCardFundingCharge::all();
        
        $data['charges'] = $charges;
        
        return view('admin.fundingcharge.index', $data, ['charges' => $charges]);
    }
    
    public function edit($id)
    {
        $data["page_title"] = "Deposit Charge";
        $data["page_sub_title"] = "Deposit Charge";
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
        
        $charge = VirtualCardFundingCharge::find($id);
        
        return view('admin.fundingcharge.edit', compact('charge'), $data);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'minimum_amount' => 'required|numeric|min:1',
            'maximum_amount'     => 'required|numeric|min:1',
            'charge'       => 'required|numeric|min:0',
        ]);
        
        $charge = VirtualCardFundingCharge::find($id);

        $charge->update($validated);

        session()->flash('message', 'Deposit Charge updated successfully.');
        session()->flash('type', 'success');
        session()->flash('title', 'Success');

        return redirect()->back();
    }
}
