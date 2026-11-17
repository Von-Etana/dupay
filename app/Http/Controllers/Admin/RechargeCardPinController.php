<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RechargeCardPin;
use App\Models\Telecom\Network;
use Illuminate\Http\Request;

class RechargeCardPinController extends Controller
{
    public function index(Request $request)
    {
        $query = RechargeCardPin::with('network');
        if ($request->filled('network_id')) $query->where('network_id', $request->network_id);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) $query->where(function ($q) use ($request) { $q->where('serial', 'like', '%'.$request->search.'%'); });
        $sort = in_array($request->get('sort'), ['value','status','created_at']) ? $request->get('sort') : 'created_at';
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';
        $pins = $query->orderBy($sort, $direction)->paginate((int) $request->get('per_page', 25))->withQueryString();
        $networks = Network::orderBy('name')->get();
        $summary = RechargeCardPin::selectRaw('status, count(*) total')->groupBy('status')->pluck('total', 'status');
        return view('admin.recharge_card_pins.index', compact('pins', 'networks', 'summary'));
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
