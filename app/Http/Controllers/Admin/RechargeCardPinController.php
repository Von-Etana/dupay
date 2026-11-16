<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RechargeCardPin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RechargeCardPinController extends Controller
{
    public function index(Request $request)
    {
        $query = RechargeCardPin::query();
        if ($request->filled('network')) $query->where('network', $request->network);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('batch')) $query->where('batch_number', 'like', '%'.$request->batch.'%');
        if ($request->filled('search')) $query->where(function ($q) use ($request) { $q->where('serial_number','like','%'.$request->search.'%')->orWhere('batch_number','like','%'.$request->search.'%'); });
        $sort = in_array($request->get('sort'), ['network','denomination','status','expires_at','created_at']) ? $request->get('sort') : 'created_at';
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';
        $pins = $query->orderBy($sort, $direction)->paginate((int) $request->get('per_page', 25))->withQueryString();
        $summary = RechargeCardPin::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total','status');
        return view('admin.recharge_card_pins.index', compact('pins','summary'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['network'=>'required|string|max:32','denomination'=>'required|numeric|min:1','pin'=>'required|string|max:255','serial_number'=>'nullable|string|max:255','batch_number'=>'nullable|string|max:255','supplier'=>'nullable|string|max:255','expires_at'=>'nullable|date']);
        $data['added_by'] = auth('admin')->id() ?: auth()->id();
        RechargeCardPin::create($data);
        return back()->with('message','Recharge card PIN added successfully.');
    }

    public function import(Request $request)
    {
        $request->validate(['file'=>'required|file|mimes:csv,txt|max:5120']);
        $rows = array_map('str_getcsv', file($request->file('file')->getRealPath()));
        $headers = array_map(fn($h) => strtolower(trim($h)), array_shift($rows));
        $required = ['network','denomination','pin'];
        abort_unless(!array_diff($required, $headers), 422, 'CSV must contain network, denomination and pin columns.');
        $created = 0; $skipped = 0;
        foreach ($rows as $row) { $item = array_combine($headers, array_pad($row, count($headers), null)); if (!$item || empty($item['pin']) || RechargeCardPin::where('network',$item['network'])->where('pin', $item['pin'])->exists()) { $skipped++; continue; } $item['added_by'] = auth('admin')->id() ?: auth()->id(); RechargeCardPin::create(array_intersect_key($item, array_flip((new RechargeCardPin)->getFillable()))); $created++; }
        return back()->with('message', "Import complete: {$created} added, {$skipped} skipped.");
    }

    public function reveal(RechargeCardPin $rechargeCardPin)
    { return response()->json(['pin' => $rechargeCardPin->pin]); }
}
