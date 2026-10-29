<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BasicSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Intervention\Image\Facades\Image;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class SettingsController extends Controller
{

    public function __construct(Request $request)
    {
        $this->pageType = $request->route()->parameter('pageType');
        $this->canEdit = "can:edit_".$this->pageType."_settings";
        $this->middleware($this->canEdit, ['only' => ['edit','update']]);

    }




    public function edit(){
        //$id = getSettingOption($this->pageType);
        $model = $this->pageType;
        $data["page_title"] = ucfirst($this->pageType)." Site Setting";
        $data["page_sub_title"] = ucfirst($this->pageType)." Setting";
        $data["pageType"] = $this->pageType;
        $data["column"] = BasicSetting::whereModel($model)
        ->get();
        $data["form_url"] = route('admin.settings', $this->pageType);
        $data["breadcrumb"] = [
            [
                "title" => "Home",
                "url" => route('admin.index')
            ],
            [
                "title" => $data["page_title"],
                "url" => null
            ],
        ];
         //add activity
 $ac['activity'] = "Visited ".$data["page_title"]." edit page";
 $ac['actions'] =  "visit";
 $ac['module'] = $model;
 $ac['user_type'] = "2";
 addSiteActitvity(auth()->guard('admin')->user(), $ac);
        return view("admin.form.index", $data);
    }

    public function update(Request $request){

        $id = $this->pageType;
        $basic = BasicSetting::whereModel($id)
        ->get();

        //create validation rules
        $myrule = array();
        foreach ($basic as  $value) {
            $myrule[$value->column_name] =  $value->rule;
        }

        $this->validate($request,$myrule);

        $in = request()->except('_method','_token', 'logo', 'favicon',
            'admin_login_banner', 'admin_login_image2',
            'existing_admin_login_banner', 'existing_admin_login_image2');



        if($request->hasFile('full_logo')){
            $image = $request->file('full_logo');
            $filename = 'full-logo'.'.'.$image->getClientOriginalExtension();
            $location = 'assets/images/' . $filename;
            Image::make($image)->save($location);
            $in["full_logo"] = $location;
        }

        if($request->hasFile('logo')){
            $image = $request->file('logo');
            $filename = 'logo'.'.'.$image->getClientOriginalExtension();
            $location = 'assets/images/' . $filename;
            Image::make($image)->save($location);
            $in["logo"] = $location;
        }
        if($request->hasFile('favicon')){
            $image = $request->file('favicon');
            $filename = 'favicon'.'.'.$image->getClientOriginalExtension();
            $location = 'assets/images/' . $filename;
            Image::make($image)->resize(60,60)->save($location);
            $in["favicon"] = $location;
        }

        // --- Admin login screen images (saved to assets/images/ like logo/favicon) ---
        if($request->hasFile('admin_login_banner')){
            $image = $request->file('admin_login_banner');
            $filename = 'admin-login-banner.'.$image->getClientOriginalExtension();
            $location = 'assets/images/' . $filename;
            Image::make($image)->save($location);
            BasicSetting::where('column_name', 'admin_login_banner')->update(['value' => $location]);
        }
        if($request->hasFile('admin_login_image2')){
            $image = $request->file('admin_login_image2');
            $filename = 'admin-login-image2.'.$image->getClientOriginalExtension();
            $location = 'assets/images/' . $filename;
            Image::make($image)->save($location);
            BasicSetting::where('column_name', 'admin_login_image2')->update(['value' => $location]);
        }

        foreach ($basic as  $mycolumn) {
            $co =  $basic->where("column_name", $mycolumn->column_name)->first();
            if($mycolumn->input_type == "5"){
                $col = $in[$mycolumn->column_name] ?? null;
                if($col){
                    $ans = "Yes";
                }else{
                    $ans = "No";
                }

                $co->value = $ans;
                $co->save();
            }

        }
        foreach ($in as $key => $value) {
           $co =  $basic->where("column_name", $key)->first();
           if($co->input_type == "5" ){
            //dd($co);
            $value =  $value == 'on' ? 'Yes' : 'No';
           }


           $co->update([
                "value" => $value
            ]);
        }

        if($this->pageType == "firebase"){


            foreach ($in as $key => $value) {
                $editor = DotenvEditor::setKey($key,$value);
                $editor = DotenvEditor::save();
            }
        }
        $pageType = ucfirst($this->pageType);
                 //add activity
 $ac['activity'] = "Updated $pageType";
 $ac['actions'] =  "update";
 $ac['module'] = "BasicSetting";
 $ac['user_type'] = "2";
 addSiteActitvity(auth()->guard('admin')->user(), $ac);


        session()->flash('message', "$pageType Setting Updated Successfully.");
        Session::flash('type', 'success');
        Session::flash('title', 'Success');
        return redirect()->back();
    }

    public function show(){
            session()->flash('message', "Not sure of your request.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.index");
    }



}