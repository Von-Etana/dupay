<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RechargeCardPin;
use App\Models\Telecom\Network;
use Illuminate\Http\Request;

class RechargeCardPinController extends Controller
{
    public function index()
    {
        $pins = RechargeCardPin::with('network')->latest()->paginate(50);
        $networks = Network::orderBy('name')->get();
        return view('admin.recharge_card_pins.index', compact('pins', 'networks'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'network_id' => 'required|exists:networks,id',
            'value' => 'required|numeric|min:1',
            'pin' => 'required|string|max:100',
            'serial' => 'nullable|string|max:100',
        ]);
        $data['pin'] = encrypt($data['pin']);
        RechargeCardPin::create($data);
        return back()->with('message', 'Recharge card PIN added to inventory.');
    }

    public function destroy(RechargeCardPin $rechargeCardPin)
    {
        abort_if($rechargeCardPin->status === 'sold', 422, 'Sold PINs cannot be removed.');
        $rechargeCardPin->update(['status' => 'void']);
        return back()->with('message', 'Recharge card PIN voided.');
    }
}
