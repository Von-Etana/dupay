<?php

namespace App\Models;

use App\Models\Telecom\ApiConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\Visitable;
use Illuminate\Database\Eloquent\Builder;

class Transaction extends Model
{
    use HasFactory;
    use Visitable;
    public $guarded=[];

    protected static function booted()
    {
        // Fire an Android push when a transaction is CREATED already-successful.
        // P2P transfers, maintenance fees, and most instant services insert rows
        // with status = 'success' directly, so they trigger created(), not updated().
        static::created(function ($transaction) {
            try {
                if (strtolower($transaction->status) === 'success') {
                    $transaction->sendTransactionPush();
                }
            } catch (\Throwable $e) {
                \Log::error('Transaction push failed on create (txn already saved): ' . $e->getMessage());
            }
        });

        // Also fire when a pending transaction is later UPDATED to success
        // (e.g. some payment-gateway flows that begin as pending).
        static::updated(function ($transaction) {
            try {
                if (
                    $transaction->wasChanged('status') &&
                    strtolower($transaction->status) === 'success'
                ) {
                    $transaction->sendTransactionPush();
                }
            } catch (\Throwable $e) {
                \Log::error('Transaction push failed on update (txn already saved): ' . $e->getMessage());
            }
        });
    }

    /**
     * Build + send the per-user push for this transaction.
     *
     * Bank-style alert format:
     *   Transaction Alert
     *   Acct: *****3833
     *   Amt: NGN 7,000.00 CR
     *   Desc: <narration>
     *   Avail Balance: NGN 7,000.15
     *
     * type "1" = Credit (money in), "0" = Debit (money out).
     * Never throws (guarded by the model events that call it).
     */
    public function sendTransactionPush()
    {
        $user = $this->user;
        if (!$user || empty($user->fcmToken)) {
            return; // no user or no device token — skip silently
        }

        // Read RAW stored attributes, bypassing this model's accessors.
        // getAmountAttribute()/getFinalBalanceAttribute() format the value AND
        // rely on getRawOriginal(), which is empty on a freshly-created row —
        // that previously produced "NGN 0.00" and the wrong Credit/Debit label.
        $attrs = $this->getAttributes();

        // ---- Direction: type "1" = Credit, "0" = Debit ----
        $isCredit = (string) ($attrs['type'] ?? '') === '1';
        $drcr     = $isCredit ? 'CR' : 'DR';

        // ---- Currency code (NGN / USD / EUR) ----
        $currency = $attrs['currency'] ?? 'NGN';
        if (empty($currency)) { $currency = 'NGN'; }

        // ---- Amounts ----
        $amount  = number_format((float) ($attrs['amount'] ?? 0), 2);
        $balance = isset($attrs['final_balance']) && $attrs['final_balance'] !== null
            ? number_format((float) $attrs['final_balance'], 2)
            : null;

        // ---- Narration / description (kept compact) ----
        $desc = $attrs['description']
            ?? ($attrs['service_type'] ?? ($attrs['service'] ?? 'Transaction'));
        $desc = trim((string) $desc);
        if (mb_strlen($desc) > 80) {
            $desc = mb_substr($desc, 0, 77) . '...';
        }

        // ---- Masked account number: Acct: *****3833 ----
        // Prefer the user's virtual (wallet) account number, fall back to phone.
        $acctNo     = optional($user->virtualAccount)->account_number ?: $user->phone_number;
        $acctDigits = $acctNo ? preg_replace('/\D/', '', $acctNo) : '';
        $maskedAcct = strlen($acctDigits) >= 4
            ? '*****' . substr($acctDigits, -4)
            : '*****';

        // ---- Build the bank-style body ----
        $title   = 'Transaction Alert';
        $lines   = [];
        $lines[] = 'Acct: ' . $maskedAcct;
        $lines[] = 'Amt: ' . $currency . ' ' . $amount . ' ' . $drcr;
        if ($desc !== '') {
            $lines[] = 'Desc: ' . $desc;
        }
        if ($balance !== null) {
            $lines[] = 'Avail Balance: ' . $currency . ' ' . $balance;
        }
        $body = implode("\n", $lines);

        // ---- Data payload (for Flutter routing / in-app rendering) ----
        $data = [
            'type'          => $isCredit ? 'credit' : 'debit',
            'dr_cr'         => $drcr,
            'account'       => $maskedAcct,
            'currency'      => (string) $currency,
            'amount'        => (string) $amount,
            'avail_balance' => (string) ($balance ?? ''),
            'description'   => $desc,
            'reference'     => (string) ($attrs['reference'] ?? ($this->reference ?? '')),
            'transaction'   => (string) $this->id,
            'status'        => 'success',
        ];

        // Same proven per-user FCM sender used by the admin "Send Notification"
        // feature. It logs its own errors and returns a status array (never throws).
        sendFcmNotification($user->fcmToken, $title, $body, $data);
    }

   // protected $appends = ['status'];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function profile(){
        return $this->belongsTo(Profile::class,'user_id','user_id');
    }

    public function payment(){
        return $this->belongsTo(Payment::class, "paymentReference", "reference");
    }

    public function tStatus(){
        return $this->belongsTo(Status::class,'name', 'status');
    }

    // public function getStatusAttribute(){
    //     return  $this->tStatus->name ?? "";
    // }





    public function column()
    {
        $Typeoption = [
            [
                "name" => "Credit",
                "id" => "1"
            ],
            [
                "name" => "Debit",
                "id" => "0"
            ]
        ];
        
        $option = Status::all();
        
        $column = [
            "username" => [
                "name" => "User",
                "description" => "Please Select",
                "column_name" => "username",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "model" => "user->profile->username",
                "link" => route('admin.user.show', '@@this@@')
            ],
            "service" => [
                "name" => "Service",
                "description" => "Enter Service",
                "column_name" => "service",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "view" => false,
            ],
            "service_type" => [
                "name" => "Service Type",
                "description" => "Enter Serivce Type",
                "column_name" => "service_type",
                "input_type" => "0",
                "required" => "required",
                "value" => "",
                "view" => false,
            ],
            "description" => [
                "name" => "Description",
                "description" => "Transaction Description",
                "column_name" => "description",
                "input_type" => "0",
                // "editable" => true,
                "value" => "",
                "required" => "required",
            ],
            "currency" => [
                "name" => "Currency",
                "description" => "Transaction Currency",
                "column_name" => "currency",
                "input_type" => "0",
                // "editable" => true,
                "value" => "",
                "required" => "required",
            ],
            "amount" => [
                "name" => "Amount",
                "description" => "Enter Amount",
                "column_name" => "amount",
                "input_type" => "0",
                "value" => "",
                // "editable" => true,
                "required" => "required",
                "isPrice" => true
            ],
            "initial_balance" => [
                "name" => "Initial Balance",
                "description" => "Initial Balance",
                "column_name" => "initial_balance",
                "input_type" => "0",
                "value" => "",
                // "editable" => true,
                "required" => "required",
                "isPrice" => true
            ],
            "final_balance" => [
                "name" => "Final Balance",
                "description" => "Enter final Balance",
                "column_name" => "final_balance",
                "input_type" => "0",
                "value" => "",
                // "editable" => true,
                "required" => "required",
                "isPrice" => true
            ],
            "charge" => [
                "name" => "Profit",
                "description" => "Enter profit",
                "column_name" => "charge",
                "input_type" => "0",
                "value" => "",
                // "editable" => true,
                "required" => "required",
                "isPrice" => true
            ],
    
            "type" => [
                "name" => "Transaction Type",
                "description" => "Please Select",
                "column_name" => "type",
                "input_type" => "4",
                "value" => "",
                "option" => $Typeoption,
                "required" => "required",
            ],
            "phone_number" => [
                "name" => "Phone Number",
                "description" => "Enter Phone Number",
                "column_name" => "phone_number",
                "input_type" => "0",
                "value" => "",
                // "editable" => true,
                "required" => "required",
            ],
            "reference" => [
                "name" => "Reference ID",
                "description" => "Enter Refrence Number",
                "column_name" => "reference",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "date" => [
                "name" => "Date",
                "description" => "Enter Date",
                "column_name" => "created_at",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "create" => false,
                "edit" => false
            ],
            "status" => [
                "name" => "status",
                "description" => "Select Status",
                "column_name" => "status",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "model" => "tStatus->name",
                "option" => $option,
            ]
        ];
        
        // Return as stdClass object
        return json_decode(json_encode($column), false);
    }

    public function getRawAmountAttribute()
    {
        return (float) $this->getRawOriginal('amount');
    }
    
    public function getRawChargeAttribute()
    {
        return (float) $this->getRawOriginal('charge');
    }
    
    public function getRawInitialBalanceAttribute()
    {
        return (float) $this->getRawOriginal('initial_balance');
    }
    
    public function getRawFinalBalanceAttribute()
    {
        return (float) $this->getRawOriginal('final_balance');
    }    


    public function getAmountAttribute()
    {
        return $this->formatWithCurrency($this->getRawOriginal('amount'));
    }
    
    public function getChargeAttribute()
    {
        return $this->formatChargeWithCurrency($this->getRawOriginal('charge'));
    }
    
    public function getInitialBalanceAttribute()
    {
        return $this->formatWithCurrency($this->getRawOriginal('initial_balance'));
    }
    
    public function getFinalBalanceAttribute()
    {
        return $this->formatWithCurrency($this->getRawOriginal('final_balance'));
    }


    /**
     * Format using transaction currency
     */
    private function formatWithCurrency($value)
    {
        $currency = $this->attributes['currency'] ?? 'NGN';
    
        switch ($currency) {
            case 'USD': $symbol = '$'; break;
            case 'EUR': $symbol = '€'; break;
            default: $symbol = '₦';
        }
    
        return $symbol . number_format((float)$value, 2);
    }

    /**
     * Format the charge (profit) column.
     * Reveals sub-cent fees (small USD/EUR conversion profit) instead of
     * rounding them to 0.00. Values of 0.01 and above keep the usual 2 dp.
     */
    private function formatChargeWithCurrency($value)
    {
        $currency = $this->attributes['currency'] ?? 'NGN';

        switch ($currency) {
            case 'USD': $symbol = '$'; break;
            case 'EUR': $symbol = '€'; break;
            default: $symbol = '₦';
        }

        $num = (float) $value;
        $decimals = ($num != 0.0 && abs($num) < 0.01) ? 4 : 2;

        return $symbol . number_format($num, $decimals);
    }





    public function tableFilter(){
        return '
        <!-- First Row: Search, Type Filter, Currency Filter -->
        <div class="row mb-3">
            <div class="col-md-4">
                <label> Search </label>
                <input class="form-control" id="ismartSearch" type="text" placeholder="Enter username, phone number or reference">
            </div>
            <div class="col-md-4">
                <label> Type Filter </label>
                <select id="filter" class="form-control">
                    <option value="all">Show All </option>
                    <option value="debit"> Debit </option>
                    <option value="credit"> Credit </option>
                    <option value="success"> Success </option>
                    <option value="pending"> Pending </option>
                    <option value="cancel"> Cancel </option>
                    <option value="refund"> Refund </option>
                    <option value="airtime"> Airtime Topup </option>
                    <option value="data"> Data Topup </option>
                    <option value="cable"> Cable Subscription </option>
                    <option value="bill"> Bill Payment </option>
                    <option value="exam"> Result Checker </option>
                    <option value="funding"> Wallet Funding </option>
                    <option value="withdrawal"> Withdrawal </option>
                    <option value="transfer"> Transfer </option>
                </select>
            </div>
            <div class="col-md-4">
                <label> Currency Filter </label>
                <select id="currency_filter" class="form-control">
                    <option value="all">All Currencies</option>
                    <option value="NGN">NGN</option>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                </select>
            </div>
        </div>
    
        <!-- Second Row: Date Range Filter with Button -->
        <div class="row mb-3">
            <div class="col-md-8">
                <label> Date Range Filter </label>
                <div class="input-group">
                    <input type="date" id="start_date" class="form-control" placeholder="Start Date">
                    <span class="input-group-text">to</span>
                    <input type="date" id="end_date" class="form-control" placeholder="End Date">
                </div>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button class="btn btn-primary w-100" id="applyDateFilter">Apply Date Filter</button>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button class="btn btn-secondary w-100" id="resetAllFilters">Reset All Filters</button>
            </div>
        </div>
        ';
    }


    public function bulkUpdateAction(){
       $apiConnection =  ApiConnection::whereStatus("1")->orderBy("id", "DESC")->get();
        return '
        <div class="m-3 bulkUpdateAction" hidden>
        <div class="row">
        <div class="col-lg-6 col-xm-12">
        <div class="card p-3">
       <h4>
       Update Status Manually
       </h4>
        <form action='.route('admin.editBulkTransactionStatus').' method="post"> 
        '.csrf_field().'
        
       '.getOtherFormInput('select', 'Status', 'status', '', json_decode(json_encode(['pending','success','unsuccessful','cancel','refund'])), 'required', '', '').'
       <button type="button" class="btn btn-primary btn-sm submitUpdateBulkUpdateAction">Update</button>
       
       </form>
       </div>
       </div>
       <div class="col-lg-6 col-xm-12">
       <div class="card p-3">
       <h4>
       Auto Check Status From API
       </h4>
       <form action='.route('admin.verifyTransactionApiStatus').' method="post"> 
       '.csrf_field().'
       '.getOtherFormInput('selectId', 'Api Connection', 'api_connection_id', '', json_decode(json_encode($apiConnection)), 'required', '', '').'
       '.getOtherFormInput('select', 'Auto Update Unsuccesful ', 'auto_update', 'No', json_decode(json_encode(['Yes','No'])), 'required', '', '').'
       '.getOtherFormInput('select', 'Auto Refund', 'auto_refund', 'No', json_decode(json_encode(['Yes','No'])), 'required', '', '').'
      <button type="button" class="btn btn-primary btn-sm submitCheckStatusApiAction">Verify Status from API</button>
      
      </form>
      </div>
      </div>
       </div>
       </div>
        ';
    }


    public function filterMethod($model, $filter){
        if($model != null && $filter != ""){
            if ($filter == "all") {
            } else if ($filter == "success") {
                $model->where('status', "success");
            } else if ($filter == "pending") {
                $model->where('status', "pending");
            } else if ($filter == "cancel") {
                $model->where('status', "cancel");
            } else if ($filter == "refund") {
                $model->where('status', "refund");
            } else if ($filter == "airtime") {
                $model->where('service_type', "Airtime Topup");
            } else if ($filter == "data") {
                $model->where('service_type', "Data Topup");
            } else if ($filter == "cable") {
                $model->where('service_type', "Cable Subscription");
            } else if ($filter == "bill") {
                $model->where('service_type', "Bill Payment");
            } else if ($filter == "exam") {
                $model->where('service_type', "Result Checker");
            } else if ($filter == "funding") {
                $model->where('service_type', "Wallet Funding");
            }else if ($filter == "transfer") {
                $model->where('service_type', "Fund Transfer");
            }else if ($filter == "withdrawal") {
                $model->where('service_type', "Fund Withdrawal");
            }
             else if ($filter == "debit") {
                $model->where('type', "0");
            } else if ($filter == "credit") {
                $model->where('type', "1");
            }
        }
        return $model;
    }



    public function searchMethod($model, $search)
{
    if ($model !== null) {
        // Convert search to string regardless of input type
        if (is_array($search)) {
            // If array, get the first element
            $search = array_shift($search) ?? '';
        } elseif (is_object($search)) {
            // If object, try to cast to string
            $search = (string)$search;
        }
        
        // Now ensure it's a string
        $search = trim((string)$search);
        
        if ($search !== '') {
            $model->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%$search%")
                  ->orWhere('reference', 'like', "%$search%")
                  ->orWhere('currency', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%")
                  ->orWhere('service', 'like', "%$search%")
                  ->orWhere('service_type', 'like', "%$search%")
                  ->orWhereHas('user.profile', function (Builder $query) use ($search) {
                      $query->where('username', 'like', "%$search%");
                  });
            });
        }
    }
    
    return $model;
}
    
    public function currencyFilter($model, $currency)
    {
        if ($model !== null && $currency !== "" && $currency !== "all") {
            $model->where('currency', $currency);
        }
        
        return $model;
    }
    
    public function dateRangeFilter($model, $startDate, $endDate)
    {
        if ($model !== null) {
            if ($startDate !== "" && $endDate !== "") {
                $endDate = date('Y-m-d', strtotime($endDate . ' +1 day'));
                $model->whereBetween('created_at', [$startDate, $endDate]);
            } elseif ($startDate !== "") {
                $model->whereDate('created_at', '>=', $startDate);
            } elseif ($endDate !== "") {
                $endDate = date('Y-m-d', strtotime($endDate . ' +1 day'));
                $model->whereDate('created_at', '<=', $endDate);
            }
        }
        
        return $model;
    }

    public function actionView(){
        return "admin.transaction._action";
    }

}