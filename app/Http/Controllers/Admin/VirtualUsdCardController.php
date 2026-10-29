<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VirtualCardUSD;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\TraitsFolder\MailTrait;

class VirtualUsdCardController extends Controller
{
    use MailTrait;

    public function index()
    {
        $data["page_title"] = "Virtual Card";
        $data["page_sub_title"] = "Virtual Card";
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

        $cards = VirtualCardUSD::with('user')->latest()->paginate(20);
        return view('admin.visual_cards.usd.index', compact('cards'), $data);
    }

    public function show($id)
    {
        // Page setup
        $data = [
            "page_title" => "Virtual Card",
            "page_sub_title" => "Virtual Card",
            "breadcrumb" => [
                [
                    "title" => "Home",
                    "url" => route('admin.index')
                ],
                [
                    "title" => "Virtual Card",
                    "url" => null
                ],
            ]
        ];

        // Always load the card from our own DB first — the page renders from
        // this no matter what StroWallet does.
        $card = VirtualCardUSD::findOrFail($id);

        // ── Live provider data is BEST-EFFORT ────────────────────────────────
        // StroWallet's detail/transactions endpoints are intermittently slow.
        // Only call them when we actually have a card_id, guard every call, and
        // fall back to the DB record — so this page never hangs on a 500 and a
        // provider outage can't freeze the admin.
        $cardData     = null;
        $transactions = [];

        if (!empty($card->card_id)) {
            $payload = [
                'card_id'    => $card->card_id,
                'public_key' => env('STROWALLET_PUBLIC_KEY'),
            ];

            try {
                $cardData = getCardDetails($payload);
            } catch (\Throwable $e) {
                Log::warning('Admin: USD card detail fetch failed', [
                    'card_id' => $card->id,
                    'error'   => $e->getMessage(),
                ]);
            }

            try {
                $transResponse = getCardTransactions($payload);
                if (isset($transResponse['success']) && $transResponse['success'] === true) {
                    $transactions = $transResponse['data'] ?? [];
                }
            } catch (\Throwable $e) {
                Log::warning('Admin: USD card transactions fetch failed', [
                    'card_id' => $card->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return view('admin.visual_cards.usd.show', array_merge(
            $data,
            compact('card', 'transactions', 'cardData')
        ));
    }

    public function freeze(Request $request)
    {
        if (empty($request->card_id)) {
            Session::flash('message', 'This card does not have a valid Card ID.');
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return back();
        }

        $card_id = $request->card_id;

        $card = VirtualCardUSD::where('id', $card_id)->first();

        if (!$card) {
            Session::flash('message', 'Card not found.');
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return back();
        }

        $action = $card->freeze_status === 'freeze' ? 'unfreeze' : 'freeze';
        $status = $card->freeze_status === 'unfreeze' ? 'frozen' : 'active';

        $response = freezeOrUnfreezeCard(
            $action,
            $card->card_id,
            env('STROWALLET_PUBLIC_KEY')
        );

        if ($response['success'] && ($response['status_code'] ?? 200) < 300) {

            $card->update([
                'freeze_status' => $action,
                'status' => $status,
            ]);

            try {

                $cardOwner = $card->user;

                if ($cardOwner && $cardOwner->email) {

                    $message = "
                        Your virtual USD card has been ".strtoupper($action)." successfully.

                        If you did not perform this action, please contact support immediately.

                        Thank you for using Dupay.
                    ";

                    $this->sendMail(
                        $cardOwner->email,
                        $cardOwner->profile->first_name ?? 'Customer',
                        'Dupay Virtual Card '.ucwords($action).' Notification',
                        $message
                    );
                }

            } catch (\Throwable $th) {
                \Log::error('Freeze email failed', [
                    'card_id' => $card->id,
                    'error' => $th->getMessage()
                ]);
            }

            Session::flash('message', 'Virtual Card ' . ucwords($action) . ' successfully.');
            Session::flash('type', 'success');
            Session::flash('title', 'Success');

        } else {

            $errorMessage = $response['response']['message']
                ?? $response['message']
                ?? 'An error occurred.';

            Session::flash('message', $errorMessage);
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
        }

        return back();
    }

    public function fund(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'id' => 'required'
        ]);

        if($request->amount < '3'){
            Session::flash('message', 'Minimum amount is $3');
            Session::flash('type', 'error');
            Session::flash('title', 'Error');

            return redirect()->back();
        }

        try {
            $card = VirtualCardUSD::findOrFail($request->id);

            $payload = [
                'card_id' => $card->card_id,
                'amount' => $request->amount,
                'public_key' => env('STROWALLET_PUBLIC_KEY'),
            ];

            $response = fundVCard($payload);

            if ($response['success']) {
                $message = $response['response']['message'] ?? $response['message'] ?? 'Card funded successfully.';

                Session::flash('message', $message);
                Session::flash('type', 'success');
                Session::flash('title', 'Success');
            } else {
                $error = $response['error'] ?? 'Unknown error';
                $errorMessage = 'Card funding failed.';

                if (is_array($error)) {
                    $errorMessage = $error['message'] ?? json_encode($error);
                } elseif (is_string($error)) {
                    $errorMessage = $error;
                }

                Session::flash('message', $errorMessage);
                Session::flash('type', 'error');
                Session::flash('title', 'Error');
            }
        } catch (\Exception $e) {
            Session::flash('message', 'An unexpected error occurred: ' . $e->getMessage());
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
        }

        return redirect()->back();
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'id' => 'required|exists:virtual_card_u_s_d_s,id'
        ]);

        try {
            $card = VirtualCardUSD::findOrFail($request->id);
            $response = withdrawFromCard($card->card_id, $request->amount, env('STROWALLET_PUBLIC_KEY'));

            if (isset($response['success']) && $response['success']) {
                Session::flash('message', 'Withdrawal of $' . $request->amount . ' from Virtual Card successful.');
                Session::flash('type', 'success');
                Session::flash('title', 'Success');
            } else {
                Session::flash('message', $response['message'] ?? 'Withdrawal failed.');
                Session::flash('type', 'error');
                Session::flash('title', 'Error');
            }
        } catch (\Exception $e) {
            Session::flash('message', 'An error occurred: ' . $e->getMessage());
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
        }

        return redirect()->back();
    }

    public function transactions($id)
    {
        $card = VirtualCardUSD::findOrFail($id);
        $response = getCardTransactions($card->card_id);

        return view('admin.visual_cards.usd.transactions', compact('card', 'response'));
    }

    public function destroy(VirtualCardUSD $id)
    {
        $id->delete();

        session()->flash('message', 'Virtual Card deleted successfully.');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return redirect('/admin/virtual-usd-cards');
    }
}