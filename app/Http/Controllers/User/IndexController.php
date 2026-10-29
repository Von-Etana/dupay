<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BasicSetting;
use App\Models\ExtraPage;
use App\Models\Faq;
use App\Models\ServiceType;
use App\Models\SiteBank;
use App\Models\SiteTheme;
use App\Models\Telecom\Network;
use Illuminate\Http\Request;

class IndexController extends Controller
{

    public function getBasicSiteSettings(){
        $basic = BasicSetting::all();
        foreach ($basic as $settings) {
            $data["site_sittings"][$settings->column_name] =  $settings->value;
        }

        $data["bank"] = SiteBank::where("status", "1")->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);

        }


    public function index()
    {
       
        $data["services"] = ServiceType::where("visible_at_home_page", "1")->where("status", "1")->orderBy("order_id", "ASC")->get();
        $data["faq"] = Faq::where("homepage", "1")->where("status", "1")->get();
        $data["network"] = Network::where("status", "1")->get();
        $data["page_title"] = getSiteSettings("site_name");
        return view('index', $data);
    }


    public function getSiteTheme(){
        $theme = SiteTheme::whereId("1")->first();
        return json_encode(["success" => true, "data" => $theme]);
    }

    public function documentation(){
        return view("wallet.documentation.index");
    }

    public function pages($url)
    {
        $page = ExtraPage::where("url",$url)->orWhere("slug", $url)->where("status", "1")->firstOrFail();
        $data["page"] = $page;
        return view('page', $data);
    }
    
     public function privacy()
    {
        return view('privacy');
    }
}
