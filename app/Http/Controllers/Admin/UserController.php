<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\TraitsFolder\MailTrait;
use FirebaseUserUpdate;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ForeignAccount;
use App\Models\Fintech\ForeignAccountTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\SecurityAlertService;

class UserController extends Controller
{
    use MailTrait;

    public function __construct()
    {

        $this->middleware('can:view_user',     ['only' => ['index','ajax']]);
        $this->middleware('can:create_user',   ['only' => ['create', 'store']]);
        $this->middleware('can:edit_user',     ['only' => ['edit', 'update']]);
        $this->middleware('can:delete_user',   ['only' => ['destroy', 'bulk_delete']]);
        $this->middleware('can:view_user_details',   ['only' => ['show']]);
        $this->middleware('can:reset_user_password',   ['only' => ['resetPassword']]);
        $this->middleware('can:reset_user_pin',   ['only' => ['resetTransactionPin']]);
        $this->middleware('can:ban_user',   ['only' => ['banUnbanUser']]);
        $this->middleware('can:activate_user',   ['only' => ['activateDeactivateUser']]);
        $this->middleware('can:verify_user',   ['only' => ['verifyUser']]);
        $this->middleware('can:edit_user_wallet',   ['only' => ['updateUserWallet', 'topUpBalance']]);
        $this->middleware('can:upgrade_user_level',   ['only' => ['updateUserLevel']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data["page_title"] = "User";
        $data["page_sub_title"] = "All Users";
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
        $model_name = app("App\Models\\User");
        $data["ajaxUrl"] = route('admin.getUser');
        $data["createUrl"] = route('admin.user.create');
        $data["canCreate"] = Gate::allows('create_user');
        $data["bulkDeleteUrl"] = route('admin.BulkDeleteItem', "User");
        $data["pageType"] = "user";
        $data["column"] = $model_name->column();

        if (method_exists($model_name, 'tableFilter')) {
            $data["filterTable"] =  $model_name->tableFilter();
        }


         //add activity
         $ac['activity'] = "Visited User -- All Users page";
         $ac['actions'] =  "visit";
         $ac['module'] = "User";
         $ac['user_type'] = "2";
         addSiteActitvity(auth()->guard('admin')->user(), $ac);

        return view('admin.dashboard.item.index', $data);
    }

     /**
     * get users datatable
     *
     * @access public
     * @var  @Request $request
     */
    public function ajax(Request $request)
    {
        $model = User::query()->orderBy("id","desc");
        $model_name = app("App\Models\\User");
         //filter
         $filter = request()->input('filter');
         if (method_exists($model_name, 'filterMethod') && $filter!= "") {
             $model =   $model_name->filterMethod($model, $filter);
         }

        //ismart search
        $ismartSearch = request()->input('ismartSearch');
        if(method_exists($model_name, 'searchMethod') && $ismartSearch!=""){
            $model =   $model_name->searchMethod($model,$ismartSearch);
        }

        $pageType = "user";
        $dataTble = DataTables::eloquent($model);
        $columns = $model_name->column();
        foreach ($columns as $column) {
            $dataTble
            ->editColumn($column->column_name, function($item) use($column,$pageType) {
                if($column->column_name == "amount" && $item["amount"] != null){
                    $item["amount"] = getPrice($item["amount"]);
                }else if($column->column_name == "charge" && $item["charge"] != null){
                    $item["charge"] = getPrice($item["charge"]);
                }
                else if($column->column_name == "balance" && $item["balance"] != null){
                    $item["balance"] = getPrice($item["balance"]);
                }
                return view('admin.form._columnView', compact('item', 'column', 'pageType'));
            });
        }
        $dataTble->addColumn('action', function ($item) {
                return view('admin.user._action', compact('item'));
        });
        $dataTble->addColumn('bulk_checkbox', function ($item) {
                return view('admin.layouts._bulk_checkbox', compact('item'));
        });

        return $dataTble->toJson();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data["page_title"] = "Users";
        $data["page_sub_title"] = "Add New User";
        $data["form_url"] = route('admin.user.store');
        $data["breadcrumb"] = [
            [
                "title" => "Home",
                "url" => route('admin.index')
            ],
            [
                "title" => "Users",
                "url" => route('admin.user.index')
            ],
            [
                "title" => "Add New User",
                "url" => null
            ]
        ];
        $model_name = app("App\Models\\User");
        $data["column"] = $model_name->column();

            //add activity
            $ac['activity'] = "Visited Create User  page";
            $ac['actions'] =  "visit";
            $ac['module'] = "User";
            $ac['user_type'] = "2";
            addSiteActitvity(auth()->guard('admin')->user(), $ac);
        return view('admin.form.index', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $model_name = app("App\Models\\User");
        $column = $model_name->column();
        $this->validate($request, $model_name->rules());
        $data = $request->except("_token", '_method');

        if (getSiteSettings("email_verify") == "Yes"){
            $email_verify = "0";
        }else{
            $email_verify = "1";
        }

        if (getSiteSettings("phone_verify") == "Yes"){
            $phone_verify = "0";
        }else{
            $phone_verify = "1";
        }

        $email_code = strtolower(Str::random(6));
        $email_time = Carbon::parse()->addMinutes(5);
        $phone_code = random_int(1000, 9999);

        if (getSiteSettings("auto_international_number") == "Yes"){
            if(substr($data["phone_number"], 0, 1) == "0"){
                $data["phone_number"] = preg_replace('/0/', '+234', $data["phone_number"], 1);
            }
        }

        $user = User::create(
            [
            'balance' => getSiteSettings("default_wallet_amount"),
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'user_level_id' => 1,
            'email_verify' => $email_verify,
            'email_code' => $email_code,
            'email_time' => $email_time,
            'phone_verify' => $phone_verify,
            'phone_code' => $phone_code,
            'phone_time' => $email_time,
            'referral' => $request->referral,
            ]
        );

        if($user){
            $user->profile()->create([
                "username" => $data['username'],
                "first_name" => $request->first_name,
                "last_name" => $request->last_name,
                "ref_id" => strtolower(Str::random(5)),
                ]);
        }

            //add activity
            $ac['activity'] = "Created New User";
            $ac['actions'] =  "create";
            $ac['module'] = "User";
            $ac['user_type'] = "2";
            $ac["extra"] = json_encode(["id" =>  $user->id, "request" => $request]);
            addSiteActitvity(auth()->guard('admin')->user(), $ac);


        session()->flash('message', "User Added Successfully");
        Session::flash('type', 'success');
        Session::flash('title', 'Success');
        return redirect()->route('admin.user.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data["page_title"] = "User";
        $data["page_sub_title"] = "User Details";
        $data["levels"] = UserLevel::where("status", "1")->get();
        $data["data"] = User::where("id",$id)->orWhere('email',$id)->orWhere('phone_number',$id)->orWhereHas('profile', function (Builder $query) use ($id) {
            $query->where('username', $id)->orWhere("ref_id", $id);
        })->firstOrFail();
        $model_name = app("App\Models\\Transaction");
        $data["TransactionColumn"] = $model_name->column();
        $data["transactionAjaxUrl"] = route('admin.showTransactionJs', $data["data"]->profile->username);
        $data["deleteTitle"] = "Transaction";
        $data["referrals"] = User::where('referral', $data["data"]->profile->ref_id)->get();

        $data["breadcrumb"] = [
            [
                "title" => "Home",
                "url" => route('admin.index')
            ],
            [
                "title" => "Users",
                "url" => route('admin.user.index')
            ],
            [
                "title" => "View Details",
                "url" => null
            ]
        ];

         //add activity
         //$usename = $data['data']->profile->username ?? '';
         $ac['activity'] = "Visited $id User Details  page";
         $ac['actions'] =  "visit";
         $ac['module'] = "User";
         $ac['user_type'] = "2";
         $ac["extra"] = json_encode(["id" =>  $id]);
         addSiteActitvity(auth()->guard('admin')->user(), $ac);

        return view('admin.user.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data["page_title"] = "Users";
        $data["page_sub_title"] = "Edit User";
        $data["form_url"] = route('admin.user.update', $id);
        $data["breadcrumb"] = [
            [
                "title" => "Home",
                "url" => route('admin.index')
            ],
            [
                "title" => "Users",
                "url" => route('admin.user.index')
            ],
            [
                "title" => "Edit User",
                "url" => null
            ]
        ];
    
        $data["data"] = User::where("id", $id)
            ->with('profile')
            ->firstOrFail();
    
        $model_name = app("App\Models\\User");
        $data["column"] = $model_name->column();
    
        // add activity
        $ac['activity'] = "Visited Edit User page for User ID: {$id}";
        $ac['actions'] = "visit";
        $ac['module'] = "User";
        $ac['user_type'] = "2";
        $ac["extra"] = json_encode(["id" => $id]);
        addSiteActitvity(auth()->guard('admin')->user(), $ac);
    
        return view('admin.user.edit', $data);
    }

    
    public function update(Request $request, $id)
    {
        $user = User::with('profile')->findOrFail($id);
    
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => 'required|string|max:20|unique:users,phone_number,' . $user->id,
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'username' => 'required|string|max:100|unique:profiles,username,' . $user->profile->id,
        ]);
    
        try {
            $user->update([
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
            ]);
    
            $user->profile()->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'username' => $validated['username'],
            ]);
            
            session()->flash('message', 'User information updated successfully.');
            Session::flash('type', 'success');
            Session::flash('title', 'Success');
    
            return redirect()->back();
    
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'type' => 'error',
                'title' => 'Error',
                'message' => 'Update failed: ' . $e->getMessage(),
            ]);
        }
    }




    public function jsUpdate(Request $request, $username){
        $field = $request->field;
        $rawVal = $request->$field;
        if (is_array($rawVal)) { $rawVal = end($rawVal); }
        $action = in_array((string) $rawVal, ['on', '1', 'true', 'Verified', 'Activated', 'On'], true) ? '1' : '0';
    
        $user = User::whereHas('profile', function (Builder $query) use ($username) {
            $query->where('username', $username)->orWhere("ref_id", $username);
        })->first();
    
        if(!$user){
            return response(['success' => false, 'message' => "User not Found"]);
        }
    
        try {
            $userFields = ['email_verify', 'phone_verify', 'account_activation'];
            $profileFields = ['verified', 'receive_sms'];
    
            if (in_array($field, $userFields)) {
                // Update users table using primary key
                $result = \DB::table('users')
                    ->where('id', $user->id) // Primary key of users table
                    ->update([$field => $action]);
    
    
            } elseif (in_array($field, $profileFields)) {
                // Update profiles table using primary key
                $profileId = $user->profile->id;
                $result = \DB::table('profiles')
                    ->where('id', $profileId) // Primary key of profiles table
                    ->update([$field => $action]);
    
    
            } else {
                return response(['success' => false, 'message' => "Unknown field: $field"]);
            }
    
            // Immediate verification
            if (in_array($field, $userFields)) {
                $newValue = \DB::table('users')->where('id', $user->id)->value($field);
            } else {
                $newValue = \DB::table('profiles')->where('id', $user->profile->id)->value($field);
            }
    
        } catch (\Exception $e) {
            return response(['success' => false, 'message' => "Update failed"]);
        }
    
        // Activity log
        $ac['activity'] = "Updated User - ".$username." Details page";
        $ac['actions'] = "update";
        $ac['module'] = "User";
        $ac['user_type'] = "2";
        $ac["extra"] = json_encode(["id" => $user->id, "request" => $request]);
        addSiteActitvity(auth()->guard('admin')->user(), $ac);
    
        return response(['success' => true, 'message' => "Updated Successfully"]);
    }


    public function updateUserWallet(Request $request, $username)
    {
        $gateWayData = PaymentGateway::find(1);

        if(!$gateWayData || $gateWayData->status != "1"){
            session()->flash('message', "Manual Funding is disabled");
            Session::flash('type', 'danger');
            Session::flash('title', 'Fail');
            return redirect()->back();
        }


        $this->validate($request, [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000000', 'regex:/^\d+(\.\d{1,2})?$/'],
            'action' => ['nullable', 'in:on'],
            'reason' => ['nullable', 'string', 'max:255'],
            'wallet_id' => ['required', 'in:1,2,3'],
            'alert' => '',
        ]);
        $action = $request->action == 'on' ? 'Credit' : 'Debit';
        $alert = $request->alert == 'on' ? true : false;
        $removeCharge = $request->remove_charge == 'on' ? true : false;
        $user = User::whereHas('profile', function (Builder $query) use ($username) {
            $query->where('username', $username)->orWhere("ref_id", $username);
        })->firstOrFail();

        try {
        DB::beginTransaction();

        if ($gateWayData->rate == "FLAT") {
            $charge = $gateWayData->charge/100 * $request->amount;
        }else{
            $charge = $gateWayData->charge;
        }

        if ($action == "Credit") {
            $walletActionRes =  userBalanceAction($user, $request->amount, $request->wallet_id);
            $init_balance = $walletActionRes["init_balance"];
            $final_balance = $walletActionRes["final_balance"];
            $desc = getPrice($request->amount) . " Naira Was Credited into your account $request->reason";
            $mydesc = "{$user->profile->username} account was credited with ".getPrice($request->amount)."  initial balance ".getPrice($init_balance)." new balance is ".getPrice($final_balance);
            $type = "1";
            $activity = "Credited ".getPrice($request->amount)." to {$user->profile->username} account initial balance ".getPrice($init_balance)." new balance is ".getPrice($final_balance);

        } else {
            $walletActionRes =  userBalanceAction($user, $request->amount, $request->wallet_id, "-");
            $init_balance = $walletActionRes["init_balance"];
            $final_balance = $walletActionRes["final_balance"];
            $desc = getPrice($request->amount) . " Naira Was Debited from your account $request->reason";
            $mydesc = getPrice($request->amount)." was debited from {$user->profile->username} account initial balance ".getPrice($init_balance)." new balance is ".getPrice($final_balance);
            $activity = "Debited ".getPrice($request->amount)." from {$user->profile->username} account initial balance ".getPrice($init_balance)." new balance is ".getPrice($final_balance);

            $type = "0";
            $charge = 0;
        }

        if (empty($walletActionRes['success'])) {
            throw new \RuntimeException('Wallet adjustment was rejected');
        }

        // A balance change must always have a transaction record, even without an alert.
            $trx = strtoupper(Str::random(16));
            $ul['wallet_id'] = $request->wallet_id;
            $ul['user_level_id'] = $user->user_level_id;
            $ul['service'] = "System";
            $ul['service_type'] = "Wallet Funding";
            $ul['description'] = $desc;
            $ul['amount'] = $request->amount;
            $ul['charge'] = $charge;
            $ul['phone_number'] = $user->phone_number;
            $ul['type'] = $type;
            $ul['initial_balance'] = $init_balance;
            $ul['final_balance'] = $final_balance;
            $ul['reference'] = $trx;
            $ul['status'] = "success"; //successful
            if ($action == "Credit") {$ul['paymentReference'] = $trx;}
            $transaction = $user->transaction()->create($ul);

            if ($action == "Credit") {
                $pT["payment_gateway_id"] = 1;
                $pT["type"] = "paydem";
                $pT["reference"] = $trx;
                $pT["amount"] = $request->amount;
                $pT["charge"] = $charge;
                $pT["wallet_id"] = $request->wallet_id;
                $pT["status"] = "success"; //successful
                $user->payment()->create($pT);
            }

            //remove charge
            if($charge > 0 && $removeCharge == true){
            subChargeFromWallet($user, $charge, $request->wallet_id, $desc, $alert);
            }

            $ac = ['activity' => $activity, 'actions' => 'update', 'module' => 'User',
                'user_type' => '2', 'extra' => json_encode(['id' => $user->id, 'reference' => $trx])];
            addSiteActitvity(auth()->guard('admin')->user(), $ac);
            DB::commit();

            $targetUsername = $user->profile?->username ?? $user->username ?? ("user#" . $user->id);
            $targetFullName = trim(($user->profile?->first_name ?? '') . ' ' . ($user->profile?->last_name ?? '')) ?: ($user->name ?? $targetUsername);
            $targetEmail = $user->email ?? null;
            $adminUser = auth()->guard('admin')->user();
            $adminUsername = $adminUser?->username ?? $adminUser?->name ?? ($adminUser?->id ? 'Admin #' . $adminUser->id : 'Admin');

            SecurityAlertService::record(
                'admin_wallet_adjustment',
                'high',
                'Admin wallet adjustment',
                "An admin {$action}ed NGN wallet for @{$targetUsername} ({$targetFullName}).",
                auth()->guard('admin')->id(),
                request()->ip(),
                [
                    'user_id' => $user->id,
                    'username' => $targetUsername,
                    'user_name' => $targetFullName,
                    'user_email' => $targetEmail,
                    'admin_username' => $adminUsername,
                    'wallet_id' => $request->wallet_id,
                    'amount' => (float) $request->amount,
                    'currency' => 'NGN',
                    'action' => ucfirst($action),
                    'reference' => $trx,
                ]
            );
            SecurityAlertService::sendTransactionNotice([
                'action' => ucfirst($action),
                'currency' => 'NGN',
                'amount' => $request->amount,
                'user_id' => $user->id,
                'username' => $targetUsername,
                'user_name' => $targetFullName,
                'user_email' => $targetEmail,
                'admin_username' => $adminUsername,
                'reference' => $trx,
                'time' => now(),
            ]);

            if ($alert) {
              try {

                (new FirebaseUserUpdate)->updateUser($user);

                (new FirebaseUserUpdate)->addUserNotification($user,
                [
                    "message" => $desc,
                    "type" => "transaction",
                    "read" => 0,
                    "data" => [
                        "service" => 'Wallet Funding',
                        "id" => $transaction->id,
                        "reference_id" => $trx,
                        "status" => "success",
                        'date' => Carbon::now()
                    ]
                ]
                );



                if (getSiteSettings("email_notify") == "Yes" &&
                    filter_var($user->email, FILTER_VALIDATE_EMAIL) &&
                    filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)) {
                    // $text = $desc . " initial balance ".getPrice($init_balance)." new balance is ".getPrice($final_balance)."  Reference ID Is : $trx";
                    $vir = $user->virtualAccount->account_number ?? '';
                    $maskedAccount = substr($vir, 0, 1) . "XX..XX" . substr($vir, -2);
                    $formattedDate = Carbon::now()->format('d-M-Y h:i A');
                    $transactionType = ($action == "Credit") ? "CREDIT ALERT" : "DEBIT ALERT";
                    $subjectTransactionType = ($action == "Credit") ? "Credit" : "Debit";
                    $narrationTransactionType = ($action == "Credit") ? "credited" : "debited";
                    $narration = $request->reason ?? "Your account has been " . $narrationTransactionType . " by dupay";
                    
                    $text = "
                    <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong> with details below:</p>
                    <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Account Name</td>
                            <td style='padding:8px; border:1px solid #ddd;'><strong>{$user->profile->first_name} {$user->profile->last_name}</strong></td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$transactionType}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Amount</td>
                            <td style='padding:8px; border:1px solid #ddd;'>₦" . number_format($request->amount, 2) . "</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Currency</td>
                            <td style='padding:8px; border:1px solid #ddd;'>NGN</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Account Number</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$maskedAccount}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Narration</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$narration}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Remarks</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$trx}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Date and Time</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Account Balance</td>
                            <td style='padding:8px; border:1px solid #ddd;'>₦" . number_format($final_balance, 2) . "</td>
                        </tr>
                    </table>
                    <br>
                    <p>Thank you for using <strong>Dupay</strong>.</p>
                    ";
                    $this->sendMail($user->email, $user->profile->first_name, 'Dupay ' . $subjectTransactionType . ' Transaction Notification', $text);
                }
                if (getSiteSettings("phone_notify") == "Yes") {
                    $text = $desc . "  Reference ID Is : #$trx";
                    $this->sendSms($user->phone_number, $text);
                }
            } catch (\Throwable $th) {
                \Log::warning('Admin wallet notification failed', ['transaction_id' => $transaction->id]);
            }
            }

        session()->flash('message', "$mydesc");
        Session::flash('type', 'success');
        Session::flash('title', 'Success');
        return redirect()->back();
        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) DB::rollBack();
            \Log::error('Admin wallet adjustment failed', ['user_id' => $user->id, 'exception' => get_class($th)]);
            return redirect()->back()->with('message', 'Wallet adjustment failed. Check transaction history before retrying.');
        }
    }


    public function updateUserLevel(Request $request, $username)
    {

        $this->validate($request, [
            'package_id' => ['required', 'integer'],
            'action' => '',
            'alert' => '',
        ]);
        $user = Profile::Where("username", $username)->orWhere("ref_id", $username)->firstOrFail();
        $user = $user->user;
        if ($user->user_level_id == $request->package_id) {
            session()->flash('message', "User is currently on this Package");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->back();
        }

        $level = UserLevel::where("id", $request->package_id)->first();

        if (!$level) {
            session()->flash('message', "Level not found");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->back();
        }

        if ($level->status == "0") {
            session()->flash('message', "Level is currently not available");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->back();
        }

        $description = "Account Upgrade To $level->name";

        //if to remove the fund from user wallet
        if ($request->action != null && $request->action == 'on') {
            //check user Wallet
            $checkWallet =  checkUserWallet($user, $level->amount);
            if (!$checkWallet) {
                session()->flash('message', "User does not have sufficient balance");
                Session::flash('type', 'error');
                Session::flash('title', 'Error');
                return redirect()->back();
            }
            $amount = $level->amount;
            $walletActionRes =  userBalanceAction($user, $amount, "1", "-");
            $success = $walletActionRes["success"];
            $init_balance = $walletActionRes["init_balance"];
            $final_balance = $walletActionRes["final_balance"];

            if (!$success) {
                session()->flash('message', "Unable to deduct amount in user wallet");
                Session::flash('type', 'error');
                Session::flash('title', 'Error');
                return redirect()->back();
            }
        } else {
            $amount = 0;
            $init_balance = $user->balance;
            $final_balance = $user->balance;
        }

        if ($request->alert != null && $request->alert == 'on') {
            $Trx = strtoupper(Str::random(16));
            $ul['wallet_id'] = "1";
            $ul['user_level_id'] = $user->user_level_id;
            $ul['service'] = "Upgrade";
            $ul['service_type'] = "Account Upgarde";
            $ul['description'] = $description;
            $ul['amount'] =  $amount;
            $ul['phone_number'] = $user->phone_number;
            $ul['type'] = "0";
            $ul['initial_balance'] = $init_balance;
            $ul['final_balance'] = $final_balance;
            $ul['reference'] = $Trx;
            $ul['status'] = "success"; //"success";
            $transaction =  $user->transaction()->create($ul);

            if ($transaction) {

                (new FirebaseUserUpdate)->updateUser($user);

                (new FirebaseUserUpdate)->addUserNotification($user,
                [
                    "message" => $description,
                    "type" => "transaction",
                    "read" => 0,
                    "data" => [
                        "service" => 'Account Upgrade',
                        "id" => $transaction->id,
                        "reference_id" => $Trx,
                        "status" => "success",
                        'date' => Carbon::now()
                    ]
                ]
                );



                try {
                    if (getSiteSettings("email_notify") == "Yes") {
                        $text = $description . "  Reference ID Is : #$Trx";
                        $this->sendMail($user->email, $user->profile->first_name, 'Account Upgarde', $text);
                    }
                    if (getSiteSettings("phone_notify") == "Yes") {
                        $text = $description . "  Reference ID Is : #$Trx";
                        $this->sendSms($user->phone_number, $text);
                    }
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }
        }

        $user->user_level_id = $level->id;
        $user->save();


         //add activity
         $ac['activity'] = "Upgraded {$user->username} Account To $level->name";
         $ac['actions'] =  "update";
         $ac['module'] = "User";
         $ac['user_type'] = "2";
         $ac["extra"] = json_encode(["id" => $user->id, "request" => $request]);
         addSiteActitvity(auth()->guard('admin')->user(), $ac);

        session()->flash('message', "Action Successful.");
        Session::flash('type', 'success');
        Session::flash('title', 'Success');
        return redirect()->back();
    }

    public function resetTransactionPin($username)
    {
        $item = Profile::Where("username", $username)->orWhere("ref_id", $username)->first();
        if (!$item) {
            session()->flash('message', "User does not exists.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.user.index");
        }
        $item = $item->user;
        // $newPin = rand(1111, 9999);
        $newPin = (string) rand(1111, 9999);
        $item->transaction_pin = Hash::make($newPin);
        $item->save();
        
        if (getSiteSettings("email_notify") == "Yes"){
            try {
                $message = "
                    <p>This is to let you know that your account transaction pin has been reset by the system administrator.</p>
                    <p style='margin:20px 0; font-size:16px;'>
                      <strong>Your new transaction pin is:</strong><br>
                      <span style='display:inline-block; margin-top:8px; padding:10px 15px; background:#f0f0f0; border-radius:4px; font-weight:bold; color:#d9534f;'>
                        {$newPin}
                      </span>
                    </p>
                    <p>For security reasons, please log in as soon as possible and change this transaction pin to one of your choice.</p>
                ";
                
                $this->sendMail(
                        $item->email,
                        $item->profile->first_name,
                        "Your New Transaction Pin",
                        $message
                    );
            } catch (\Throwable $th) {
                //throw $th;
            }
        }

         //add activity
         $ac['activity'] = "Reset {$username} Transaction Pin";
         $ac['actions'] =  "update";
         $ac['module'] = "User";
         $ac['user_type'] = "2";
         $ac["extra"] = json_encode(["id" => $item->id]);
         addSiteActitvity(auth()->guard('admin')->user(), $ac);

        session()->flash('message', "User Transaction Pin Reset Successfully.");
        Session::flash('type', 'success');
        Session::flash('title', 'Success');
        return back();
    }

    public function resetPassword($username)
    {
        $item = Profile::Where("username", $username)->orWhere("ref_id", $username)->first();
        if (!$item) {
            session()->flash('message', "User does not exists.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.user.index");
        }

        $newPassword = strtoupper(Str::random(8));
        $item = $item->user;
        $item->password = Hash::make($newPassword);
        $item->save();



        if (getSiteSettings("email_notify") == "Yes"){
            try {
                $message = "
                    <p>This is to let you know that your account password has been reset by the system administrator.</p>
                    <p style='margin:20px 0; font-size:16px;'>
                      <strong>Your new password is:</strong><br>
                      <span style='display:inline-block; margin-top:8px; padding:10px 15px; background:#f0f0f0; border-radius:4px; font-weight:bold; color:#d9534f;'>
                        {$newPassword}
                      </span>
                    </p>
                    <p>For security reasons, please log in as soon as possible and change this password to one of your choice.</p>
                ";
                
                // $this->sendPasswordReset($item->email,$item->profile->username,'Your New Password',$newPassword);
                $this->sendMail(
                        $item->email,
                        $item->profile->first_name,
                        "Your New Password",
                        $message
                    );
            } catch (\Throwable $th) {
                //throw $th;
            }
        }

          //add activity
          $ac['activity'] = "Reset {$username} Password";
          $ac['actions'] =  "update";
          $ac['module'] = "User";
          $ac['user_type'] = "2";
          $ac["extra"] = json_encode(["id" => $item->id]);
          addSiteActitvity(auth()->guard('admin')->user(), $ac);

        session()->flash('message', "User Password Reset Successfully.");
        Session::flash('type', 'success');
        Session::flash('title', 'Success');
        return back();
    }


    public function changePassword($username)
    {
        $item = Profile::Where("username", $username)->orWhere("ref_id", $username)->first();
        if (!$item) {
            session()->flash('message', "User does not exists.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.user.index");
        }
        $newPassword = request('password');
        //$newPassword = strtoupper(Str::random(8));
        $item = $item->user;
        $item->password = Hash::make($newPassword);
        $item->save();



        if (getSiteSettings("email_notify") == "Yes"){
            try {
                $this->sendPasswordReset($item->email,$item->profile->username,'Your New Password',$newPassword);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }

          //add activity
          $ac['activity'] = "Update {$username} Password";
          $ac['actions'] =  "update";
          $ac['module'] = "User";
          $ac['user_type'] = "2";
          $ac["extra"] = json_encode(["id" => $item->id]);
          addSiteActitvity(auth()->guard('admin')->user(), $ac);

        session()->flash('message', "User Password changed Successfully.");
        Session::flash('type', 'success');
        Session::flash('title', 'Success');
        return back();
    }


    public function banUnbanUser($username)
    {
        $item = Profile::Where("username", $username)->orWhere("ref_id", $username)->first();
        if (!$item) {
            session()->flash('message', "User does not exists.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.user.index");
        }
        $item = $item->user;
        if ($item->ban_status == "1") {
            $msg = "Unban";
            $item->ban_status = "0";
        } else {
            $msg = "Ban";
            $item->ban_status = "1";
        }
        $item->save();

         //add activity
         $ac['activity'] = "$msg {$username}";
         $ac['actions'] =  "update";
         $ac['module'] = "User";
         $ac['user_type'] = "2";
         $ac["extra"] = json_encode(["id" => $item->id]);
         addSiteActitvity(auth()->guard('admin')->user(), $ac);

        session()->flash('message', "User $msg Successfully.");
        Session::flash('type', 'success');
        Session::flash('title', 'Success');
        return back();
    }
    
    public function blockUnblockUser($username)
    {
        $item = Profile::Where("username", $username)->orWhere("ref_id", $username)->first();
        if (!$item) {
            session()->flash('message', "User does not exists.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.user.index");
        }
        $item = $item->user;
        if ($item->blocked_status == "1") {
            $msg = "Unblocked";
            $item->blocked_status = "0";
        } else {
            $msg = "Blocked";
            $item->blocked_status = "1";
        }
        $item->save();

         //add activity
         $ac['activity'] = "$msg {$username}";
         $ac['actions'] =  "update";
         $ac['module'] = "User";
         $ac['user_type'] = "2";
         $ac["extra"] = json_encode(["id" => $item->id]);
         addSiteActitvity(auth()->guard('admin')->user(), $ac);

        session()->flash('message', "User $msg Successfully.");
        Session::flash('type', 'success');
        Session::flash('title', 'Success');
        return back();
    }

    public function activateDeactivateUser($username)
    {
        $item = Profile::Where("username", $username)->orWhere("ref_id", $username)->first();
        if (!$item) {
            session()->flash('message', "User does not exists.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.user.index");
        }
        $item = $item->user;
        if ($item->account_activation == "1") {
            $msg = "Account Deactivated";
            $item->account_activation = "0";
            $activity = "Deactivated $username Account";
        } else {
            $msg = "Account Activated";
            $activity = "Activated $username Account";
            $item->account_activation = "1";
        }
        $item->save();

         //add activity
         $ac['activity'] = $activity;
         $ac['actions'] =  "update";
         $ac['module'] = "User";
         $ac['user_type'] = "2";
         $ac["extra"] = json_encode(["id" => $item->id]);
         addSiteActitvity(auth()->guard('admin')->user(), $ac);

        session()->flash('message', "User $msg Successfully.");
        Session::flash('type', 'success');
        Session::flash('title', 'Success');
        return back();
    }


    public function destroy($id)
    {
        $user = User::findorFail($id);
        $username = $user->profile->username;
        $user->delete();

         //add activity
         $ac['activity'] = "Deleted $username";
         $ac['actions'] =  "delete";
         $ac['module'] = "User";
         $ac['user_type'] = "2";
         $ac["extra"] = json_encode(["id" => $id]);
         addSiteActitvity(auth()->guard('admin')->user(), $ac);

        session()->flash('message', "User Deleted Successfully");
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return redirect()->route('admin.user.index');
    }
    
    public function topUpBalance(Request $request, $id, $currency)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000000', 'regex:/^\d+(\.\d{1,2})?$/'],
            'reason' => 'nullable|string|max:255',
        ]);
    
        $currency = strtoupper($currency);
    
        if (!in_array($currency, ['USD', 'EUR'])) {
            session()->flash('message', 'Invalid currency specified.');
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return back();
        }
    
        $isCredit = $request->has('action');
        $alert = $request->has('alert');
    
        $amount = $request->amount;
        $reason = $request->reason ?: ($isCredit ? "Admin {$currency} top-up" : "Admin {$currency} debit");
    
        $user = User::with(['profile', 'usdForeignAccount', 'eurForeignAccount'])->findOrFail($id);
    
        try {
            DB::beginTransaction();
            $user = User::with(['profile', 'usdForeignAccount', 'eurForeignAccount'])
                ->whereKey($id)->lockForUpdate()->firstOrFail();
            // Determine which foreign account and balance to use
            if ($currency === 'USD') {
                $foreignAccount = $user->usdForeignAccount;
                $balanceColumn = 'usd_balance';
            } else {
                $foreignAccount = $user->eurForeignAccount;
                $balanceColumn = 'eur_balance';
            }
    
            $before = $user->{$balanceColumn};
    
            if (!$isCredit && $before < $amount) {
                DB::rollBack();
                session()->flash('message', "Insufficient {$currency} balance for debit.");
                Session::flash('type', 'error');
                Session::flash('title', 'Error');
                return back();
            }
    
            $after = $isCredit ? $before + $amount : $before - $amount;
            $user->update([$balanceColumn => $after]);
    
            $trx = strtoupper(Str::random(16));
            $type = $isCredit ? 1 : 0; // 1 = credit, 0 = debit
            $description = $reason;
            $serviceType = $isCredit ? 'Account Credit' : 'Account Debit';
    
            $transaction = $user->transaction()->create([
                'user_level_id'   => $user->user_level_id,
                'wallet_id'       => '1',
                'service'         => 'System',
                'service_type'    => "Wallet Funding",
                'description'     => $description,
                'amount'          => $amount,
                'real_price'      => $amount,
                'charge'          => 0,
                'phone_number'    => $user->phone_number,
                'type'            => $type,
                'initial_balance' => $before,
                'final_balance'   => $after,
                'reference'       => $trx,
                'status'          => 'success',
                'currency'        => $currency,
            ]);

            addSiteActitvity(auth()->guard('admin')->user(), [
                'activity' => "Admin {$currency} wallet " . ($isCredit ? 'credit' : 'debit'),
                'actions' => 'update', 'module' => 'User', 'user_type' => '2',
                'extra' => json_encode(['id' => $user->id, 'reference' => $trx, 'amount' => $amount]),
            ]);
    
            DB::commit();

            $targetUsername = $user->profile?->username ?? $user->username ?? ("user#" . $user->id);
            $targetFullName = trim(($user->profile?->first_name ?? '') . ' ' . ($user->profile?->last_name ?? '')) ?: ($user->name ?? $targetUsername);
            $targetEmail = $user->email ?? null;
            $adminUser = auth()->guard('admin')->user();
            $adminUsername = $adminUser?->username ?? $adminUser?->name ?? ($adminUser?->id ? 'Admin #' . $adminUser->id : 'Admin');
            $actionVerb = $isCredit ? 'credited' : 'debited';
            $actionTitle = $isCredit ? 'Credit' : 'Debit';

            SecurityAlertService::record(
                'admin_foreign_wallet_adjustment',
                'high',
                "Admin {$currency} wallet adjustment",
                "An admin {$actionVerb} {$currency} wallet for @{$targetUsername} ({$targetFullName}).",
                auth()->guard('admin')->id(),
                request()->ip(),
                [
                    'user_id' => $user->id,
                    'username' => $targetUsername,
                    'user_name' => $targetFullName,
                    'user_email' => $targetEmail,
                    'admin_username' => $adminUsername,
                    'currency' => $currency,
                    'amount' => (float) $amount,
                    'action' => $actionTitle,
                    'reference' => $trx,
                ]
            );
            SecurityAlertService::sendTransactionNotice([
                'action' => $actionTitle,
                'currency' => $currency,
                'amount' => $amount,
                'user_id' => $user->id,
                'username' => $targetUsername,
                'user_name' => $targetFullName,
                'user_email' => $targetEmail,
                'admin_username' => $adminUsername,
                'reference' => $trx,
                'time' => now(),
            ]);

            // Notifications are best effort and must never roll back committed funds.
            if ($alert) {
                try {
                $actionWord = $isCredit ? 'CREDIT' : 'DEBIT';
                $formattedDate = now()->format('d-M-Y h:i A');
                $formattedAmount = number_format($amount, 2);
                $formattedBalance = number_format($after, 2);
    
                // Foreign account details (fallbacks if missing)
                $accountName = strtoupper($foreignAccount->account_name ?? (($user->profile->first_name ?? '') . ' ' . ($user->profile->last_name ?? '')));
                $accountNumber = $foreignAccount->account_number ?? 'N/A';
                $maskedAccount = strlen($accountNumber) > 4
                    ? substr($accountNumber, 0, 1) . 'XX..' . substr($accountNumber, -2)
                    : $accountNumber;
    
                $message = "
                    <p>This is to inform you that a transaction has occurred on your <strong>Dupay {$currency} Account</strong> with the details below:</p>
                    <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Account Name</td>
                            <td style='padding:8px; border:1px solid #ddd;'><strong>{$accountName}</strong></td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$actionWord} ALERT</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Amount</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$currency} {$formattedAmount}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Currency</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$currency}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Account Number</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$maskedAccount}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Narration</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$description}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Reference</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$trx}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Date and Time</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Available Balance</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$currency} {$formattedBalance}</td>
                        </tr>
                    </table>
                    <br>
                    <p>Thank you for choosing <strong>Dupay</strong>. We appreciate your trust in our services.</p>
                ";
    
                $subject = "{$currency} Account {$actionWord} Notification";
                if (filter_var($user->email, FILTER_VALIDATE_EMAIL) &&
                    filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)) {
                    $this->sendMail($user->email, $user->profile->first_name ?? '', $subject, $message);
                }
                } catch (\Throwable $notificationError) {
                    \Log::warning('Admin foreign wallet notification failed', ['transaction_id' => $transaction->id]);
                }
            }
    
            $msg = $isCredit ? 'Credited' : 'Debited';
            session()->flash('message', "User {$currency} Account {$msg} Successfully.");
            Session::flash('type', 'success');
            Session::flash('title', 'Success');
    
            return back();
        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) DB::rollBack();
    
            \Log::error('Admin foreign wallet adjustment failed', ['user_id' => $id, 'currency' => $currency, 'exception' => get_class($th)]);
            session()->flash('message', 'Transaction failed. No balance change was committed; check the transaction history before retrying.');
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
    
            return back();
        }
    }
    
    public function transactions($id, Request $request)
    {
        $user = User::with('profile')->findOrFail($id);
    
        $currency = strtoupper($request->query('currency', ''));
    
        $data["page_title"] = "User Transactions";
        $data["page_sub_title"] = "{$user->profile->username} Transactions";
        $data["user"] = $user;
        $data["currency"] = $currency;
        $data["ajaxUrl"] = route('admin.user.fyc.transactions.ajax', ['id' => $user->id, 'currency' => $currency]);
        $data["breadcrumb"] = [
            ["title" => "Home", "url" => route('admin.index')],
            ["title" => "Users", "url" => route('admin.user.index')],
            ["title" => $user->profile->username, "url" => route('admin.user.show', $user->id)],
            ["title" => "Transactions", "url" => null],
        ];
    
        // Log activity
        $ac = [
            'activity' => "Visited transactions page for user: {$user->profile->username}",
            'actions' => "visit",
            'module' => "User",
            'user_type' => "2",
            'extra' => json_encode(["id" => $user->id, "currency" => $currency]),
        ];
        addSiteActitvity(auth()->guard('admin')->user(), $ac);
    
        return view('admin.user.transactions', $data);
    }
    
    
    public function transactionsAjax(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $currency = strtoupper($request->query('currency', ''));
    
        $transactions = $user->foreignTransactions()
            ->when($currency, function ($query, $currency) {
                $query->where('currency', $currency);
            })
            ->orderByDesc('id');
    
        return DataTables::eloquent($transactions)
            ->addColumn('foreign_account_number', fn($t) => optional($t->foreignAccount)->account_number ?? 'N/A')
            ->addColumn('foreign_bank_name', fn($t) => optional($t->foreignAccount)->bank_name ?? 'N/A')
            ->editColumn('foreign_account_id', fn($t) => $t->foreign_account_id)
            ->editColumn('type', fn($t) => ucfirst($t->type))
            ->editColumn('kind', fn($t) => ucfirst($t->kind ?? 'N/A'))
            ->editColumn('amount', fn($t) => getPrice($t->amount))
            ->editColumn('currency', fn($t) => strtoupper($t->currency))
            ->editColumn('description', fn($t) => $t->description ?? '—')
            ->editColumn('status', function ($t) {
                $badgeClass = match ($t->status) {
                    'success' => 'badge bg-success',
                    'failed' => 'badge bg-danger',
                    'pending' => 'badge bg-warning text-dark',
                    default => 'badge bg-secondary',
                };
                return '<span class="'.$badgeClass.'">'.ucfirst($t->status).'</span>';
            })
            ->editColumn('balance_before', fn($t) => getPrice($t->balance_before))
            ->editColumn('balance_after', fn($t) => getPrice($t->balance_after))
            ->editColumn('payer_name', fn($t) => $t->payer_name ?? '—')
            ->editColumn('payer_bank_name', fn($t) => $t->payer_bank_name ?? '—')
            ->editColumn('payer_account_number', fn($t) => $t->payer_account_number ?? '—')
            ->editColumn('payer_routing_number', fn($t) => $t->payer_routing_number ?? '—')
            ->editColumn('fee', fn($t) => getPrice($t->fee))
            ->editColumn('amount_settled', fn($t) => getPrice($t->amount_settled))
            ->editColumn('reference_id', fn($t) => $t->reference_id ?? '—')
            ->editColumn('created_at', fn($t) => $t->created_at->format('Y-m-d H:i'))
            ->addColumn('action', fn($t) => view('admin.user._trans_action', compact('t'))->render())
            ->rawColumns(['status', 'action'])
            ->make(true);
    }
    
    // public function transactions($id)
    // {
    //     $user = User::with('profile')->findOrFail($id);
    
    //     $data["page_title"] = "User Transactions";
    //     $data["page_sub_title"] = "{$user->profile->username} Transactions";
    //     $data["user"] = $user;
    //     $data["ajaxUrl"] = route('admin.user.fyc.transactions.ajax', $user->id);
    //     $data["breadcrumb"] = [
    //         ["title" => "Home", "url" => route('admin.index')],
    //         ["title" => "Users", "url" => route('admin.user.index')],
    //         ["title" => $user->profile->username, "url" => route('admin.user.show', $user->id)],
    //         ["title" => "Transactions", "url" => null],
    //     ];
    
    //     // Log activity
    //     $ac['activity'] = "Visited transactions page for user: {$user->profile->username}";
    //     $ac['actions'] = "visit";
    //     $ac['module'] = "User";
    //     $ac['user_type'] = "2";
    //     $ac["extra"] = json_encode(["id" => $user->id]);
    //     addSiteActitvity(auth()->guard('admin')->user(), $ac);
    
    //     return view('admin.user.transactions', $data);
    // }
    
    // public function transactionsAjax($id)
    // {
    //     $user = User::findOrFail($id);
    
    //     // $transactions = ForeignAccountTransaction::whereHas('foreignAccount', function ($query) use ($user) {
    //     //     $query->where('user_id', $user->id);
    //     // })->orderByDesc('id');
        
    //     $transactions = $user->foreignTransactions();
    
    //     return DataTables::eloquent($transactions)
    //         ->addColumn('foreign_account_number', function ($t) {
    //             return optional($t->foreignAccount)->account_number ?? 'N/A';
    //         })
    //         ->addColumn('foreign_bank_name', function ($t) {
    //             return optional($t->foreignAccount)->bank_name ?? 'N/A';
    //         })
    //         ->editColumn('foreign_account_id', fn($t) => $t->foreign_account_id)
    //         ->editColumn('type', fn($t) => ucfirst($t->type))
    //         ->editColumn('kind', fn($t) => ucfirst($t->kind ?? 'N/A'))
    //         ->editColumn('amount', fn($t) => getPrice($t->amount))
    //         ->editColumn('currency', fn($t) => strtoupper($t->currency))
    //         ->editColumn('description', fn($t) => $t->description ?? '—')
    //         ->editColumn('status', function ($t) {
    //             $badgeClass = match ($t->status) {
    //                 'success' => 'badge bg-success',
    //                 'failed' => 'badge bg-danger',
    //                 'pending' => 'badge bg-warning text-dark',
    //                 default => 'badge bg-secondary',
    //             };
    //             return '<span class="'.$badgeClass.'">'.ucfirst($t->status).'</span>';
    //         })
    //         ->editColumn('balance_before', fn($t) => getPrice($t->balance_before))
    //         ->editColumn('balance_after', fn($t) => getPrice($t->balance_after))
    //         ->editColumn('payer_name', fn($t) => $t->payer_name ?? '—')
    //         ->editColumn('payer_bank_name', fn($t) => $t->payer_bank_name ?? '—')
    //         ->editColumn('payer_account_number', fn($t) => $t->payer_account_number ?? '—')
    //         ->editColumn('payer_routing_number', fn($t) => $t->payer_routing_number ?? '—')
    //         ->editColumn('fee', fn($t) => getPrice($t->fee))
    //         ->editColumn('amount_settled', fn($t) => getPrice($t->amount_settled))
    //         ->editColumn('reference_id', fn($t) => $t->reference_id ?? '—')
    //         ->editColumn('created_at', fn($t) => $t->created_at->format('Y-m-d H:i'))
    //         ->addColumn('action', function ($t) {
    //             return view('admin.user._trans_action', compact('t'))->render();
    //         })
    //         ->rawColumns(['status', 'action'])
    //         ->make(true);
    // }
    
    public function destroyTranx($id)
    {
        $tranx = ForeignAccountTransaction::findOrFail($id);
    
        // Get related user and currency
        $userId = optional($tranx->foreignAccount)->user_id;
        $currency = $tranx->currency ?? 'USD';
    
        // Delete transaction
        $tranx->delete();
    
        // Log admin activity
        // $ac['activity'] = "Deleted transaction {$id}";
        // $ac['actions'] = "delete";
        // $ac['module'] = "Transaction";
        // $ac["extra"] = json_encode(["id" => $id]);
        // addSiteActitvity(auth()->guard('admin')->user(), $ac);
    
        // Flash message
        session()->flash('message', "Transaction Deleted Successfully");
        session()->flash('type', 'success');
        session()->flash('title', 'Success');
    
        // Redirect back to user’s transactions (with currency)
        return redirect()->route('admin.user.fyc.transactions', [$userId, $currency]);
    }
    

    public function updateDailyTransactionLimit(Request $request, $username)
    {
        $validated = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:0'],
            'reason' => 'nullable|string|max:255',
            'action' => 'nullable|string',
            'alert' => 'nullable|string',
        ]);
    
        if ($validated->fails()) {
            return redirect()->back()->withErrors($validated)->withInput();
        }
    
        $action = $request->action == 'on' ? 'Increase' : 'Decrease';
        $alert = $request->alert == 'on';
        $reason = $request->reason ?: "Daily transaction limit {$action}d by admin";
    
        $user = User::whereHas('profile', function (Builder $query) use ($username) {
            $query->where('username', $username)->orWhere('ref_id', $username);
        })->firstOrFail();
    
        $oldLimit = $user->daily_transaction_limit;
    
        $newLimit = $action === "Increase"
            ? $oldLimit + $request->amount
            : max(0, $oldLimit - $request->amount);
    
        $user->update(['daily_transaction_limit' => $newLimit]);
    
        if ($alert) {
            try {
                $subject = "Notice: Update to Your Daily Transaction Limit";

                $text = "
                <p>
                We would like to notify you that your <strong>daily transaction limit</strong> has been 
                <b>{$action}d</b> on your Dupay account.
                </p>
                
                <p>
                Please find the details of this update below:
                </p>
                
                <p style='line-height: 1.6;'>
                <strong>Previous Limit:</strong> ₦" . number_format($oldLimit, 2) . "<br>
                <strong>New Limit:</strong> ₦" . number_format($newLimit, 2) . "<br>
                <strong>Amount " . ($action === 'Increase' ? 'Added' : 'Deducted') . ":</strong> ₦" . number_format($request->amount, 2) . "<br>
                <strong>Reason:</strong> {$reason}<br>
                <strong>Effective Date:</strong> " . Carbon::now()->format('d M Y, h:i A') . "
                </p>
                
                <p>
                This adjustment was carried out by our system administrators to ensure that your account remains compliant with Dupay’s transaction policies and your current activity level.
                </p>
                
                <p>
                If this update was not expected or you have concerns about the change, please contact our support team immediately through your Dupay dashboard or via our official channels. Our team is available to assist you at any time.
                </p>
                
                <p>
                Thank you for your continued trust in <strong>Dupay</strong>. We remain committed to providing a secure, transparent, and reliable digital financial experience for all our users.
                </p>
                
                <p>
                Warm regards,<br>
                <strong>The Dupay Team</strong><br>
                <span style='font-size:13px; color:#555;'>Dupay Technologies Ltd.</span><br>
                <span style='font-size:13px; color:#777;'>Secure • Transparent • Reliable</span>
                </p>
                
                <hr style='border:none; border-top:1px solid #eee; margin:20px 0;'>
                <p style='font-size:12px; color:#777;'>
                This is an automated notification from Dupay. Please do not reply to this email.  
                For assistance, visit <a href='https://dupaysupport.com' style='color:#007bff; text-decoration:none;'>dupaysupport.com</a>.
                </p>
                ";
    
                $this->sendMail($user->email, $user->profile->first_name, $subject, $text);
            } catch (\Throwable $th) {
                Log::error("Failed to send transaction limit update notification: " . $th->getMessage());
            }
        }
    
        $ac = [
            'activity' => "{$action}d daily transaction limit for {$username} from " . getPrice($oldLimit) . " to " . getPrice($newLimit),
            'actions' => 'update',
            'module' => 'User',
            'user_type' => '2',
            'extra' => json_encode([
                'id' => $user->id,
                'old_limit' => $oldLimit,
                'new_limit' => $newLimit,
                'action' => $action,
                'amount' => $request->amount,
                'reason' => $reason,
            ]),
        ];
    
        addSiteActitvity(auth()->guard('admin')->user(), $ac);
    
        Session::flash('message', "Daily transaction limit {$action}d successfully from " . getPrice($oldLimit) . " to " . getPrice($newLimit));
        Session::flash('type', 'success');
        Session::flash('title', 'Success');
    
        return redirect()->back();
    }
    
        
    public function sendUserNotification(Request $request, $username)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // Find user by username only (exact match on profile)
        $user = User::whereHas('profile', function($q) use ($username) {
            $q->where('username', $username);
        })->first();

        if (!$user) {
            session()->flash('message', 'User not found: ' . $username);
            Session::flash('type', 'error');
            return back();
        }

        if (empty($user->fcmToken)) {
            session()->flash('message', 'User @' . $username . ' has no device token. They need to log into the app first.');
            Session::flash('type', 'warning');
            return back();
        }

        // Use sendFcmToMultipleDevices with single token array — same as broadcast (proven to work)
        $result = sendFcmToMultipleDevices(
            [$user->fcmToken],
            $request->title,
            $request->message,
            ['type' => 'custom', 'username' => $username]
        );

        \Log::info('Admin notification to @' . $username . ': ' . json_encode($result));

        session()->flash('message', 'Notification sent to @' . $username . ' successfully.');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');
        return back();
    }
    




    
}
