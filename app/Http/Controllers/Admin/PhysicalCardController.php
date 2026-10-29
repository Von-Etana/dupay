<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PhysicalCard;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use App\TraitsFolder\MailTrait;

class PhysicalCardController extends Controller
{
    use MailTrait;
    
    // public function index(Request $request)
    // {
    //     $data["page_title"] = "Assign Card";
    //     $data["page_sub_title"] = "Assign ATM Card";
    //     $data["breadcrumb"] = [
    //         ["title" => "Home", "url" => route('admin.index')],
    //         ["title" => $data["page_sub_title"], "url" => null],
    //     ];
    
    //     $query = PhysicalCard::with('user.profile');
    
    //     // 🔍 Apply search filter
    //     if ($request->has('search') && !empty($request->search)) {
    //         $search = $request->search;
    //         $query->where(function ($q) use ($search) {
    //             $q->where('card_id', 'like', "%{$search}%")
    //               ->orWhere('account_id', 'like', "%{$search}%")
    //               ->orWhere('currency', 'like', "%{$search}%")
    //               ->orWhere('card_pan', 'like', "%{$search}%")
    //               ->orWhere('masked_pan', 'like', "%{$search}%")
    //               ->orWhereHas('user.profile', function ($q) use ($search) {
    //                   $q->where('first_name', 'like', "%{$search}%")
    //                     ->orWhere('last_name', 'like', "%{$search}%");
    //               });
    //         });
    //     }
    
    //     $physical_cards = $query->paginate(20)->appends($request->only('search'));
    
    //     return view('admin.physical_cards.index', compact('physical_cards') + $data);
    // }

    public function index()
    {
        $data["page_title"] = "Assign Card";
        $data["page_sub_title"] = "Assign ATM Card";
        $data["breadcrumb"] = [
            ["title" => "Home", "url" => route('admin.index')],
            ["title" => $data["page_sub_title"], "url" => null],
        ];
        $data["ajaxUrl"] = route('admin.user.physical_cards.ajax');

        return view('admin.physical_cards.index', $data);
    }
    
    public function cardAjax(Request $request)
    {
        $status = $request->query('status', '');
    
        $cards = PhysicalCard::with('user')
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderByDesc('id');
    
        return DataTables::eloquent($cards)
            ->addColumn('user_name', fn($c) => optional($c->user->profile)->username ?? 'N/A')
            ->addColumn('user_email', fn($c) => optional($c->user)->email ?? 'N/A')
            ->editColumn('card_pan', fn($c) => $c->masked_pan ?? maskCardPan($c->card_pan))
            ->editColumn('cvv', fn($c) => '•••')
            ->editColumn('brand', fn($c) => strtoupper($c->brand ?? '—'))
            ->editColumn('expiry', fn($c) => sprintf('%02d/%s', $c->expiry_month, $c->expiry_year))
            // Tell DataTables how to SEARCH these computed columns (not real DB columns)
            ->filterColumn('expiry', function ($query, $keyword) {
                $query->whereRaw("CONCAT(LPAD(expiry_month,2,'0'),'/',expiry_year) LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('user_name', function ($query, $keyword) {
                $query->whereHas('user.profile', function ($q) use ($keyword) {
                    $q->where('username', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('user_email', function ($query, $keyword) {
                $query->whereHas('user', function ($q) use ($keyword) {
                    $q->where('email', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('spending_limit', function ($query, $keyword) {
                $query->where('spending_limit_amount', 'like', "%{$keyword}%")
                      ->orWhere('spending_limit_interval', 'like', "%{$keyword}%");
            })
            ->filterColumn('permissions', function ($query, $keyword) {
                // permissions is built from atm/pos/web/mobile flags — not directly searchable
                // no-op filter so DataTables doesn't try to query a non-existent column
            })
            ->editColumn('status', function ($c) {
                $badgeClass = match ($c->status) {
                    'active'   => 'badge bg-success',
                    'inactive' => 'badge bg-secondary',
                    'blocked'  => 'badge bg-danger',
                    'pending'  => 'badge bg-warning text-dark',
                    default    => 'badge bg-light text-dark',
                };
                return '<span class="'.$badgeClass.'">'.ucfirst($c->status).'</span>';
            })
            ->editColumn('currency', fn($c) => strtoupper($c->currency ?? '—'))
            ->editColumn('spending_limit', fn($c) =>
                getPrice($c->spending_limit_amount).' / '.ucfirst($c->spending_limit_interval)
            )
            ->editColumn('permissions', function ($c) {
                $flags = [];
                if ($c->atm) $flags[] = 'ATM';
                if ($c->pos) $flags[] = 'POS';
                if ($c->web) $flags[] = 'WEB';
                if ($c->mobile) $flags[] = 'MOBILE';
                return implode(', ', $flags) ?: '—';
            })
            ->editColumn('blocked', fn($c) => $c->blocked ? 'Yes' : 'No')
            ->editColumn('blocked_at', fn($c) =>
                $c->blocked_at ? $c->blocked_at->format('Y-m-d H:i') : '—'
            )
            ->editColumn('block_reason', fn($c) => $c->block_reason ?? '—')
            ->editColumn('bin_check_name', fn($c) => $c->bin_check_name ?? '—')
            ->editColumn('type', fn($c) => ucfirst($c->type ?? '—'))
            ->editColumn('card_id', fn($c) => $c->card_id ?? '—')
            ->editColumn('account_id', fn($c) => $c->account_id ?? '—')
            ->editColumn('customer_id', fn($c) => $c->customer_id ?? '—')
            ->editColumn('setup_step', fn($c) => $c->setup_step ?? '—')
            ->editColumn('shipping_details', fn($c) => $c->shipping_details ?? '—')
            ->editColumn('send_to', fn($c) => $c->send_to ?? '—') // ✅ Added this missing column
            ->editColumn('created_at', fn($c) => $c->created_at->format('Y-m-d H:i'))
            ->addColumn('action', fn($c) => view('admin.physical_cards._actions', compact('c'))->render())
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function create()
    {
        $data["page_title"] = "Assign Card";
        $data["page_sub_title"] = "Assign ATM Card";
        $data["breadcrumb"] = [
            ["title" => "Home", "url" => route('admin.index')],
            ["title" => $data["page_sub_title"], "url" => null],
        ];

        $users = User::all();
        return view('admin.physical_cards.create', compact('users'), $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'          => 'required|exists:users,id',
            'card_pan'         => 'required|string',
            'cvv'              => 'required|digits:3',
            'brand'            => 'required|string',
            'masked_pan'       => 'nullable|string',
            'expiry_month'     => 'nullable|digits:2',
            'expiry_year'      => 'nullable|digits:4',
            'status'           => 'nullable|string',
            'atm'              => 'nullable|boolean',
            'pos'              => 'nullable|boolean',
            'web'              => 'nullable|boolean',
            'mobile'           => 'nullable|boolean',
            'spending_limit_amount' => 'nullable|numeric',
            'spending_limit_interval' => 'nullable|string',
            'pin'              => 'nullable|string',
            'send_to'          => 'nullable|string',
            'bin_check_name'   => 'nullable|string',
            'type'             => 'nullable|string',
            'card_id'          => 'nullable|string',
            'account_id'       => 'nullable|string',
            'currency'         => 'nullable|string',
            'card_hash'        => 'nullable|string',
            'setup_step'       => 'nullable|string',
            'customer_id'      => 'nullable|string',
        ]);

        PhysicalCard::create($validated);

        session()->flash('message', 'Card assigned successfully.');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return redirect()->route('admin.physical_cards.index');
    }

    public function edit(PhysicalCard $physical_card)
    {
        $data["page_title"] = "Edit Card";
        $data["page_sub_title"] = "Edit ATM Card";
        $data["breadcrumb"] = [
            ["title" => "Home", "url" => route('admin.index')],
            ["title" => $data["page_sub_title"], "url" => null],
        ];

        $users = User::all();
        return view('admin.physical_cards.edit', compact('physical_card', 'users'), $data);
    }
    
    public function show(PhysicalCard $physical_card)
    {
        $data["page_title"] = "View Card";
        $data["page_sub_title"] = "View ATM Card";
        $data["breadcrumb"] = [
            ["title" => "Home", "url" => route('admin.index')],
            ["title" => $data["page_sub_title"], "url" => null],
        ];

        return view('admin.physical_cards.view', compact('physical_card'), $data);
    }

    public function update(Request $request, PhysicalCard $card)
    {
        try {
            $request->validate([
                'user_id'                 => 'required|exists:users,id',
                'card_pan'                => 'required|string|max:50',
                'cvv'                     => 'required|string|max:10',
                'brand'                   => 'required|string|max:50',
                'masked_pan'              => 'nullable|string|max:50',
                'expiry_month'            => 'nullable|digits:2',
                'expiry_year'             => 'nullable|digits:4',
                'status'                  => 'nullable|in:active,inactive,blocked,pending',
                'atm'                     => 'nullable|boolean',
                'pos'                     => 'nullable|boolean',
                'web'                     => 'nullable|boolean',
                'mobile'                  => 'nullable|boolean',
                'spending_limit_amount'   => 'nullable|numeric|min:0',
                'spending_limit_interval' => 'nullable|string|max:50',
                'send_to'                 => 'nullable|string|max:100',
                'bin_check_name'          => 'nullable|string|max:100',
                'type'                    => 'nullable|string|max:50',
                'card_id'                 => 'nullable|string|max:100',
                'account_id'              => 'nullable|string|max:100',
                'currency'                => 'nullable|string|max:10',
                'card_hash'               => 'nullable|string|max:255',
                'setup_step'              => 'nullable|string|max:50',
                'customer_id'             => 'nullable|string|max:100',
            ]);
    
            $card->user_id = $request->user_id;
            $card->card_pan = $request->card_pan;
            $card->cvv = $request->cvv;
            $card->brand = $request->brand;
            $card->masked_pan = $request->masked_pan;
            $card->expiry_month = $request->expiry_month;
            $card->expiry_year = $request->expiry_year;
            $card->status = $request->status;
            $card->atm = $request->atm;
            $card->pos = $request->pos;
            $card->web = $request->web;
            $card->mobile = $request->mobile;
            $card->spending_limit_amount = $request->spending_limit_amount;
            $card->spending_limit_interval = $request->spending_limit_interval;
            $card->send_to = $request->send_to;
            $card->bin_check_name = $request->bin_check_name;
            $card->type = $request->type;
            $card->card_id = $request->card_id;
            $card->account_id = $request->account_id;
            $card->currency = $request->currency;
            $card->card_hash = $request->card_hash;
            $card->setup_step = $request->setup_step;
            $card->customer_id = $request->customer_id;
    
            $card->save();
    
            session()->flash('message', 'Card updated successfully.');
            Session::flash('type', 'success');
            Session::flash('title', 'Success');
    
            return redirect()->route('admin.physical_cards.index');
    
        } catch (\Throwable $e) {
            session()->flash('message', 'An error occurred while updating the card: ' . $e->getMessage());
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
    
            return redirect()->back()->withInput();
        }
    }

    public function destroy(PhysicalCard $physical_card)
    {
        $physical_card->delete();

        session()->flash('message', 'Card deleted successfully.');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return redirect()->route('admin.physical_cards.index');
    }
    
   public function resetCardPin(Request $request, PhysicalCard $physical_card)
{
    $validated = $request->validate([
        'new_pin'     => 'required|digits:4',
        'confirm_pin' => 'required|same:new_pin',
    ]);

    try {

        $client = new \GuzzleHttp\Client();

        $query = http_build_query([
            'public_key' => env('STROWALLET_PUBLIC_KEY'),
            'card_id'    => $physical_card->card_id,
            'new_pin'    => $validated['new_pin'],
        ]);

        $response = $client->put("https://strowallet.com/api/naira_resetPin/?$query", [
            'headers' => ['accept' => 'application/json'],
        ]);

        $result = json_decode($response->getBody(), true);

        if (!($result['success'] ?? false)) {
            session()->flash('message', $result['message'] ?? 'Failed to reset PIN.');
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return back();
        }

        // ✅ Sync new PIN to local DB
        $physical_card->pin = Hash::make($validated['new_pin']);
        $physical_card->save();

        /*
        |--------------------------------------------------------------------------
        | SEND EMAIL TO CARD OWNER (SAFE BLOCK)
        |--------------------------------------------------------------------------
        */
        try {

            $user = $physical_card->user;

            if ($user && getSiteSettings("email_notify") === "Yes") {

                $message = "
    Your physical card pin has been reset successfully by the administrator.
                ";

                $this->sendMail(
                    $user->email,
                    $user->profile->first_name ?? $user->name ?? 'Customer',
                    'Physical Card PIN Reset Notification',
                    $message
                );
            }

        } catch (\Throwable $th) {
            \Log::warning('Physical card PIN reset email failed', [
                'card_id' => $physical_card->id,
                'error'   => $th->getMessage()
            ]);
        }

        session()->flash('message', 'Card PIN reset successfully.');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return back();

    } catch (\GuzzleHttp\Exception\RequestException $e) {

        $errorBody = $e->getResponse()
            ? json_decode($e->getResponse()->getBody(), true)
            : $e->getMessage();

        session()->flash('message', is_array($errorBody) ? ($errorBody['message'] ?? 'Request failed') : $errorBody);
        Session::flash('type', 'error');
        Session::flash('title', 'Error');

        return back();
    }
}
    
    
}