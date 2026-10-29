<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VirtualCardNGN;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\TraitsFolder\MailTrait;

class VirtualNGNCardController extends Controller
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
        
        $cards = VirtualCardNGN::with('user')->latest()->paginate(20);
        return view('admin.visual_cards.ngn.index', compact('cards'), $data);
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
    
        $card = VirtualCardNGN::findOrFail($id);
    
        $cardData = getVirtualNairaCard($card->card_id);
    
        $transResponse = getVirtualNairaCardATMHistory($card->card_id);
    
        if (isset($transResponse['success']) && $transResponse['success'] === true) {
            $transactions = $transResponse['data'] ?? [];
        } else {
            Log::warning('Card transactions fetch failed', [
                'card_id' => $card->id,
                'error' => $transResponse['message'] ?? 'Unknown error',
                'details' => $transResponse['error'] ?? null
            ]);
            $transactions = [];
        }
    
        return view('admin.visual_cards.ngn.show', array_merge(
            $data,
            compact('card', 'cardData', 'transactions')
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
            
        $card = VirtualCardNGN::where('id', $card_id)->first();
    
        if (!$card) {
            Session::flash('message', 'Card not found.');
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return back();
        }
    
        $action = $card->status === 'active' ? 'inactive' : 'active';
            
        $response = updateVirtualCardStatus($card->card_id, $action);
    
        if ($response['success']) {
    
            // Update card
            $card->update([
                'status' => $action
            ]);
    
            try {
    
                $cardOwner = $card->user;
    
                if ($cardOwner && $cardOwner->email) {
    
                    $statusText = $action === 'active' ? 'Activated' : 'Deactivated';
    
                    $message = "
                    This is to inform you that your Dupay Virtual Naira Card has been successfully {$statusText}.
                    
                    If you initiated this action, no further steps are required.
                    
                    However, if you do not recognize this activity, we strongly advise that you secure your account immediately by contacting our support team.
                    
                    Your security remains our highest priority.
                    
                    Thank you for choosing Dupay.
                    
                    Warm regards,  
                    Dupay Security Team
                    ";
    
                    $this->sendMail(
                        $cardOwner->email,
                        $cardOwner->profile->first_name ?? 'Customer',
                        'Dupay Security Alert — Virtual Card '.$statusText,
                        $message
                    );
                }
    
            } catch (\Throwable $th) {
                \Log::error('Virtual card status email failed', [
                    'card_id' => $card->id,
                    'error'   => $th->getMessage()
                ]);
            }
    
            $statusText = $action === 'active' ? 'Activated' : 'Deactivated';
    
            Session::flash('message', 'Virtual Card ' . $statusText . ' successfully.');
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
    
    // public function freeze(Request $request)
    // {
    //     if (empty($request->card_id)) {
    //         Session::flash('message', 'This card does not have a valid Card ID.');
    //         Session::flash('type', 'error');
    //         Session::flash('title', 'Error');
    //         return back();
    //     }
        
    //     $card_id = $request->card_id;
        
    //     $card = VirtualCardNGN::where('id', $card_id)->first();
    
    //     $action = $card->status === 'active' ? 'inactive' : 'active';
        
    //     $response = updateVirtualCardStatus($card->card_id, $action);
    
    //     if ($response['success']) {
    //         $card->update([
    //             'status' => $action
    //         ]);
    
    //         Session::flash('message', 'Virtual Card ' . ucwords($card->status == 'active' ? 'Deactivated' : 'Activated') . ' successfully.');
    //         Session::flash('type', 'success');
    //         Session::flash('title', 'Success');
    //     } else {
    //         $errorMessage = $response['response']['message'] ?? $response['message'] ?? 'An error occurred.';
    //         Session::flash('message', $errorMessage);
    //         Session::flash('type', 'error');
    //         Session::flash('title', 'Error');
    //     }
    
    //     return back();
    // }
    
    public function reset(Request $request)
    {
        $request->validate([
            'pin' => 'required|numeric|min:1',
            'id' => 'required'
        ]);
        $card_id = $request->id;
    
        $card = VirtualCardNGN::where('id', $card_id)->first();
    
        $payload = [
            'card_id' => $card->card_id,
            'amount' => $request->amount,
            'public_key' => env('STROWALLET_PUBLIC_KEY'),
        ];
        

        $response = resetVirtualCardPin($card->card_id, $request->pin);

        if ($response['success']) {
            $message = $response['message'];

            Session::flash('message', $message);
            Session::flash('type', 'success');
            Session::flash('title', 'Success');
        } else {
            // Attempt to extract a detailed message from the error structure
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
    
        return redirect()->back();
    }
}
