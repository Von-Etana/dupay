<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Telecom\Airtime;
use App\Models\Telecom\AirtimeType;
use App\Models\Telecom\Bill;
use App\Models\Telecom\Cable;
use App\Models\Telecom\Data;
use App\Models\Telecom\DataType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\ForeignAccount;
use App\Models\SecurityAlert;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $currentDate = Carbon::now()->format('Y-m-d');
        $yesterdayDate = Carbon::yesterday()->format('Y-m-d');
        $data["currentDate"] = $currentDate;
        $data["yesterdayDate"] = $yesterdayDate;
        $data["page_title"] = "Dashboard";
        $data["page_sub_title"] = "Admin Dashboard";
        $data["totalUser"] = number_format(User::count());
        $data["totalTransaction"] = number_format(Transaction::count());

        // Distinct users with a SUCCESSFUL transaction today (auto-resets at midnight)
        $data["dailyTransactors"] = number_format(
            Transaction::whereDate('created_at', $currentDate)
                ->where('status', 'success')
                ->distinct('user_id')
                ->count('user_id')
        );
        $data["totalBalance"] = getPrice(User::sum('balance'));
        $data["newUser"] = User::whereDate('created_at', $currentDate)->get()->take(20);
        $data["dailyUserStats"] = number_format(User::whereDate('created_at', $currentDate)->count());
        $data["yesterdayUserStats"] = number_format(User::whereDate('created_at', $yesterdayDate)->count());
        $data["visitorBrowser"] = Visitor::uniqueBrowsers();
        $data["onlineUsers"] =  visitor()->onlineVisitors(User::class);
        $data["totalUSDBalance"] = User::sum('usd_balance');
        $data["totalEURBalance"] = User::sum('eur_balance');
        $data["openSecurityAlerts"] = Schema::hasTable('security_alerts')
            ? SecurityAlert::whereNull('acknowledged_at')->count()
            : 0;
        
        $data["totalUsdForeignUsers"] = number_format(
            ForeignAccount::where('currency', 'USD')->distinct('user_id')->count('user_id')
        );
        
        $data["totalEurForeignUsers"] = number_format(
            ForeignAccount::where('currency', 'EUR')->distinct('user_id')->count('user_id')
        );
        
        // 🔹 Foreign USD accounts
        $data["todayUsdForeignUsers"] = number_format(
            ForeignAccount::where('currency', 'USD')
                ->whereDate('created_at', Carbon::today())
                ->distinct('user_id')
                ->count('user_id')
        );
        
        $data["yesterdayUsdForeignUsers"] = number_format(
            ForeignAccount::where('currency', 'USD')
                ->whereDate('created_at', Carbon::yesterday())
                ->distinct('user_id')
                ->count('user_id')
        );
        
        // 🔹 Foreign EUR accounts
        $data["todayEurForeignUsers"] = number_format(
            ForeignAccount::where('currency', 'EUR')
                ->whereDate('created_at', Carbon::today())
                ->distinct('user_id')
                ->count('user_id')
        );
        
        $data["yesterdayEurForeignUsers"] = number_format(
            ForeignAccount::where('currency', 'EUR')
                ->whereDate('created_at', Carbon::yesterday())
                ->distinct('user_id')
                ->count('user_id')
        );


        return view("admin.dashboard.index", $data);
    }

    public function sales()
    {
        $currentDate = Carbon::now()->format('Y-m-d');
        $yesterdayDate = Carbon::yesterday()->format('Y-m-d');
        $data["currentDate"] = $currentDate;
        $data["yesterdayDate"] = $yesterdayDate;
        $data["page_title"] = "Sales";
        $data["page_sub_title"] = "Admin Sales";
        $data["totalUser"] = number_format(User::count());
        $data["totalTransaction"] = number_format(Transaction::count());
        $data["totalBalance"] = getPrice(User::sum('balance'));
        $data["newUser"] = User::whereDate('created_at', $currentDate)->get()->take(20);

 //add activity
 $ac['activity'] = "Visited Sales calculator page ";
 $ac['actions'] =  "visit";
 $ac['module'] = "Dashboard";
 $ac['user_type'] = "2";
 addSiteActitvity(auth()->guard('admin')->user(), $ac);
        return view("admin.dashboard.sales", $data);
    }



    public function getVisitorChart()
    {

        $type = request('type');

        if ($type == 'daily') {
            $length = 7;
            $xaxis = [
                'categories' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] // Categories for days of the week
            ];
        } else {
            $length = 12;
            $xaxis = [
                'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] // Categories for month
            ];
        }

        for ($i = 1; $i <= $length; $i++) {

            if ($type == 'daily') {
                $start = Carbon::now()->startOfWeek()->addDays($i - 1);
                $end = Carbon::now()->startOfWeek()->addDays($i);
            } else {
                $start = Carbon::create(null, $i, 1)->startOfMonth();
                $end = Carbon::create(null, $i, 1)->endOfMonth();
            }

            $directData = Visitor::whereNull('referer')->where('created_at', '>=', $start)
                ->where('created_at', '<=', $end)->distinct('ip')->count('ip');
            $organicData = Visitor::whereNotNull('referer')->where('created_at', '>=', $start)
                ->where('created_at', '<=', $end)->distinct('ip')->count('ip');

            $directDatas[] = $directData;
            $organicDatas[] =  $organicData;
        }

        // Generate the visitor stats data
        $visitorStats = [
            [
                'name' => 'Direct',
                'data' => $directDatas
            ],
            [
                'name' => 'Organic',
                'data' => $organicDatas
            ]
        ];

        $visitorData = [
            'series' =>  $visitorStats,
            'xaxis' => $xaxis
        ];
        return response(['success' => true, 'data' => $visitorData]);
    }






    public function getJsItem(Request $request)
    {
        if ($request->type == "dataPlanType") {
            $item = DataType::where("status", "1")->where("network_id", $request->network)->get();
            if (count($item) < 1) {
                return "<option value=''> Service currently unavailable </option>";
            }
            $option = "<option value=''>Please Select</option>";
            foreach ($item as $value) {
                $option .= "<option value='$value->id'>$value->name</option>";
            }
            return $option;
        } else  if ($request->type == "airtimeType") {
            $item = AirtimeType::where("status", "1")->where("network_id", $request->network)->get();
            if (count($item) < 1) {
                return "<option value=''> Service currently unavailable </option>";
            }
            $option = "<option value=''>Please Select</option>";
            foreach ($item as $value) {
                $option .= "<option value='$value->id'>$value->name</option>";
            }
            return $option;
        }
    }


    public function getOfferPlanJsItem(Request $request)
    {
        if ($request->service_type == "DATA") {
            $item = Data::where("status", "1")->get();
            if (count($item) < 1) {
                return "<option value=''> Service currently unavailable </option>";
            }
            $option = "<option value=''>Please Select</option>";
            foreach ($item as $value) {
                $option .= "<option value='$value->id'>{$value->network} {$value->type} $value->name - N$value->price</option>";
            }
            return $option;
        } else  if ($request->service_type == "AIRTIME") {
            $item = Airtime::where("status", "1")->get();
            if (count($item) < 1) {
                return "<option value=''> Service currently unavailable </option>";
            }
            $option = "<option value=''>Please Select</option>";
            foreach ($item as $value) {
                $option .= "<option value='$value->id'>{$value->network} {$value->type} $value->name - N$value->price</option>";
            }
            return $option;
        }else  if ($request->service_type == "CABLE") {
            $item = Cable::where("status", "1")->get();
            if (count($item) < 1) {
                return "<option value=''> Service currently unavailable </option>";
            }
            $option = "<option value=''>Please Select</option>";
            foreach ($item as $value) {
                $option .= "<option value='$value->id'>{$value->cableType->name} - $value->name</option>";
            }
            return $option;
        }
        else  if ($request->service_type == "BILL") {
            $item = Bill::where("status", "1")->get();
            if (count($item) < 1) {
                return "<option value=''> Service currently unavailable </option>";
            }
            $option = "<option value=''>Please Select</option>";
            foreach ($item as $value) {
                $option .= "<option value='$value->id'>$value->name</option>";
            }
            return $option;
        }
    }
}
