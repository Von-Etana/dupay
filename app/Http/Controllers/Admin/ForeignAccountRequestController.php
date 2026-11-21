<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ForeignAccountRequest;
use App\Models\ForeignAccount;
use Illuminate\Support\Facades\Session;
use App\TraitsFolder\MailTrait;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ForeignAccountRequestController extends Controller
{
    use MailTrait;
    
    public function index()
    {
        $data["page_title"] = "Foreign Account";
        $data["page_sub_title"] = "View All Request";
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
        
        $requests = ForeignAccountRequest::with('user')->latest()->paginate(20);

        return view('admin.foreign-accounts.index', compact('requests') + $data);
    }

    public function show($id)
    {
        $data["page_title"] = "Foreign Account";
        $data["page_sub_title"] = "View Account Request";
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
        
        $request = ForeignAccountRequest::with('user')->findOrFail($id);

        return view('admin.foreign-accounts.show', compact('request') + $data);
    }

    public function edit($id)
    {
        $data["page_title"] = "Foreign Account";
        $data["page_sub_title"] = "View Account Request";
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
        
        $requestData = ForeignAccountRequest::findOrFail($id);

        return view('admin.foreign-accounts.edit', compact('requestData') + $data);
    }

    public function update(Request $request, $id)
    {
        $requestData = ForeignAccountRequest::findOrFail($id);
    
        $data = $request->all();
        
        if ($request->hasFile('document')) {
            $data['document'] = $request->file('document')->store('foreign_accounts/documents', 'public');
        }
    
        if ($request->hasFile('document2')) {
            $data['document2'] = $request->file('document2')->store('foreign_accounts/documents2', 'public');
        }
    
        $requestData->update($data);
    
        session()->flash('message', 'Request updated successfully.');
        session()->flash('type', 'success');
        session()->flash('title', 'Success');
    
        return redirect()->back();
    }

    public function approve($id)
    {
        return $this->provisionAccount($id);
    }

    public function resubmitKyc($id)
    {
        $requestData = ForeignAccountRequest::with('user')->findOrFail($id);
        $personId = optional($requestData->user)->graph_person_id;
        if (!$personId) return $this->errorRedirect('Create the Graph person before resubmitting KYC.');
        $document = ltrim(preg_replace('#^/?storage/#', '', (string) $requestData->document), '/');
        if (!$document || !\Illuminate\Support\Facades\Storage::disk('public')->exists($document)) {
            return $this->errorRedirect('Upload a valid identity document before resubmitting KYC.');
        }
        $verification = optional($requestData->user)->walletVerification;
        if (!$verification) return $this->errorRedirect('Identity number is missing.');
        $type = strtolower((string) $requestData->document_type);
        $type = ['national_id' => 'nin', 'national-id' => 'nin', 'nin' => 'nin', 'drivers_licence' => 'drivers_license', 'drivers-license' => 'drivers_license'][$type] ?? $type;
        if (!in_array($type, ['passport', 'drivers_license', 'nin', 'voters_card'], true)) return $this->errorRedirect('Select a supported identity document type.');
        // Older KYC records stored the selected NIN in the foreign-account
        // request's `bvn` column, while newer records use wallet_verifications.nin.
        $identityNumber = $type === 'nin'
            ? ($verification->nin ?: $requestData->bvn ?: $verification->bvn ?: null)
            : ($verification->bvn ?: $requestData->bvn ?: null);
        if (!$identityNumber) return $this->errorRedirect('The selected identity number is missing from the wallet verification record.');
        try {
            $response = \Illuminate\Support\Facades\Http::withToken(config('services.graph.api_key'))
                ->timeout(30)->patch('https://api.useoval.com/person/'.rawurlencode($personId).'/kyc', [
                    'id_type' => $type,
                    'id_number' => (string) $identityNumber,
                    'id_country' => strtoupper($requestData->country ?: 'NG'),
                    'id_upload' => asset('storage/'.$document),
                ]);
            if (!$response->successful() || $response->json('status') !== 'success') {
                $providerMessage = $response->json('message') ?: $response->json('error.message');
                \Log::warning('Graph KYC resubmission failed', ['request_id' => $id, 'http_status' => $response->status(), 'provider_message' => $providerMessage, 'response' => $response->json()]);
                return $this->errorRedirect('Graph did not accept the KYC update'.($providerMessage ? ': '.$providerMessage : '. Check the document and identity details.'));
            }
            return redirect()->back()->with('message', 'KYC update submitted to Graph successfully.');
        } catch (\Throwable $e) {
            \Log::error('Graph KYC resubmission error', ['request_id' => $id, 'error' => $e->getMessage()]);
            return $this->errorRedirect('Graph KYC update could not be completed. Please retry.');
        }
    }
    
    public function createBankAccount($id)
    {
        return $this->provisionAccount($id);
    }

    private function provisionAccount($id)
    {
        $requestData = ForeignAccountRequest::findOrFail($id);
        if ($requestData->status === 'declined') {
            return $this->errorRedirect('This request was declined. Edit it and return it to pending before retrying.');
        }
        if (!$requestData->user) {
            return $this->errorRedirect('User not found.');
        }
        $user = $requestData->user;
        if (!$user->hasVerifyWallet) {
            return $this->errorRedirect('Kindly verify your account.');
        }

        try {
            if (!$user->graph_person_id) {
                $person = CreateGraphPerson($user, $requestData);
                if (empty($person['status']) || empty($user->fresh()->graph_person_id)) {
                    return $this->errorRedirect('Graph Create Person API error: ' . ($person['message'] ?? 'No person ID returned.'));
                }
                $user->refresh();
            }

            // A previous request may have created an account while issuance was pending.
            // Fetch it instead of submitting another account creation request.
            $local = ForeignAccount::where('user_id', $user->id)
                ->where('currency', $requestData->currency)
                ->whereNotNull('account_id')->latest('id')->first();
            $result = $local
                ? FetchGraphBankAccount($local->account_id)
                : CreateGraphBankAccount($user, $requestData->currency);
            if (empty($result['status']) || empty($result['data']['id'])) {
                return $this->errorRedirect('Graph account API error: ' . ($result['message'] ?? 'No account ID returned.'));
            }

            $account = $result['data'];
            $accountId = $account['id'];
            DB::transaction(function () use ($user, $requestData, $account, $accountId) {
                $localAccount = ForeignAccount::where('account_id', $accountId)->first()
                    ?: ForeignAccount::where('user_id', $user->id)
                        ->where('currency', $requestData->currency)
                        ->whereNull('account_id')->latest('id')->first()
                    ?: new ForeignAccount();
                if ($localAccount->exists && (int) $localAccount->user_id !== (int) $user->id) {
                    throw new \RuntimeException('Graph account is linked to a different user.');
                }
                $localAccount->fill([
                    'user_id' => $user->id,
                    'account_id' => $accountId,
                    'holder_id' => $account['holder_id'] ?? $user->graph_person_id,
                    'account_number' => $account['account_number'] ?? '',
                    'account_name' => $account['account_name'] ?? null,
                    'bank_name' => $account['bank_name'] ?? null,
                    'bank_code' => $account['bank_code'] ?? null,
                    'routing_number' => $account['routing_number'] ?? null,
                    'bank_address' => json_encode($account['bank_address'] ?? []),
                    'balance' => $account['balance'] ?? 0,
                    'currency' => $account['currency'] ?? $requestData->currency,
                    'status' => $account['status'] ?? 'pending',
                    'bank_data' => json_encode($account),
                ]);
                $localAccount->save();

                if (!empty($account['account_number'])) {
                    $requestData->status = 'approved';
                    $requestData->decline_reason = null;
                    $requestData->save();
                }
            });

            if (empty($account['account_number'])) {
                session()->flash('message', 'Graph accepted the account request. Issuance is pending; retry this action to refresh its status.');
                session()->flash('type', 'info');
                session()->flash('title', 'Pending');
                return redirect()->back();
            }
            return $this->successRedirect('Graph account created and saved locally.');
        } catch (\Exception $e) {
            \Log::error('Graph account provisioning failed', ['request_id' => $id, 'error' => $e->getMessage()]);
            return $this->errorRedirect('Graph account could not be saved. Check the server log before retrying.');
        }
    }
    
    private function successRedirect($message)
    {
        session()->flash('message', $message);
        session()->flash('type', 'success');
        session()->flash('title', 'Success');
        return redirect()->back();
    }
    
    private function errorRedirect($message)
    {
        session()->flash('message', $message);
        session()->flash('type', 'error');
        session()->flash('title', 'Error');
        return redirect()->back();
    }

    public function decline(Request $request, $id)
    {
        $data = $request->all();
        
        $validatedData = Validator::make($data, [
            'reason' => 'required|string|max:1000',
        ]);
        
        if ($validatedData->fails()) {
            $error = implode(', ', $validatedData->errors()->all());
            session()->flash('message', $error);
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
        }
    
        $requestData = ForeignAccountRequest::findOrFail($id);
    
        $requestData->update([
            'status' => 'declined',
            'reason' => $request->reason,
        ]);
    
        $user = $requestData->user;
    
        if ($user && getSiteSettings("email_notify") == "Yes") {
            $message = "Hello {$user->profile->username},\n\n".
                       "We regret to inform you that your request for a foreign account has been declined.\n\n".
                       "Reason for decline: {$request->reason}\n\n".
                       "If you believe this was a mistake or would like to provide more information, ".
                       "please contact our support team.\n\n".
                       "Best regards,\n".
                       config('app.name') . " Team";
    
            $this->sendMail($user->email, $user->profile->first_name ?? "", "Foreign Account Request Details Declined", $message);
        }
    
        session()->flash('message', 'Request declined successfully.');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');
    
        return redirect()->back();
    }


    public function destroy($id)
    {
        $requestData = ForeignAccountRequest::findOrFail($id);
        $requestData->delete();
        
        session()->flash('message', 'Request deleted successfully.');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return redirect()->back();
    }
}
