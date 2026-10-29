<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\TraitsFolder\MailTrait;
use App\Models\VirtualCardExchangeRate;

class VirtualCardExchangeRateController extends Controller
{
    use MailTrait;
    
    public function index()
    {
        $data["page_title"] = "Exchange Rate";
        $data["page_sub_title"] = "View All Exchange Rate";
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
        
        $rates = VirtualCardExchangeRate::latest()->paginate(20);

        return view('admin.fundingexchange.index', compact('rates') + $data);
    }
    
    public function edit($id)
    {
        $data["page_title"] = "Exchange Rate";
        $data["page_sub_title"] = "View Exchange Rate";
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
        
        $rateData = VirtualCardExchangeRate::findOrFail($id);

        return view('admin.fundingexchange.edit', compact('rateData') + $data);
    }
    
    public function update(Request $request, $id)
    {
        try {
            $rateData = VirtualCardExchangeRate::findOrFail($id);
    
            $validated = $request->validate([
                'currency'       => 'required',
                'rate'              => 'required',
                'fee'      => 'required',
                'cap'      => 'nullable|numeric|min:0',
            ]);

            $validated['cap'] = $request->input('cap', 0);
    
            $rateData->update($validated);
    
            session()->flash('message', 'Exchange Rate updated successfully.');
            session()->flash('type', 'success');
            session()->flash('title', 'Success');
    
            return redirect()->back();
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = implode(', ', $e->validator->errors()->all());
    
            session()->flash('message', 'Validation failed: ' . $errors);
            session()->flash('type', 'error');
            session()->flash('title', 'Validation Error');
    
            return redirect()->back();
        } catch (\Exception $e) {
            session()->flash('message', 'Something went wrong while updating the Exchange Rate: ' . $e->getMessage());
            session()->flash('type', 'error');
            session()->flash('title', 'Error');
    
            return redirect()->back();
        }
    }
    
    public function destroy($id)
    {
        $accountData = VirtualCardExchangeRate::findOrFail($id);
        $accountData->delete();
        
        session()->flash('message', 'Exchange Rate deleted successfully.');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return redirect()->back();
    }
}