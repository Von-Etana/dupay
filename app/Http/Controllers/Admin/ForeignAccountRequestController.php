<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ForeignAccountRequest;
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
        $requestData = ForeignAccountRequest::findOrFail($id);
    
        if ($requestData->status !== 'pending') {
            return $this->errorRedirect('This request has already been processed.');
        }
    
        if (!$requestData->user) {
            return $this->errorRedirect('User not found.');
        }
    
        $user = $requestData->user;
        
        if (!$user->hasVerifyWallet) {
            return $this->errorRedirect('Kindly verify your account.');
        }
    
        DB::beginTransaction();
    
        try {
            if (empty($user->graph_person_id)) {
                $response = CreateGraphPerson($user, $requestData);

                if (empty($response['status'])) {
                    DB::rollBack();

                    return $this->errorRedirect(
                        'Graph Create Person API error: ' . ($response['message'] ?? 'Unknown error.')
                    );
                }
            }

            $return = CreateGraphBankAccount($user, $requestData->currency);
    
                if ($return['status']) {
    
                    $requestData->update([
                        'status'         => 'approved',
                        'decline_reason' => null,
                        'account_number' => $return['data']['accountNumber'] ?? null,
                        'bank_name'      => $return['data']['bankName'] ?? null,
                    ]);
    
                    DB::commit();
    
                    return $this->successRedirect(
                        'Request approved and virtual account created successfully on Graph.'
                    );
    
                } else {
                    DB::rollBack();
    
                    return $this->errorRedirect(
                        'Graph Create Bank API error: ' . ($return['message'] ?? 'Unknown error.')
                    );
                }
    
        } catch (\Exception $e) {
            DB::rollBack();
    
            return $this->errorRedirect(
                'Unexpected error: ' . $e->getMessage()
            );
        }
    }
    
    public function createBankAccount($id)
    {
        $requestData = ForeignAccountRequest::findOrFail($id);
    
        if ($requestData->status !== 'pending') {
            return $this->errorRedirect('This request has already been processed.');
        }
    
        if (!$requestData->user) {
            return $this->errorRedirect('User not found.');
        }
    
        $user = $requestData->user;
        
        if (!$user->hasVerifyWallet) {
            return $this->errorRedirect('Kindly verify your account.');
        }
    
        DB::beginTransaction();
    
        try {
            $return = CreateGraphBankAccount($user, $requestData->currency);

            if ($return['status']) {

                $requestData->update([
                    'status'         => 'approved',
                    'decline_reason' => null,
                    'account_number' => $return['data']['accountNumber'] ?? null,
                    'bank_name'      => $return['data']['bankName'] ?? null,
                ]);

                DB::commit();

                return $this->successRedirect(
                    'Request approved and virtual account created successfully on Graph.'
                );

            } else {
                DB::rollBack();

                return $this->errorRedirect(
                    'Graph Create Bank API error: ' . ($return['message'] ?? 'Unknown error.')
                );
            }

        } catch (\Exception $e) {
            DB::rollBack();
    
            return $this->errorRedirect(
                'Unexpected error: ' . $e->getMessage()
            );
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
