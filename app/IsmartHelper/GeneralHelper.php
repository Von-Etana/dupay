<?php
use App\Models\BasicSetting;
use App\Models\Fintech\Charge;
use App\Models\Payment;
use App\Models\ServiceType;
use App\Models\SiteActivity;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Jenssegers\Agent\Agent as Agent;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Google\Client as GoogleClient;
use GuzzleHttp\Client as GuzzleClient;
use App\Models\ExchangeRate;

function toFloat($value): float
{
    if (is_null($value) || $value === '') {
        return 0.0;
    }
    
    if (is_numeric($value)) {
        return (float) $value;
    }
    
    // Remove any non-numeric characters except decimal point and minus
    $cleaned = preg_replace('/[^\d.-]/', '', (string) $value);
    return (float) $cleaned;
}

function getPriceA($amount, $currency = 'NGN')
{
    $symbols = [
        'NGN' => '₦',
        'USD' => '$',
        'EUR' => '€',
    ];

    $symbol = $symbols[$currency] ?? '';

    return $symbol . number_format($amount, 2);
}


//   function updateUserBalance(float $amount, string $type = 'credit')
//     {
//         return DB::transaction(function () use ($amount, $type) {
            
//             Log::info($amount);

//             $user = Auth::user();  // ← authenticated user
//             if (! $user) {
//                 throw new \Exception("User not authenticated");
//             }

//             $wallet = User::where('id', $user->id)
//                             ->lockForUpdate()
//                             ->firstOrFail();

//             if ($type === 'credit') {
//                 $wallet->balance += $amount;
//             } else {
//                 if ($wallet->balance < $amount) {
//                     throw new \Exception("Insufficient balance");
//                 }
//                 $wallet->balance -= $amount;
//             }

//             $wallet->save();

//             return $wallet;
//         });
//     }
    
    
    function updateUserBalance($newBalance)
{
    $user = auth()->user();
    if (!$user) {
        return false;
    }

    $user->balance = $newBalance;
    return $user->save();
}
    
    
    

function formatCurrency($amount, $currency = 'NGN')
{
    $amount = preg_replace('/[^\d.\-]/', '', $amount);

    $amount = floatval($amount);

    $symbols = [
        'NGN' => '₦',
        'USD' => '$',
        'EUR' => '€',
    ];

    $symbol = $symbols[$currency] ?? '';

    return $symbol . number_format($amount, 2);
}


if (!function_exists('checkDailyTransactionLimit')) {
    /**
     * Kept for the existing call sites; the logic now lives in
     * TransactionLimitService so every channel shares one implementation.
     *
     * The old body only summed 'Fund Withdrawal' and 'Airtime Topup', so data,
     * cable, bills, P2P transfers, epins, exams, card funding and currency
     * conversions never counted toward the limit. It also ignored currency
     * (a $100 USD debit consumed ₦100 of a naira limit) and status (a failed or
     * refunded purchase still ate the limit).
     */
    function checkDailyTransactionLimit($user, $amount)
    {
        return \App\Services\TransactionLimitService::check($user, $amount, 'NGN');
    }
}

if (!function_exists('maskCardPan')) {
    function maskCardPan($pan)
    {
        if (!$pan) return '—';
        $visibleStart = substr($pan, 0, 4);
        $visibleEnd = substr($pan, -4);
        return $visibleStart . str_repeat('•', strlen($pan) - 8) . $visibleEnd;
    }
}

function convertCurrency(string $fromCurrency, string $toCurrency, float $amount, bool $applyFee = true): array
{
    $fromCurrency = strtoupper($fromCurrency);
    $toCurrency = strtoupper($toCurrency);
    $pair = "{$fromCurrency}-{$toCurrency}";

    $exchange = ExchangeRate::where('currency', $pair)->first();

    if (!$exchange) {
        return [
            'status' => false,
            'message' => "Exchange rate for {$pair} not found."
        ];
    }

    // ── Fee is charged in the SOURCE currency (the one being exchanged FROM) ──
    // e.g. USD -> NGN charges the fee in USD. The fee is deducted from the
    // amount FIRST, then only the remainder is converted to the target currency.
    // NOTE: both `fee` (a percentage, e.g. 1.5 = 1.5%) and `cap` are now
    // interpreted in the SOURCE currency.
    $feeAmount       = 0.0;      // denominated in $fromCurrency (source currency)
    $amountToConvert = $amount;  // source amount remaining after the fee

    if ($applyFee && $exchange->fee > 0) {
        $feeAmount = $amount * ($exchange->fee / 100);
        if (isset($exchange->cap) && $exchange->cap > 0 && $feeAmount > $exchange->cap) {
            $feeAmount = $exchange->cap;
        }
        $amountToConvert = $amount - $feeAmount;
    }

    // ---- SAFETY INVARIANT (anti money-minting) --------------------------------
    // A currency pair and its reverse must NEVER allow a profitable round-trip.
    // The NGN->USD / NGN->EUR exploit worked because those rates were stored
    // inverted (e.g. NGN-EUR = 1640 instead of 1/1640), so amount * rate turned
    // 120 NGN into 196,111 EUR. Refuse a non-positive or inverted rate.
    if ((float) $exchange->rate <= 0) {
        return ['status' => false, 'message' => "Exchange rate for {$pair} not configured."];
    }
    $__reverse = ExchangeRate::where('currency', "{$toCurrency}-{$fromCurrency}")->first();
    if ($__reverse && (float) $__reverse->rate > 0
        && ((float) $exchange->rate * (float) $__reverse->rate) > 1.0001) {
        \Log::critical('Conversion blocked: inverted/inconsistent exchange rate', [
            'pair' => $pair, 'rate' => $exchange->rate, 'reverse' => $__reverse->rate,
        ]);
        return ['status' => false, 'message' => "Currency conversion is temporarily unavailable. Please try again later."];
    }

    $convertedAmount = $amountToConvert * $exchange->rate;

    return [
        'status' => true,
        'message' => 'Conversion successful.',
        'data' => [
            'original_amount'  => $amount,
            'converted_amount' => round($convertedAmount, 2),
            'exchange_rate'    => $exchange->rate,
            'fee'   => $exchange->fee,
            'fee_amount' => round($feeAmount, 4),   // SOURCE currency, 4dp so sub-cent USD/EUR fees are not lost
            'fee_currency' => $fromCurrency,         // currency the fee is charged in
            'cap'   => $exchange->cap ?? 0,
            'from_currency'    => $fromCurrency,
            'to_currency'      => $toCurrency,
        ]
    ];
}

function getFirebaseAccessToken()
{
    
    try{
    $client = new \Google\Client();
    $client->setAuthConfig(storage_path('app/firebase/globesul-firebase-adminsdk-yavkc-293015857e.json'));
    $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithAssertion();
    }

    $accessToken = $client->getAccessToken();
    return $accessToken['access_token'] ?? null;
    } catch (\Exception $e) {
            \Log::error( $e->getMessage());
        }
}




function sendFcmNotification($deviceToken, $title, $body, $data = [])
{
    $projectId = 'dupay-app';
    $accessToken = getFirebaseAccessToken();

    if (!$accessToken) {
        return [
            'status' => false,
            'message' => 'Unable to retrieve Firebase access token.'
        ];
    }

    $payload = [
        'message' => [
            'token' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            
            'data' => is_array($data) ? array_map('strval', $data) : new \stdClass()
        ]
    ];

    $client = new \GuzzleHttp\Client();

    try {
        $response = $client->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        return [
            'status' => true,
            'response' => json_decode($response->getBody(), true),
        ];

    } catch (\GuzzleHttp\Exception\RequestException $e) {
           \Log::error('FCM send failed', ['status' => $e->getResponse()?->getStatusCode()]);
        return [
            'status' => false,
            'message' => $e->getMessage(),
            'response' => $e->getResponse() ? json_decode($e->getResponse()->getBody(), true) : null,
        ];
    }
}

function sendFcmToMultipleDevices(array $tokens, string $title, string $body, array $data = [])
{
    $projectId = "dupay-app";
    $accessToken = getFirebaseAccessToken();
    $client = new GuzzleClient();

    foreach ($tokens as $token) {
        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => array_map('strval', $data),
            ]
        ];

        try {
            $client->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
        } catch (\Exception $e) {
            \Log::error('FCM multi-device send failed');
        }
    }
}


class FirebaseUserUpdate{
    private $database;

    public function __construct()
    {
        $this->database = \App\Services\FirebaseService::connect();
    }

    public function updateUser($user)
    {
        try {
        $this->database->getReference(getSiteSettings('FIREBASE_APP_NAME').'/users/' . $user->profile->username)
            ->update([
                'balance/' => $user->balance ?? "0",
                'bonus/' => $user->bonus ?? "0",
                'commission/' => $user->profile->commission ?? "0",
                'stats/' => getUSerStats($user)
            ]);
        } catch (\Throwable $th) {
        //throw $th;
      }
    }

    public function addUserNotification($user, $data)
    {
         try {
        $this->database->getReference(getSiteSettings('FIREBASE_APP_NAME').'/notification/' . $user->profile->username)
            ->push($data);
    } catch (\Throwable $th) {
        //throw $th;
      }
    }
    }


function getSiteSettings($column_name)
{
    $basic = BasicSetting::whereColumn_name($column_name)->first();
    if ($basic)
        return $basic->value;
}

function addSiteActitvity($user, $ac){

    $ip = NULL; $deep_detect = TRUE;
        if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
            $ip = $_SERVER["REMOTE_ADDR"];
            if ($deep_detect) {
                if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
            }
        }

            //code...
        try {
            //code...

            $xml = simplexml_load_file("http://www.geoplugin.net/xml.gp?ip=".$ip);


        $country =  $xml->geoplugin_countryName ;
        $city = $xml->geoplugin_city;
        $area = $xml->geoplugin_areaCode;
        $code = $xml->geoplugin_countryCode;

        $user_agent     =   $_SERVER['HTTP_USER_AGENT'];
        $os_platform    =   "Unknown OS Platform";
        $os_array       =   array(
            '/windows nt 10/i'     =>  'Windows 10',
            '/windows nt 6.3/i'     =>  'Windows 8.1',
            '/windows nt 6.2/i'     =>  'Windows 8',
            '/windows nt 6.1/i'     =>  'Windows 7',
            '/windows nt 6.0/i'     =>  'Windows Vista',
            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'     =>  'Windows XP',
            '/windows xp/i'         =>  'Windows XP',
            '/windows nt 5.0/i'     =>  'Windows 2000',
            '/windows me/i'         =>  'Windows ME',
            '/win98/i'              =>  'Windows 98',
            '/win95/i'              =>  'Windows 95',
            '/win16/i'              =>  'Windows 3.11',
            '/macintosh|mac os x/i' =>  'Mac OS X',
            '/mac_powerpc/i'        =>  'Mac OS 9',
            '/linux/i'              =>  'Linux',
            '/ubuntu/i'             =>  'Ubuntu',
            '/iphone/i'             =>  'iPhone',
            '/ipod/i'               =>  'iPod',
            '/ipad/i'               =>  'iPad',
            '/android/i'            =>  'Android',
            '/blackberry/i'         =>  'BlackBerry',
            '/webos/i'              =>  'Mobile'
        );
        foreach ($os_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $os_platform    =   $value;
            }
        }
        $browser        =   "Unknown Browser";
        $browser_array  =   array(
            '/msie/i'       =>  'Internet Explorer',
            '/firefox/i'    =>  'Firefox',
            '/safari/i'     =>  'Safari',
            '/chrome/i'     =>  'Chrome',
            '/edge/i'       =>  'Edge',
            '/opera/i'      =>  'Opera',
            '/netscape/i'   =>  'Netscape',
            '/maxthon/i'    =>  'Maxthon',
            '/konqueror/i'  =>  'Konqueror',
            '/mobile/i'     =>  'Handheld Browser'
        );
        foreach ($browser_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $browser    =   $value;
            }
        }
        $ac = array_merge($ac, $ac);
        $ac['ip'] = $ip;
        $ac['location'] = $city.(" - $area - ").$country .(" - $code ");
        $ac['browser'] = $browser.' on '.$os_platform;
        $ac["user_id"] = $user->id;
        if($user->id == 1 && $ac["user_type"] == "2" ){
        }else{
            SiteActivity::create($ac);
        }
        //$user->activitySummary()->create($ac);
    } catch (\Throwable $th) {
            //throw $th;
        }

}


function formatFileSize($size = 0 ) {
    $size = $size *1024 *1024;
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
      $size /= 1024;
    }

    return number_format($size, 2) . ' ' . $units[$i];


  }



function getOtherFormInput($type, $name, $column_name, $value, $option, $required, $ajaxClass = null, $disabled = null, $icon = null, $class = null, $inputClass = null, $labelClass = null, $dataColumn=null, $multiple = null, $id = null)
{
    $data["type"] = $type;
    $data["name"] = $name;
    $data["column_name"] = $column_name;
    $data["id"] = $id;
    $data["value"] = $value;
    $data["option"] = $option;
    $data["dataColumn"] = $dataColumn;
    $data["required"] = $required;
    $data["ajaxClass"] = $ajaxClass;
    $data["disabled"] = $disabled;
    $data["icon"] = $icon;
    $data["class"] = $class;
    $data["inputClass"] = $inputClass;
    $data["labelClass"] = $labelClass;
    $data["multiple"] = $multiple;
    return view('others.otherform', $data);

}


function getTextFormInput($type, $name, $column_name, $value, $description, $required, $icon = null, $disabled = null, $class = null, $inputClass = null, $labelClass = null, $dataColumn=null, $addMore = null, $id =null, $wysiwyg= null)
{
    $data["name"] = $name;
    $data["column_name"] = $column_name;
    $data["value"] = $value;
    $data["type"] = $type;
    $data["description"] = $description;
    $data["dataColumn"] = $dataColumn;
    $data["required"] = $required;
    $data["icon"] = $icon;
    $data["disabled"] = $disabled;
    $data["class"] = $class;
    $data["inputClass"] = $inputClass;
    $data["labelClass"] = $labelClass;
    $data["addMore"] = $addMore;
    $data["id"] = $id;
    $data["wysiwyg"] = $wysiwyg;
    return view('others.inputform', $data);
}

function isMobile()
{
    $Agent = new Agent();
    if (getSiteSettings("enable_mobile") == "Yes" && $Agent->isMobile()) {
        return true;
    }
    return false;
}

function enableMobile()
{
    $Agent = new Agent();

    if(getSiteSettings("site_view_type") == "Responsive"){
    if ($Agent->isMobile()) {
        return "user.mobile.";
    } else {
        return "user.pc.";
    }
    }else if(getSiteSettings("site_view_type") == "Pc"){
        return "user.pc.";
    }else{
        return "user.mobile.";
    }

}



function getPrice($price)
{
    // Remove commas, symbols, and non-numeric characters
    $price = preg_replace('/[^0-9.\-]/', '', $price);

    return "₦" . number_format((float)$price, 2);
}

function getAvailableWallet($model)
{
    $service =  ServiceType::where("model", $model)->first();
    $option = [];
    if (!$service || $service->status != "1") {
        return $option;
    } else {

        if ($service->enable_main_wallet == "1") {
            array_push($option, [
                'name' => 'Main Wallet',
                'id' => '1',
            ]);
        }

        if ($service->enable_bonus_wallet == "1") {
            array_push($option, [
                'name' => 'Bonus Wallet',
                'id' => '2',
            ]);
        }
        if ($service->enable_commission_wallet == "1") {
            array_push($option, [
                'name' => 'Commission Wallet',
                'id' => '3',
            ]);
        }
    }
    return $option;
}


function checkWalletEnable($model, $wallet_id)
{
    switch ($wallet_id) {
        case '1':
            $wallet = "enable_main_wallet";
            break;
        case '2':
            $wallet = "enable_bonus_wallet";
            break;
        case '3':
            $wallet = "enable_commission_wallet";
            break;
        default:
        $wallet = "enable_main_wallet";
            break;
    }
    $service =  ServiceType::where("model", $model)->where($wallet, "1")->first();
    if ($service && $service->status == "1") {
        return true;
    }
    return false;
}


//check user wallet
function checkUserWallet($user, $amount, $select_wallet_id = "1")
{
    $user =  $user ?? Auth::user();
    $b = true;
    switch ($select_wallet_id) {
        case '1':
            // if($user->balance){
            //     return false;
            // }
            $b = ($user->balance - $user->level->wallet_minimum) >= $amount;
            $bb = $user->balance >= $amount;
            break;
        case '2':
            // if($user->bonus){
            //     return false;
            // }
            $bb = $user->bonus >= $amount;
            break;
        case '3':
            // if($user->profile->commission){
            //     return false;
            // }
            $bb = $user->profile->commission >= $amount;
            break;
        default:
            break;
    }

    if ($b == true && $bb == true) {
        return true;
    }
    return false;
}




/**
 * Should the blocked-account and daily-limit guards apply to this debit?
 *
 * Auto mode (the default) enforces only when the account being debited is the
 * one currently authenticated on the API guard — i.e. a user spending their own
 * money through the app. Everything else is a system movement and is left alone:
 *
 *   webhooks (StroWallet, SafeHaven, Fincra, Flutterwave) — no authenticated
 *       user; refusing these would desync us from the provider that has already
 *       moved the money.
 *   admin actions — authenticated on the admin guard, not the api guard.
 *   queued jobs and scheduled commands — no auth context at all.
 */
function userShouldEnforceSpendGuards($user, $enforceGuards = null): bool
{
    if ($enforceGuards !== null) {
        return (bool) $enforceGuards;
    }

    if (!$user) {
        return false;
    }

    try {
        $apiUser = Auth::guard('api')->user();
    } catch (\Throwable $e) {
        return false;
    }

    return $apiUser && (int) $apiUser->id === (int) $user->id;
}

/**
 * @param  mixed  $enforceGuards  null = decide automatically (enforce only when the
 *                                authenticated API user is spending their OWN money),
 *                                true  = always enforce, false = never enforce.
 *                                System debits — webhooks, admin actions, queued jobs,
 *                                refunds — must not be refused by a user-facing limit,
 *                                and are skipped automatically because no API user is
 *                                authenticated as the account being debited.
 */
function userBalanceAction($user, $amount, $select_wallet_id = "1", $sign = "+", $enforceGuards = null)
{
    // NOTE: This function is now RACE-SAFE and NEGATIVE-SAFE for every caller.
    // It takes a row lock on the user (and profile, for commission), re-reads the
    // real balance UNDER the lock, rejects insufficient debits, writes, and commits.
    // The caller's in-memory $user model is synced so a later $user->save() cannot
    // write a stale balance and re-open the double-spend race.
    // Return shape is unchanged: ["success","init_balance","final_balance"] (+ "reason").

    $Cuser = $user ?? Auth::user();
    if (!$Cuser) {
        return ["success" => false, "reason" => "user_not_found", "init_balance" => 0, "final_balance" => 0];
    }

    $amount = (float) $amount;

    // Never let a zero/negative magnitude move money.
    if ($amount <= 0) {
        return ["success" => false, "reason" => "invalid_amount", "init_balance" => 0, "final_balance" => 0];
    }

    $userId = $Cuser->id;

    return \DB::transaction(function () use ($Cuser, $userId, $amount, $select_wallet_id, $sign, $enforceGuards) {

        // Serialize all concurrent balance changes for this user on the user row.
        $locked = \App\Models\User::where('id', $userId)->lockForUpdate()->first();
        if (!$locked) {
            return ["success" => false, "reason" => "user_not_found", "init_balance" => 0, "final_balance" => 0];
        }

        // ── Blocked account + daily limit: the authoritative check ────────────
        // This runs INSIDE the transaction, after the row lock, immediately
        // before the balance moves. That placement is what makes both guards
        // unbypassable:
        //   * a route that forgets the middleware still cannot debit, because
        //     every wallet debit in the codebase comes through here;
        //   * two requests fired at the same instant cannot both pass, because
        //     the second blocks on the row lock until the first has committed
        //     its debit, and then re-reads the new daily total.
        // Checking in middleware alone cannot do either: it runs before the
        // controller, outside the transaction, on an unlocked row.
        if ($sign === "-" && userShouldEnforceSpendGuards($locked, $enforceGuards)) {

            if (\App\Services\UserBlockService::isBlocked($locked)) {
                return [
                    "success"       => false,
                    "reason"        => "account_blocked",
                    "message"       => \App\Services\UserBlockService::message(),
                    "init_balance"  => 0,
                    "final_balance" => 0,
                ];
            }

            // Bonus (2) and commission (3) wallets are internal reward balances,
            // not the naira spending wallet the limit is expressed in.
            if ((string) $select_wallet_id === "1") {
                $limitCheck = \App\Services\TransactionLimitService::check($locked, $amount, "NGN");
                if (empty($limitCheck["success"])) {
                    return [
                        "success"        => false,
                        "reason"         => "daily_limit_exceeded",
                        "message"        => $limitCheck["message"],
                        "daily_limit"    => $limitCheck["limit"] ?? null,
                        "spent_today"    => $limitCheck["spent"] ?? null,
                        "remaining_limit"=> $limitCheck["remaining"] ?? null,
                        "init_balance"   => 0,
                        "final_balance"  => 0,
                    ];
                }
            }
        }

        switch ($select_wallet_id) {

            case '3': // Commission - lives on the profiles table.
                $profile = \App\Models\Profile::where('user_id', $userId)->lockForUpdate()->first();
                if (!$profile) {
                    return ["success" => false, "reason" => "profile_not_found", "init_balance" => 0, "final_balance" => 0];
                }
                $init = (float) $profile->commission;
                if ($sign === "-") {
                    if ($init < $amount) {
                        return ["success" => false, "reason" => "insufficient_balance", "init_balance" => $init, "final_balance" => $init];
                    }
                    $final = $init - $amount;
                } else {
                    $final = $init + $amount;
                }
                $profile->commission = $final;
                $profile->save();
                if ($Cuser->relationLoaded('profile') && $Cuser->profile) {
                    $Cuser->profile->commission = $final;
                    $Cuser->profile->syncOriginalAttribute('commission');
                }
                break;

            case '2': // Bonus wallet (on users table).
                $init = (float) $locked->bonus;
                if ($sign === "-") {
                    if ($init < $amount) {
                        return ["success" => false, "reason" => "insufficient_balance", "init_balance" => $init, "final_balance" => $init];
                    }
                    $final = $init - $amount;
                } else {
                    $final = $init + $amount;
                }
                $locked->bonus = $final;
                $locked->save();
                $Cuser->bonus = $final;
                $Cuser->syncOriginalAttribute('bonus');
                break;

            case '1': // Main wallet (on users table).
            default:
                $init = (float) $locked->balance;
                if ($sign === "-") {
                    if ($init < $amount) {
                        // Authoritative insufficiency check, re-read under the lock.
                        return ["success" => false, "reason" => "insufficient_balance", "init_balance" => $init, "final_balance" => $init];
                    }
                    $final = $init - $amount;
                } else {
                    $final = $init + $amount;
                }
                $locked->balance = $final;
                $locked->save();
                // Keep the caller's model correct so a later $user->save() does not
                // clobber the debit with a stale in-memory balance.
                $Cuser->balance = $final;
                $Cuser->syncOriginalAttribute('balance');
                break;
        }

        return [
            "success"       => true,
            "reason"        => null,
            "init_balance"  => $init,
            "final_balance" => $final,
        ];
    });
}


/**
 * Atomic, race-safe balance mutation for the MAIN wallet (wallet id "1").
 *
 * WHY THIS EXISTS
 * ---------------
 * userBalanceAction() reads $user->balance into PHP, computes, then saves.
 * Two concurrent requests (e.g. two card authorizations for the same user
 * arriving together) both read the SAME starting balance and the second
 * save overwrites the first — a classic double-spend. A DB transaction
 * alone does NOT fix this; the two reads still see the same value.
 *
 * This helper takes a ROW LOCK on the user (SELECT ... FOR UPDATE) inside a
 * transaction, re-reads the balance under the lock, checks sufficiency for a
 * debit, writes, and commits. A second concurrent call blocks until the
 * first commits, then reads the already-updated balance — so it can no
 * longer overspend.
 *
 * @param  int    $userId  The user's id (we re-fetch under lock, we do NOT
 *                         trust a balance from an already-loaded model).
 * @param  float  $amount  Positive magnitude to move.
 * @param  string $sign    "-" to debit, "+" to credit.
 * @return array  ['success'=>bool, 'init_balance'=>float, 'final_balance'=>float, 'reason'=>?string]
 */
function lockedWalletAction($userId, $amount, $sign = "-")
{
    $amount = (float) $amount;

    // Guard: never allow a non-positive magnitude to reach the ledger.
    if ($amount <= 0) {
        return ['success' => false, 'reason' => 'invalid_amount', 'init_balance' => 0, 'final_balance' => 0];
    }

    return \DB::transaction(function () use ($userId, $amount, $sign) {

        // Row lock — concurrent calls for this user serialize here.
        $user = \App\Models\User::where('id', $userId)->lockForUpdate()->first();

        if (!$user) {
            return ['success' => false, 'reason' => 'user_not_found', 'init_balance' => 0, 'final_balance' => 0];
        }

        $init = (float) $user->balance;

        if ($sign === "-") {
            if ($init < $amount) {
                // Re-checked UNDER the lock — this is the authoritative check.
                return ['success' => false, 'reason' => 'insufficient_balance', 'init_balance' => $init, 'final_balance' => $init];
            }
            $final = $init - $amount;
        } else {
            $final = $init + $amount;
        }

        $user->balance = $final;
        $user->save();

        return ['success' => true, 'reason' => null, 'init_balance' => $init, 'final_balance' => $final];
    });
}



function subChargeFromWallet($user, $amount, $select_wallet_id = "1", $desc = null, $alert = true)
{
    $user = $user ?? Auth::user();
    $chargeRes =  userBalanceAction($user, $amount, $select_wallet_id, "-");
    $init_balance = $chargeRes["init_balance"];
    $final_balance = $chargeRes["final_balance"];
    $desc = getPrice($amount)." Charge .$desc";

        if($alert){
            $trx = strtoupper(Str::random(16));
            $chargeT['wallet_id'] = $select_wallet_id;
            $chargeT['user_level_id'] = $user->user_level_id;
            $chargeT['service'] = "System";
            $chargeT['service_type'] = "Service Charge";
            $chargeT['description'] = $desc;
            $chargeT['amount'] = $amount;
            $chargeT['charge'] = 0;
            $chargeT['phone_number'] = $user->phone_number;
            $chargeT['type'] = "0";
            $chargeT['initial_balance'] = $init_balance;
            $chargeT['final_balance'] = $final_balance;
            $chargeT['reference'] = $trx;
            //$chargeT['status_id'] = 5; //successful
            $chargeT['status'] = "success"; //successful
            $user->transaction()->create($chargeT);
    }

}


function calCharge($amount, $type = "0"){
    $allCharge = Charge::
    where("amount_from", "<=", (int) $amount)
    ->where("amount_to", ">",(int) $amount)
    ->where("type", $type)
    ->where("status", "1")
    ->first();
    if($allCharge){
        if ($allCharge->mode == "0") {
            $rate = $allCharge->fee/100 * $amount;
            $charge =  $rate;
        }else{
            $charge =  $allCharge->fee;
        }
        return $charge;
    }

    //check for default
    $allCharge = Charge::where("type", $type)->where("default", "1")->first();
    if($allCharge){
        if ($allCharge->mode == "0") {
            $rate = $allCharge->fee/100 * $amount;
            $charge =  $rate;
        }else{
            $charge = $allCharge->fee;
        }
        return $charge;
    }

    return 0;

}


function getUSerStats($user){

    $stats =[
    "daily_deposit"  => number_format(calTotalFunding($user, '1', '1', true)),
    "daily_spent"  => number_format(calTotalTransaction($user, '1', '0', true)),
    "daily_transaction" => number_format(calTotalTransaction($user, '1', '0', true, 'count')),
     "all_deposit"  => number_format(calTotalFunding($user, '1', '1', false)),
    "all_spent" => number_format(calTotalTransaction($user, '1', '0', false)),
    "all_transaction" => number_format(calTotalTransaction($user, '1', '0', false, 'count'))
    ];
    return $stats;
}


function calTotalFunding($user, $wallet_id = "1", $today = false, $type = "1"){
    $funding = Transaction::query()->where("status", "success");
    $payment = Payment::query()->where("status", "success");
    if($user){
        $funding->where("user_id", $user->id);
        $payment->where("user_id", $user->id);
    }

    if($type != "2"){
        $funding->where("type", $type);
    }

    if($today){
        $funding->whereDate('created_at', Carbon::today());
        $payment->whereDate('created_at', Carbon::today());
    }

    if($wallet_id != "1"){
        $totalFunding = $funding->where("wallet_id", $wallet_id)->sum("amount");
    }else{
        $totalFunding = $payment->where("wallet_id", $wallet_id)->sum("amount");
    }
    return $totalFunding;
}


function calTotalTransaction($user, $wallet_id = "1", $type = "1", $today = false, $rType = "amount"){
    //$user = $user ?? Auth::user();
    $totalFunding = 0;
    $trans = Transaction::query()->where("status", "success");

    if($user){
        $trans->where("user_id", $user->id);
    }

    if($wallet_id != "0"){
        $trans->where("wallet_id", $wallet_id);
    }

    if($type != "2"){
        $trans->where("type", $type);
    }

    if($today){
        $trans->whereDate('created_at', Carbon::today());
    }

    if($rType == "amount"){
        $totalFunding =  $trans->sum("amount");
    }else{
        $totalFunding =   $trans->count();
    }
    return $totalFunding;
}


function putHttp($payload, $endpoint, $header)
{
   

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

$response = curl_exec($ch);

if ($response === false) {
  return "Error: " . curl_error($ch);
}

curl_close($ch);
$json = json_decode($response);
return $json;

}


function postHttp($payload, $endpoint, $header)
{
   /* $jsonData = json_encode($payload);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $jsonData,
        CURLOPT_HTTPHEADER => $header,
    ));
    $response = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $err = curl_error($curl);
    curl_close($curl);
    $json = json_decode($response);
    return $json;*/

    $ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if ($response === false) {
  return "Error: " . curl_error($ch);
}

curl_close($ch);
$json = json_decode($response);
return $json;

}

function postHttpParameter($endpoint, $params, $header)
{
    $params = http_build_query($params);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    $response = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($response);
    return $json;
}


function getHttp2($payload, $endpoint, $header)
{
    //$jsonData = json_encode($payload);
    $jsonData = http_build_query($payload);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => $jsonData,
        CURLOPT_HTTPHEADER => $header,
    ));
    $response = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $err = curl_error($curl);
    curl_close($curl);
    $json = json_decode($response);
    return $json;
}



// GET API
function getHttp($endpoint, $header)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        $header
    );
    $response = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($response);
    return $json;
}

function getHeaderApiConnection($apiConnection)
{
    $header = array();
    $token = "";
    $apiToken = $apiConnection->token;
    $apiTokenField = $apiConnection->token_field;

    if ($apiConnection->api_auth_method == "0" && $apiConnection->auth_api_connection_id != null) {
        $apiToken = headerTokenReAuth($apiConnection->authApiConnection);
    }

    //check to encrypt token
    if ($apiConnection->encrypt_token == "1") {
        $token = base64_encode($apiToken);
    } else {
        $token = $apiToken;
    }

    if ($apiConnection->header_type == "0" && $apiConnection->header != null) {
        //replace header
        $head = explode(",", $apiConnection->header);
        if (is_array($head)) {
            foreach ($head as $value) {
                $value = str_replace("{{token}}", $token, $value);
                $header[] = trim("$value");
            }
        }
    } else {
        //add to header
        if ($apiConnection->header != null) {
            $head = explode(",", $apiConnection->header);
            if (is_array($head)) {
                foreach ($head as $value) {
                    $value = str_replace("{{token}}", $token, $value);
                    $header[] = trim("$value");
                }
            }
        }
        if ($token != null && $token != "") {
            $header[] = "Content-Type: application/json";
            $header[] =  "Authorization: {$apiTokenField} {$token}";
        }
    }

    return [
        "header" => $header,
        "token" => $token
    ];
}

function headerTokenReAuth($apiConnection)
{
    $reAuthToken = null;
    $json = null;
    $apiSuccessField = $apiConnection->success_field;
    $endpoint = $apiConnection->url;

    //header
    $Myheader = getHeaderApiConnection($apiConnection);
    $header = $Myheader["header"];
    $token = $Myheader["token"];

    try {

        if ($apiConnection->method == "0") {
            $endpoint = str_replace("{{token}}", $token, $endpoint);
            $json = getHttp($endpoint, $header);
        } else {
            //APi Connection POST Method
            //payload
            $apiPayLoad = json_decode($apiConnection->payload, true);
            $payload = array();
            if ($apiPayLoad != null &&  is_array($apiPayLoad)) {
                foreach ($apiPayLoad as $key => $value) {
                    $value = str_replace("{{token}}", $token, $value);
                    $payload[$key] = $value;
                }
            }
            $json = postHttp($payload, $endpoint, $header);
        }

        if ($json != null && $json->$apiSuccessField != null) {
            $reAuthToken = $json->$apiSuccessField;
        }
    } catch (\Throwable $th) {
        //throw $th;
    }





    return $reAuthToken;
}


//twillio otp
 function sendTwiloOTP($phone){
    try {

$accountSid = 'AC2f3314123db404070867e6ac131cfe29';
$authToken = '3246b9a63809fd290df4f4b2cc86103e';
$serviceSid = 'VA0e0511dedc69eac19ae1a967539e7cf5';
$to = $phone;
$channel = 'sms';

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://verify.twilio.com/v2/Services/$serviceSid/Verifications");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('To' => $to, 'Channel' => $channel)));
curl_setopt($ch, CURLOPT_USERPWD, "$accountSid:$authToken");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
if (curl_errno($ch)) {
echo 'Curl error: ' . curl_error($ch);
}

curl_close($ch);

// Process the $response here as needed.
//echo $response;
$responseJson = json_decode($response, true);

return $responseJson['status'];

} catch (\Throwable $th) {
//throw $th;
}
 }


function verifyTwiloOTP($phone, $otp){
    try {

$accountSid = 'AC2f3314123db404070867e6ac131cfe29';
$authToken = '3246b9a63809fd290df4f4b2cc86103e';
$serviceSid = 'VA0e0511dedc69eac19ae1a967539e7cf5';
$code = $otp;
$to = $phone;

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://verify.twilio.com/v2/Services/$serviceSid/VerificationCheck");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('Code' => $code, 'To' => $to)));
curl_setopt($ch, CURLOPT_USERPWD, "$accountSid:$authToken");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
if (curl_errno($ch)) {
echo 'Curl error: ' . curl_error($ch);
}

curl_close($ch);

// Process the $response here as needed.
$responseJson = json_decode($response, true);

return $responseJson['status'];

} catch (\Throwable $th) {
//throw $th;
}

}

/**
 * Detect whether a VTpass API response indicates the transaction is still
 * processing (pending / initiated), as opposed to a hard failure.
 *
 * VTpass commonly returns a transaction as pending FIRST and then settles it
 * to "delivered" a short time later. Treating that pending state as a failure
 * (and refunding) is wrong — the purchase may still complete. This helper lets
 * the controllers keep such transactions in "pending" instead of refunding.
 *
 * Recognised pending signals (any one is enough):
 *   - response code "099" (TRANSACTION IS PROCESSING) or "089"
 *   - content->transactions->status is "pending" or "initiated"
 *   - a response_description mentioning "pending" or "processing"
 *
 * @param  mixed $json  The decoded VTpass response (stdClass) or null.
 * @return bool         true if the txn looks pending/processing.
 */
function isVtpassPending($json)
{
    if ($json === null || is_string($json)) {
        // null = no/garbled response; a string here means a curl error string.
        // Either way, treat as "not confirmed failed" -> caller keeps it pending.
        return true;
    }

    try {
        // 1. Top-level VTpass response code.
        $code = null;
        if (isset($json->code)) {
            $code = (string) $json->code;
        }
        if (in_array($code, ['099', '089'], true)) {
            return true;
        }

        // 2. The authoritative transaction status, when present.
        if (isset($json->content)
            && isset($json->content->transactions)
            && isset($json->content->transactions->status)) {
            $txnStatus = strtolower((string) $json->content->transactions->status);
            if (in_array($txnStatus, ['pending', 'initiated', 'processing'], true)) {
                return true;
            }
            // An explicit final-failure status is NOT pending.
            if (in_array($txnStatus, ['failed', 'reversed', 'reversal'], true)) {
                return false;
            }
        }

        // 3. Fallback: scan the human-readable description.
        $desc = null;
        if (isset($json->response_description) && is_string($json->response_description)) {
            $desc = strtolower($json->response_description);
        }
        if ($desc !== null) {
            if (strpos($desc, 'pending') !== false || strpos($desc, 'processing') !== false) {
                return true;
            }
        }
    } catch (\Throwable $th) {
        // If we can't parse it confidently, err on the side of NOT refunding.
        return true;
    }

    return false;
}
