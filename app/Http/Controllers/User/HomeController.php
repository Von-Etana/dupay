<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Telecom\AirtimeType;
use App\Models\Telecom\Bill;
use App\Models\Telecom\BulkSms;
use App\Models\Telecom\Cable;
use App\Models\Telecom\CableType;
use App\Models\Telecom\Data;
use App\Models\Telecom\DataType;
use App\Models\Telecom\Exam;
use App\Models\Telecom\Network;
use App\Models\Notification;
use App\Models\PaymentGateway;
use App\Models\ServiceType;
use App\Models\SiteBank;
use App\Models\SupportType;
use App\Models\Transaction;
use App\Models\UserLevel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDF;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('verifyUser');
        $this->middleware('auth');
    }


    public function getJsItem(Request $request){
        //data topup type
        if($request->type == "dataPlanType"){
           $item = DataType::where("status", "1")->where("network_id", $request->network)->get();
           if(count($item) < 1){
               return "<option value=''> Service currently unavailable </option>";
           }
           $option ="<option value=''>Please Select</option>";
           foreach ($item as $value) {
            $info = [
                'id' => $value->id,
                'name' => $value->name,
                "network_image" => $value->network->image,
                "network_name" => $value->network->name
              ];

            $info = json_encode($info);

               $option .= "<option value='$info'>$value->name</option>";
           }
           return $option;

       }
       //data topup plan
       elseif($request->type == "dataPlan"){
        $item = Data::where("data_type_id", $request->dataTypeId)->where("status", "1")->get();
        if(count($item) < 1){
            return "<option value=''> Service currently unavailable </option>";
        }
        $option ="<option value=''>Please Select</option>";
        foreach ($item as $value) {
            $price = $value->calLevelPrice();
            $info = [
                'id' => $value->id,
                'name' => $value->name,
                "price" =>$price,
              ];

            $info = json_encode($info);
            $option .= "<option value='$info'>$value->name - ₦$price</option>";
        }
        return $option;

    }

       else  if($request->type == "airtimeType"){
           $item = AirtimeType::where("status", "1")->where("network_id", $request->network)->get();
           if(count($item) < 1){
               return "<option value=''> Service currently unavailable </option>";
           }
           $option ="<option value=''>Please Select</option>";
           foreach ($item as $value) {
            $info = [
                'id' => $value->id,
                'name' => $value->name,
                "network_image" => $value->network->image,
                "network_name" => $value->network->name
              ];

            $info = json_encode($info);
               $option .= "<option value='$info'>$value->name</option>";
           }
           return $option;
       }

       //cable plan
       elseif($request->type == "cablePlan"){
        $item = Cable::where("cable_type_id", $request->tv)->where("status", "1")->get();
        if(count($item) < 1){
            return "<option value=''> Service currently unavailable </option>";
        }
        $option ="<option value=''>Please Select</option>";
        foreach ($item as $value) {
            $info = [
                'id' => $value->id,
                'name' => $value->name,
                "amount" =>$value->amount,
                "tv_name" => $value->cableType->name,
              ];

            $info = json_encode($info);
            $option .= "<option value='$info'>$value->name</option>";
        }
        return $option;

    }


    }

    public function loadJsPage($page)
    {
        $data["title"] =  $page;
        if($page == "upgradeAccount"){
            $data["levels"] = UserLevel::where("status", "1")->get();
        }
        return view(enableMobile()."loads.$page", $data)->render();

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::user();
        $data["user"] = $user;
        $data["total_referral"] =  User::where("referral", $user->profile->username)->orWhere("referral", $user->profile->ref_id)->count();
        $data["levels"] = UserLevel::where("status", "1")->get();
        $data["service"] = ServiceType::where("status", "1")->where("user_side_bar", "1")->orderBy("order_id", "ASC")->get();
        $data["page_title"] = "Dashboard";
        $data["page_sub_title"] = "My Dashboard";
        $data["moving_notification"] = Notification::where("type", "1")->where("platform", "1")->where("status", "1")->get();
        $data["popup_notification"] = Notification::where("type", "2")->where("platform", "1")->where("status", "1")->get();
        return view(enableMobile().'dashboard.index', $data);
    }



    public function pricing(){
        $user = Auth::user();
        $data["levels"] = UserLevel::where("status", "1")->get();
        $data["page_title"] = "Service Pricing";
        $data["page_sub_title"] = "Service Pricing";
        return view(enableMobile()."dashboard.pricing", $data);
    }

    public function profile(){
        $user = Auth::user();
        $data["monnify"] = PaymentGateway::where("name", "monnify")->orWhere("name", "Monnify")->orWhere("name", "MONNIFY")->firstOrFail();
        $data["user"] = $user;
        $data["page_title"] = "Profile";
        $data["page_sub_title"] = "Profile";
        return view(enableMobile().".profile.index", $data);
    }

    public function profileSettings(){
        $user = Auth::user();
        $data["user"] = $user;
        $data["page_title"] = "Profile";
        $data["page_sub_title"] = "Update Profile";
        return view(enableMobile()."settings.profile", $data);
    }

    public function passwordSettings(){
        $data["page_title"] = "Password";
        $data["page_sub_title"] = "Update Password";
        return view(enableMobile()."settings.password", $data);
    }

    public function transactionPin(){
        $user = Auth::user();
        $data["user"] = $user;
        $data["page_title"] = "Transaction Pin";
        $data["page_sub_title"] = $user->transaction_pin == null ? "Create Transaction Pin":"Update Transaction Pin";
        return view(enableMobile()."settings.pin", $data);
    }


    public function showTransaction()
    {
        $user = Auth::user();
        $allData = $user->transaction()->orderBy("id", "DESC")->get()->take(10);
        $data["page_title"] = "Transaction";
        $data["page_sub_title"] = "All Transactions";
        $data["data"] = $allData;
        //return response(['success' => true, "data" => $allData]);
        return view(enableMobile()."transaction.index", $data);
    }


    public function showTransactionJs()
    {
        if (request()->input('filter') != null) {
            $user = Auth::user();
            $builder = Transaction::query()->where("user_id", $user->id);
            //$builder->where("user_id", $user->id);

            if (request()->input('search') != null) {
                $search = request()->input('search');
                $builder->where('phone_number', "like", "%$search%")->orWhere('description', "like", "%$search%")->orWhere('reference', "like", "%$search%")->orWhere('paymentReference', "like", "%$search%");
            }

            $option = request()->input('filter');

            if ($option == "all") {
            } else if ($option == "success") {
                $builder->where('status', "success");
            } else if ($option == "pending") {
                $builder->where('status', "pending");
            } else if ($option == "cancel") {
                $builder->where('status', "cancel");
            } else if ($option == "airtime") {
                $builder->where('service_type', "Airtime Topup");
            } else if ($option == "data") {
                $builder->where('service_type', "Data Topup");
            } else if ($option == "cable") {
                $builder->where('service_type', "Cable Subscription");
            } else if ($option == "bill") {
                $builder->where('service_type', "Bill Payment");
            } else if ($option == "exam") {
                $builder->where('service_type', "Result Checker");
            } else if ($option == "funding") {
                $builder->where('service_type', "Wallet Funding");
            }else if ($option == "transfer") {
                $builder->where('service_type', "Fund Transfer");
            }else if ($option == "withdrawal") {
                $builder->where('service_type', "Fund Withdrawal");
            }

            else if ($option == "debit") {
                $builder->where('type', "0");
            } else if ($option == "credit") {
                $builder->where('type', "1");
            }
        }

        $item =  $builder
            ->orderBy('id', 'DESC')
            ->paginate(20);

            if (count($item) < 1) {
                return  "<div class='alert alert-danger'>No record found
               </div>";
            }
        $data["data"] = $item;

        return view(enableMobile()."transaction.transactionListB", $data)->render();
    }

    public function transactionDetails($reference)
    {
        $user = Auth::user();
        $data["data"] = $user->transaction()->where("reference", $reference)->firstOrFail();
        $data["isPayment"] = false;
        $data["page_title"] = "Transaction";
        $data["page_sub_title"] = "Transaction Detail";
        if($data["data"]->payment != null){
            $data["isPayment"] = true;
        }
        return view(enableMobile()."transaction.details", $data);
    }


    public function downloadPDF($reference)
    {
        $user = Auth::user();
        $data["data"] = $user->transaction()->where("reference", $reference)->firstOrFail();
        $data["isPayment"] = false;
        if($data["data"]->payment != null){
            $data["isPayment"] = true;
        }


        $pdf = PDF::loadView('others.invoice', $data);
        return $pdf->download(getSiteSettings("title") . "_$reference.pdf");
        //return view('others.invoice', $data);
    }

    public function paymentLogs(){
        $user = Auth::user();
        $data['payment'] = $user->payment()->orderBy("id", "DESC")->get();
        $data["page_title"] = "Payment Logs";
        $data["page_sub_title"] = "All Payments Logs";
        return view(enableMobile().'transaction.paymentLog', $data);
    }

    public function showTransferLogs(){
        $user = Auth::user();
        $data["data"] = $user->transferFund()->orderBy("id", "DESC")->get();
        $data["page_title"] = "Transfer Log";
        $data["page_sub_title"] = "All Transfer Logs";
        return view(enableMobile()."transaction.transferLog", $data);
    }

    public function showWithdrawalLogs(){
        $user = Auth::user();
        $data["data"] = $user->withdrawalLog()->orderBy("id", "DESC")->get();
        $data["page_title"] = "Withdrawal Log";
        $data["page_sub_title"] = "All Withdrawal Logs";
        return view(enableMobile()."transaction.withdrawaLLogs", $data);
    }

    public function data(){
        $user = Auth::user();
        $data["network"] = Network::where("status", "1")->get();
        $data["page_title"] = "Data";
        $data["page_sub_title"] = "Data Topup";
        return view(enableMobile()."dashboard.data", $data);
    }


    public function airtime(){
        $user = Auth::user();
        $data["network"] = Network::where("status", "1")->get();
        $data["page_title"] = "Airtime";
        $data["page_sub_title"] = "Airtime Topup";
        return view(enableMobile()."dashboard.airtime", $data);
    }

    public function airtimeToCash(){
        $user = Auth::user();
        $data["network"] = Network::where("status", "1")->get();
        $data["page_title"] = "Airtime To Cash";
        $data["page_sub_title"] = "Convert Airtime Cash";
        return view(enableMobile()."dashboard.airtimeToCash", $data);
    }

    public function cable(){
        $user = Auth::user();
        $data["data"] = CableType::where("status", "1")->get();
        $data["page_title"] = "Cable";
        $data["page_sub_title"] = "Cable Subscription";
        return view(enableMobile()."dashboard.cable", $data);
    }


    public function bill(){
        $user = Auth::user();
        $data["data"] = Bill::where("status", "1")->get();
        $data["page_title"] = "Bill";
        $data["page_sub_title"] = "Bill Payment";
        return view(enableMobile()."dashboard.bill", $data);
    }


    public function exam(){
        $user = Auth::user();
        $data["exam"] = Exam::where("status", "1")->get();
        $data["page_title"] = "Exam";
        $data["page_sub_title"] = "Result Checker";
        return view(enableMobile()."dashboard.exam", $data);
    }


    public function examPin(){
        $user = Auth::user();
        $data["exam"] = $user->examPin;
        $data["page_title"] = "Exam Pin";
        $data["page_sub_title"] = "Result Checker Pin";
        return view(enableMobile()."dashboard.examPin", $data);
    }


    public function bulkSms(){
        $data["page_title"] = "Bulk SMS";
        $data["page_sub_title"] = "Send Bulk SMS";
        return view(enableMobile()."dashboard.bulksms", $data);
    }



    public function referral(){
        $user = Auth::user();
        $data["data"] = User::where("referral", $user->profile->username)->orWhere("referral", $user->profile->ref_id)->get();
        $data["page_title"] = "Referral";
        $data["page_sub_title"] = "My Referral";
        return view(enableMobile()."dashboard.referral", $data);
    }


    /****
     *
     *
     * support
     *
     */
    public function allSupport(){
        $user = Auth::user();
        $data["data"] = $user->support()->latest()->paginate(10);
        $data["page_title"] = "Support";
        $data["page_sub_title"] = "Support Ticket";
        return view(enableMobile()."support.index", $data);
    }

    public function viewSupport($ticketNumber){
        $user = Auth::user();
        $data["data"] = $user->support()->where("ticket_number", $ticketNumber)->firstOrFail();
        $data["page_title"] = "Support";
        $data["page_sub_title"] = $data["data"]->subject;
        return view(enableMobile()."support.details", $data);
    }

     public function createSupport(){
        $data["data"] = SupportType::where("status", "1")->get();
        $data["page_title"] = "Support";
        $data["page_sub_title"] = "Open Support Ticket";
        return view(enableMobile()."support.create", $data);
    }




    /*****
     *
     *
     * Payment [wallet fundding]
     *
     *
     */


    public function atmPayment($id = 1){
        $data["user"] = Auth::user();
        $data["data"] = PaymentGateway::findOrFail($id);
        if($data["data"]->status == "0"){
            session()->flash('title','Funding Deactivated');
            session()->flash('message','Sorry Funding through this medium has been deactivated.!');
            session()->flash('type','danger');
            return redirect()->back();
        }
        $data["page_title"] = "Wallet";
        $data["page_sub_title"] = "Wallet Funding";
        if($id == 1){
            return view(enableMobile()."payment.atm", $data);
        }else{
            return view(enableMobile()."payment.atm_flw", $data);
        }

    }

    public function virtualAccountPayment(){
        $data["user"] = Auth::user();
        $data["monnify"] = PaymentGateway::where("name", "monnify")->orWhere("name", "Monnify")->orWhere("name", "MONNIFY")->firstOrFail();
        if($data["monnify"]->status == "0"){
            session()->flash('title','Funding Deactivated');
            session()->flash('message','Sorry Funding through this medium has been deactivated.!');
            session()->flash('type','danger');
            return redirect()->back();
        }
        $data["page_title"] = "Wallet";
        $data["page_sub_title"] = "Virtual Account Funding";
        return view(enableMobile()."payment.virtualAccount", $data);
    }

    public function BankPayment(){
        $data["user"] = Auth::user();
        $data["bank"] = SiteBank::where("status", "1")->get();
        if(!$data["bank"]){
            session()->flash('title','Funding Deactivated');
            session()->flash('message','Sorry Funding through this medium has been deactivated.!');
            session()->flash('type','danger');
            return redirect()->back();
        }
        $data["page_title"] = "Wallet";
        $data["page_sub_title"] = "Bank Deposit/Transer";
        return view(enableMobile()."payment.bankDeposit", $data);
    }

}

