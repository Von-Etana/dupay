<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssignedCardCredential;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\PhysicalCard;

class AssignedCardCredentialController extends Controller
{
    public function index()
    {
        $data["page_title"] = "Assign Card";
        $data["page_sub_title"] = "Assign Atm Card";
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
        
        $credentials = AssignedCardCredential::with('user')->latest()->paginate(20);
        return view('admin.card_credentials.index', compact('credentials'), $data);
    }

    public function create()
    {
        $data["page_title"] = "Assign Card";
        $data["page_sub_title"] = "Assign Atm Card";
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
        
        $cards = PhysicalCard::where('setup_step', '0')->orWhere('setup_step', '1')->get();
        return view('admin.card_credentials.create', compact('cards'), $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'card_id'          => 'required|exists:physical_cards,id',
            'card_pan_last6'   => 'required|digits:6',
            'cvv'              => 'required|digits:3',
            'card_pan'         => 'required',
            'brand'            => 'required',
        ]);
        
        $card = PhysicalCard::where('id', $request->card_id)->first();
        
        $card->setup_step = '1';
        $card->save();

        AssignedCardCredential::create([
            'user_id' => $card->user_id,
            'card_pan_last6' => $request->card_pan_last6,
            'card_pan' => $request->card_pan,
            'cvv' => $request->cvv,
            'brand' => $request->brand
        ]);

        session()->flash('message', 'Card credential assigned successfully.');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return redirect()->route('admin.card_credentials.index');
    }

    public function edit(AssignedCardCredential $cardCredential)
    {
        $data["page_title"] = "Assign Card";
        $data["page_sub_title"] = "Assign Atm Card";
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
        
        $cards = PhysicalCard::all();
        return view('admin.card_credentials.edit', compact('cardCredential', 'cards'), $data);
    }

    public function update(Request $request, AssignedCardCredential $cardCredential)
    {
        $validated = $request->validate([
            'user_id'          => 'required|exists:users,id',
            'card_pan_last6'   => 'required|digits:6',
            'card_pan'         => 'required',
            'cvv'              => 'required|digits:3',
            'used'             => 'required|boolean',
            'brand'            => 'required',
            'delivery_status'  => 'required',
        ]);

        $cardCredential->update($validated);

        session()->flash('message', 'Card credential updated successfully.');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return redirect()->route('admin.card_credentials.index');
    }

    public function show(AssignedCardCredential $cardCredential)
    {
        return view('admin.card_credentials.show', compact('cardCredential'));
    }

    public function destroy(AssignedCardCredential $cardCredential)
    {
        $cardCredential->delete();

        session()->flash('message', 'Card credential deleted successfully.');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return redirect()->route('admin.card_credentials.index');
    }
}
