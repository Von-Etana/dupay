<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ForeignAccount;
use Illuminate\Support\Facades\Session;
use App\TraitsFolder\MailTrait;
use App\Models\User;

class ForeignAccountController extends Controller
{
    use MailTrait;
    
    public function index($currency)
    {
        $data["page_title"] = "Foreign Account";
        $data["page_sub_title"] = "View All Foreign Accounts";
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
        
        $accounts = ForeignAccount::with('user')
        ->where('currency', strtoupper($currency))
        ->latest()
        ->paginate(20);

        return view('admin.accounts.index', compact('accounts') + $data);
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
        
        $account = ForeignAccount::with('user')->findOrFail($id);

        return view('admin.accounts.show', compact('account') + $data);
    }
    
    public function edit($id)
    {
        $data["page_title"] = "Foreign Account";
        $data["page_sub_title"] = "View Foreign Account";
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
        
        $accountData = ForeignAccount::findOrFail($id);

        return view('admin.accounts.edit', compact('accountData') + $data);
    }
    
    public function update(Request $request, $id)
    {
        try {
            $accountData = ForeignAccount::findOrFail($id);
    
            $validated = $request->validate([
                'user_id'        => 'required|integer|exists:users,id',
                'account_id'     => 'nullable|string|max:255',
                'holder_id'      => 'nullable|string|max:255',
                'account_number' => 'nullable|string|max:255',
                'account_name'   => 'nullable|string|max:255',
                'bank_name'      => 'nullable|string|max:255',
                'bank_code'      => 'nullable|string|max:50',
                'routing_number' => 'nullable|string|max:100',
                'bank_address'   => 'nullable|string|max:255',
                'balance'        => 'nullable|numeric|min:0',
                'currency'       => 'nullable|string|max:10',
                'status'         => 'required|in:active,inactive',
                'bank_data'      => 'nullable|string',
            ]);
    
            $accountData->update($validated);
    
            session()->flash('message', 'Foreign Account updated successfully.');
            session()->flash('type', 'success');
            session()->flash('title', 'Success');
    
            return redirect()->back();
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = implode(', ', $e->validator->errors()->all());
    
            session()->flash('message', 'Validation failed: ' . $errors);
            session()->flash('type', 'error');
            session()->flash('title', 'Validation Error');
    
            return redirect()->back()->withInput();
        } catch (\Exception $e) {
            session()->flash('message', 'Something went wrong while updating the account: ' . $e->getMessage());
            session()->flash('type', 'error');
            session()->flash('title', 'Error');
    
            return redirect()->back()->withInput();
        }
    }
    
    public function destroy($id)
    {
        $accountData = ForeignAccount::findOrFail($id);
        $accountData->delete();
        
        session()->flash('message', 'Foreign account deleted successfully.');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return redirect()->back();
    }
}
