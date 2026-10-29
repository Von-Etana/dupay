<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use App\Models\User;
use App\Models\DepositFee;

class DepositFeeController extends Controller
{
    public function index($currency)
    {
        $data["page_title"] = "Deposit Fees";
        $data["page_sub_title"] = "Deposit Fees";
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
        
        $fees = DepositFee::where('currency', $currency)->get();
        
        $data['fees'] = $fees;
        
        return view('admin.depositfee.index', $data, ['fees' => $fees]);
    }
    
    public function edit($id)
    {
        $data["page_title"] = "Deposit Fee";
        $data["page_sub_title"] = "Deposit Fee";
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
        
        $fee = DepositFee::find($id);
        
        return view('admin.depositfee.edit', compact('fee'), $data);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'minimum_amount' => 'required|numeric|min:1',
            'maximum_amount'     => 'required|numeric|min:1',
            'fee'       => 'required|numeric|min:0',
            'charge_type' => 'nullable|in:fixed,percentage',
            'cap'        => 'nullable|numeric|min:0',
        ]);

        // Default to percentage if not provided; cap 0 = no cap
        $validated['charge_type'] = $request->input('charge_type', 'percentage');
        $validated['cap'] = $request->input('cap', 0);
        
        $fee = DepositFee::find($id);

        $fee->update($validated);

        session()->flash('message', 'Deposit fee updated successfully.');
        session()->flash('type', 'success');
        session()->flash('title', 'Success');

        return redirect()->back();
    }
    
}