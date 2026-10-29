<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EpinRate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\Telecom\Network;

class EpinRateController extends Controller
{
    public function index()
    {
        $data["page_title"] = "Recharge Card Pin";
        $data["page_sub_title"] = "Recharge Card Pin Rate";
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
        
        $rates = EpinRate::with('network')->get();
        return view('admin.epin_rate.index', compact('rates') + $data);
    }

    public function edit(EpinRate $epin_rate)
    {
        $data["page_title"] = "Recharge Card Pin";
        $data["page_sub_title"] = "Recharge Card Pin Rate";
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
        
        $networks = Network::all();
        return view('admin.epin_rate.edit', compact('epin_rate', 'networks'), $data);
    }

    public function update(Request $request, EpinRate $epin_rate)
    {
        $validated = $request->validate([
            'network_id' => 'required|exists:networks,id',
            'amount'     => 'required|numeric|min:1',
            'rate'       => 'required|numeric|min:0',
        ]);

        $epin_rate->update($validated);

        session()->flash('message', 'ePIN rate updated successfully.');
        session()->flash('type', 'success');
        session()->flash('title', 'Success');

        return redirect()->route('admin.epin_rates.index');
    }

}
