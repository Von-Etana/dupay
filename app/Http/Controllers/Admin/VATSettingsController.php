<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VatSetting;
use Illuminate\Support\Facades\Session;

class VATSettingsController extends Controller
{
    public function index()
    {
        $data["page_title"] = "Monthly Maintenance";
        $data["page_sub_title"] = "Maintenance debit";
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
        
        $vat = VatSetting::first();
        return view('admin.vat.index', compact('vat'), $data);
    }
    
    public function update(Request $request)
    {
        $request->validate([
            'vat_value' => 'required|numeric|min:0',
            'type' => 'required|in:percent,fixed',
        ]);
    
        try {
            $vat = VatSetting::first();
            if (!$vat) {
                $vat = new VatSetting();
            }
    
            $vat->vat_value = $request->vat_value;
            $vat->type = $request->type;
            $vat->save();
    
            // Success flash
            Session::flash('message', 'VAT settings updated successfully.');
            Session::flash('type', 'success');
            Session::flash('title', 'Success');
    
        } catch (\Exception $e) {
            // Error flash
            Session::flash('message', 'Failed to update VAT settings: '.$e->getMessage());
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
        }
    
        return redirect()->back();
    }

}
