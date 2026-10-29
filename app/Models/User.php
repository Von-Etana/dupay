<?php

namespace App\Models;


use App\Models\Fintech\WithdrawalLog;
use App\Models\Fintech\Beneficiary;
use App\Models\Fintech\PosRequest;
use App\Models\Fintech\VirtualCard;
use App\Models\Telecom\DataCardLog;
use App\Models\Telecom\VirtualAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
//use Laravel\Sanctum\HasApiTokens;
use Laravel\Passport\HasApiTokens;
use Shetabit\Visitor\Traits\Visitor;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Fintech\WalletVerificaiton;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use Visitor;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    public $guarded=[];
    
    // protected $fillable = ['payscribe_customer_id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        's_password'
        , 's_pin', 'pin', 'transaction_pin', 'email_code', 'phone_code', 'fcmToken'
    ];
    
    protected $appends = ['hasVerifyWallet', 'has_transaction_pin'];

    public function getHasTransactionPinAttribute(): bool
    {
        return !empty($this->transaction_pin);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [

                'first_name' => 'required',
                'first_name' => 'required',
                'username'  => 'required|alpha_dash|unique:profiles,username' . ($id ? ",$id" : ''),
                'email'  => 'required|email|unique:user,email' . ($id ? ",$id" : ''),
                'password' => $id ? "" : ['required', 'string', 'min:4', 'confirmed'],
                'status'  => '',
            ],
            $merge);
    }

    public function profile(){
        return $this->hasOne(Profile::class);
    }

    public function profileKYC(){
        return $this->hasOne(ProfileKyc::class);
    }

    public function posRequest(){
        return $this->hasOne(PosRequest::class);
    }
    
    public function walletVerification(){
        return $this->hasOne(WalletVerificaiton::class);
    }

    public function level(){
        return $this->belongsTo(UserLevel::class, "user_level_id");
    }

    public function transaction(){
        return $this->hasMany(Transaction::class);
    }

    public function latestTransaction(){
        return $this->hasMany(Transaction::class)->latest()->take(10);
    }

    public function payment(){
        return $this->hasMany(Payment::class);
    }
    
    public function withdrawalLog(){
        return $this->hasMany(WithdrawalLog::class);
    }


    public function dataCardLog(){
        return $this->hasMany(DataCardLog::class);
    }


    public function beneficiary(){
        return $this->hasMany(Beneficiary::class)->latest()->take(10);
    }


    public function virtualAccount(){
        return $this->hasOne(VirtualAccount::class);
    }

    public function activitySummary(){
        return $this->hasMany(SiteActivity::class)->where("user_type", "1")->latest();
    }


    public function virtualCard()
    {
        return $this->hasMany(VirtualCard::class);
    }
    public function physicalCard()
    {
        return $this->hasOne(PhysicalCard::class);
    }
    
    public function referred_users()
    {
        return $this->hasMany(User::class, 'referral');
    }

    
    public function getHasVerifyWalletAttribute(){
        // Only status 1 (Approved/Verified) counts as verified.
        // 0 = Pending, 2 = Rejected must both return "0".
          return  ($this->walletVerification != null && (int) $this->walletVerification->status === 1) ? "1" : "0";
    }
    
    public function foreignAccount()
    {
        return $this->hasMany(ForeignAccount::class);
    }
    
    public function foreignWallet()
    {
        return $this->hasOne(ForeignWallet::class);
    }
    
    public function referrerProfile()
    {
        return $this->hasOne(Profile::class, 'ref_id', 'referral');
    }
    
    public function getReferrerNameAttribute()
    {
        if ($this->referrerProfile) {
            return trim($this->referrerProfile->first_name . ' ' . $this->referrerProfile->last_name);
        }

        return 'No Referrer';
    }
    
    public function referrals()
    {
        return $this->hasMany(Profile::class, 'ref_id', 'referral');
    }
    
    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referral', 'profile.ref_id')
            ->join('profiles', 'profiles.ref_id', '=', 'users.referral')
            ->select('users.*');
    }

    public function usdForeignAccount()
    {
        return $this->hasOne(ForeignAccount::class)->where('currency', 'USD');
    }

    public function eurForeignAccount()
    {
        return $this->hasOne(ForeignAccount::class)->where('currency', 'EUR');
    }
    
    public function foreignTransactions()
    {
        return $this->hasMany(Fintech\ForeignAccountTransaction::class);
    }
    
    public function getUsdTotalCreditAttribute()
    {
        return $this->foreignTransactions()
            ->where('currency', 'USD')
            ->where('type', 'credit')
            ->sum('amount');
    }
    
    public function getUsdTotalDebitAttribute()
    {
        return $this->foreignTransactions()
            ->where('currency', 'USD')
            ->where('type', 'debit')
            ->sum('amount');
    }
    
    public function getEurTotalCreditAttribute()
    {
        return $this->foreignTransactions()
            ->where('currency', 'EUR')
            ->where('type', 'credit')
            ->sum('amount');
    }
    
    public function getEurTotalDebitAttribute()
    {
        return $this->foreignTransactions()
            ->where('currency', 'EUR')
            ->where('type', 'debit')
            ->sum('amount');
    }

    public  function column()
    {
        $level = UserLevel::all();
        $profile = Profile::all();
        $option  =
            [
                [
                    "name" => "Banned",
                    "id" => "1"
                ],
                [
                    "name" => "Active",
                    "id" => "0"
                ]
            ]
        ;
        $column = [
            // "profile_img" => [
            //     "name" => "Image",
            //     "description" => "Choose image",
            //     "column_name" => "profile_img",
            //     "input_type" => "7",
            //     "value" => "",
            //     "model" => "profile->getProfileImage()",
            //     "required" => "",
            // ],
            // "account_type" => [
            //     "name" => "Account Type",
            //     "description" => "Enter Account Type",
            //     "column_name" => "account_type",
            //     "input_type" => "0",
            //     "value" => "",
            //     "required" => "required",
            //     "model" => "profile->account_type",
            // ],
            "username" => [
                "name" => "Username",
                "description" => "Enter Username",
                "column_name" => "username",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
                "model" => "profile->username",
                "link" => route('admin.user.show', "profile->username")
            ],
            "balance" => [
                "name" => "Balance",
                "description" => "Enter Balance",
                "column_name" => "balance",
                "input_type" => "0",
                "value" => "",
                "editable" => true,
                "required" => "required",
                "isPrice" => true
            ],
            "first_name" => [
                "name" => "First Name",
                "description" => "Enter Firstname",
                "column_name" => "first_name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
                "model" => "profile->first_name",
            ],
            "last_name" => [
                "name" => "Last Name",
                "description" => "Enter Lastname",
                "column_name" => "last_name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
                "model" => "profile->last_name",
            ],

            "email" => [
                "name" => "Email",
                "description" => "Enter Email",
                "column_name" => "email",
                "input_type" => "2",
                "value" => "",
                "editable" => true,
                "required" => "required",
            ],
            "password" => [
                "name" => "Password",
                "description" => "Enter Password",
                "column_name" => "password",
                "input_type" => "1",
                "value" => "",
                "required" => "required",
                "edit" => false,
                "view" => false
            ],
            "user_level_id" => [
                "name" => "Level",
                "description" => "Please Select",
                "column_name" => "user_level_id",
                "id" => "roles",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $level,
               // "view" => false
                //"dataColumn" => ["id" => "id", "name" => "name"]
            ],
            // "ban_status" => [
            //     "name" => "Ban",
            //     "description" => "Please Select",
            //     "column_name" => "ban_status",
            //     "input_type" => "4",
            //     "required" => "required",
            //     "value" => "",
            //     "use_switch" => true,
            //     "option" => $option,
            // ]

        ];
        return json_decode(json_encode($column));
    }



    public function tableFilter(){
        return '
        <div class="row mb-3">
        <div class="col-md-6">
        <label> Search </label>
        <input class="form-control" id="ismartSearch" type="text" placeholder="Enter username, phone number or email">
        </div>
        <div class="col-md-6">
        <label> Filter </label>

                        <select id="filter" class="form-control">
                                    <option value="all">Show All </option>
                                    <option value="ban"> Ban </option>
                                    <option value="active"> Active </option>
                                    <option value="email_verify"> Email Verified </option>
                                    <option value="email_unverify"> Email UnVerified </option>
                                    <option value="phone_verify"> Phone Verified </option>
                                    <option value="phone_unverify"> Phone UnVerified </option>


                        </select>
                        </div>
                        </div>
        ';
    }


    public function filterMethod($model, $filter){
        if($model != null && $filter != ""){
            if ($filter == "all") {
            } else if ($filter == "ban") {
                $model->where('ban_status', "1");
            } else if ($filter == "active") {
                $model->where('ban_status', "0");
            } else if ($filter == "email_verify") {
                $model->where('email_verify', "1");
            } else if ($filter == "email_unverify") {
                $model->where('email_verify', "0");
            }else if ($filter == "phone_verify") {
                $model->where('phone_verify', "1");
            }else if ($filter == "phone_unverify") {
                $model->where('phone_verify', "0");
            }else if ($filter == "high_balance") {
                $model->orderBy('balance', "DESC");
            }else if ($filter == "low_balance") {
                $model->orderBy('balance', "ASC");
            }
        }
        return $model;
    }


    public function searchMethod($model, $search){
        if($model != null && $search != ""){
            $model->where('phone_number', "like", "%$search%")->orWhere('email', "like", "%$search%")->OrwhereHas('profile', function (Builder $query) use ($search) {
                $query->where('username', "like", "%$search%");
            });
        }
        return $model;
    }

}
