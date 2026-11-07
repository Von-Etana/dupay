<?php

namespace Illuminate\Support {
    class Str {
        public static function uuid() { return 'test-uuid-' . bin2hex(random_bytes(8)); }
    }
}

namespace Illuminate\Contracts\Validation {
    interface Rule {
        public function passes($attribute, $value);
        public function message();
    }
}

namespace Illuminate\Support\Facades {
    class Auth {
        public static $user = null;
        public static function user() { return self::$user; }
        public static function logout() { self::$user = null; }
        public static function id() { return self::$user ? self::$user->id : null; }
        public static function guard($name = null) {
            return new class {
                public function user() { return Auth::$user; }
                public function id() { return Auth::$user ? Auth::$user->id : 1; }
            };
        }
    }
    class Hash {
        public static function check($value, $hashed) {
            return $hashed === 'hashed:' . $value;
        }
        public static function make($value) {
            return 'hashed:' . $value;
        }
    }
    class Cache {
        public static $store = [];
        public static function add($k, $v, $ttl = null) {
            if (isset(self::$store[$k])) return false;
            self::$store[$k] = $v;
            return true;
        }
    }
    class Log {
        public static $context = [];
        public static $logs = [];
        public static function info($m, $c = []) { self::$logs[] = ['info', $m, $c]; }
        public static function warning($m, $c = []) { self::$logs[] = ['warning', $m, $c]; }
        public static function error($m, $c = []) { self::$logs[] = ['error', $m, $c]; }
        public static function withContext($c = []) { self::$context = array_merge(self::$context, $c); }
    }
    class Mail {
        public static $sent = [];
        public static function raw($text, $cb) { self::$sent[] = $text; }
    }
    class Schema {
        public static function hasTable($t) { return true; }
        public static function hasColumn($t, $c) { return true; }
    }
}

namespace App\Models {
    class User {
        public $id = 1;
        public $account_activation = "1";
        public $email_verify = "1";
        public $phone_verify = "1";
        public $ban_status = "0";
        public $blocked_status = "0";
        public $transaction_pin = null;
        public $latestSecurityEvent = null;

        public static $mockUser = null;
        public static function find($id) {
            return self::$mockUser;
        }
        public static function findOrFail($id) {
            return self::$mockUser;
        }
        public function getKey() { return $this->id; }
        public function transaction() {
            return new class {
                public function where() { return $this; }
                public function count() { return 0; }
            };
        }
    }

    class SecurityAlert {
        public static $alerts = [];
        public $id = 100;
        public $type, $severity, $title, $message, $admin_id, $ip_address, $metadata, $created_at;

        public static function create($data) {
            $a = new self();
            foreach ($data as $k => $v) { $a->$k = $v; }
            $a->created_at = date('Y-m-d H:i:s');
            self::$alerts[] = $a;
            return $a;
        }
    }

    class SecurityEvent {
        public static $events = [];
        public static function create($data) {
            self::$events[] = $data;
            return new self();
        }
    }
}

namespace App\Services {
    class UserBlockService {
        public static function isBlocked($user) {
            return $user->blocked_status === "1" || $user->ban_status === "1";
        }
    }
}

namespace {
    $GLOBALS['SETTINGS'] = [
        'auto_account_activation' => 'Yes',
        'email_verify' => 'Yes',
        'phone_verify' => 'No',
        'user_reg' => 'Yes',
    ];

    if (!function_exists('now')) {
        function now() {
            return new class {
                public function addMinutes($m) { return $this; }
                public function subMinutes($m) { return $this; }
                public function subHours($h) { return $this; }
            };
        }
    }

    if (!function_exists('getSiteSettings')) {
        function getSiteSettings($key) {
            return $GLOBALS['SETTINGS'][$key] ?? null;
        }
    }

    if (!function_exists('config')) {
        function config($key, $default = null) {
            if ($key === 'disposable_email.domains') {
                $data = require __DIR__ . '/../../config/disposable_email.php';
                return $data['domains'] ?? [];
            }
            if ($key === 'security.alert_email') return 'admin@test.com';
            if ($key === 'mail.from.address') return 'system@test.com';
            return $default;
        }
    }

    if (!function_exists('request')) {
        function request() {
            return $GLOBALS['CURRENT_REQUEST'] ?? new FakeRequest();
        }
    }

    if (!function_exists('response')) {
        function response($data, $status = 200) {
            return new class($data, $status) {
                public $data;
                public $status;
                public function __construct($d, $s) { $this->data = $d; $this->status = $s; }
                public function getData() { return $this->data; }
                public function header($k, $v) {}
            };
        }
    }

    if (!function_exists('redirect')) {
        function redirect() {
            return new class {
                public function route($r) { return "redirect:$r"; }
            };
        }
    }

    if (!function_exists('session')) {
        function session() {
            return new class {
                public function flash($k, $v) {}
            };
        }
    }

    class FakeAttributes {
        private $attrs = [];
        public function set($k, $v) { $this->attrs[$k] = $v; }
        public function get($k, $d = null) { return $this->attrs[$k] ?? $d; }
        public function has($k) { return isset($this->attrs[$k]); }
    }

    class FakeRequest {
        public $isApi = true;
        public $params = [];
        public $headersList = [];
        public $attributes;

        public function __construct($params = [], $isApi = true, $headers = []) {
            $this->params = $params;
            $this->isApi = $isApi;
            $this->headersList = array_change_key_case($headers, CASE_LOWER);
            $this->attributes = new FakeAttributes();
        }
        public function expectsJson() { return $this->isApi; }
        public function is($pattern = null) { return $this->isApi; }
        public function ip() { return '192.168.1.100'; }
        public function userAgent() { return 'FakeBrowser/1.0'; }
        public function input($key = null, $default = null) { return $this->params[$key] ?? $default; }
        public function filled($key) { return !empty($this->params[$key]); }
        public function header($key = null, $default = null) { return $this->headersList[strtolower($key)] ?? $default; }
        public function __get($key) { return $this->params[$key] ?? null; }
    }

    require_once __DIR__ . '/../../app/Rules/NotDisposableEmail.php';
    require_once __DIR__ . '/../../app/Http/Middleware/VerifyUser.php';
    require_once __DIR__ . '/../../app/Http/Middleware/CorrelationIdMiddleware.php';
    require_once __DIR__ . '/../../app/Services/SecurityAlertService.php';

    $pass = 0; $fail = 0;
    function ok($name, $cond) {
        global $pass, $fail;
        if ($cond) { $pass++; echo "  PASS  $name\n"; }
        else { $fail++; echo "  FAIL  $name\n"; }
    }

    echo "\n=== 1. Testing Disposable Email Blocker ===\n";
    $rule = new \App\Rules\NotDisposableEmail();
    ok("Legitimate domain passes (gmail.com)", $rule->passes('email', 'john.doe@gmail.com') === true);
    ok("Legitimate corporate domain passes (company.co.uk)", $rule->passes('email', 'alice@company.co.uk') === true);
    ok("Disposable domain blocked (mailinator.com)", $rule->passes('email', 'spammer@mailinator.com') === false);
    ok("Disposable domain blocked (tempmail.com)", $rule->passes('email', 'bot123@tempmail.com') === false);
    ok("Disposable domain blocked (guerrillamail.com)", $rule->passes('email', 'test@guerrillamail.com') === false);
    ok("Disposable subdomain blocked (xyz.mailinator.com)", $rule->passes('email', 'test@sub.mailinator.com') === false);
    ok("Invalid email format rejected", $rule->passes('email', 'invalid-email-string') === false);

    echo "\n=== 2. Testing Universal Email Verification Middleware ===\n";
    $middleware = new \App\Http\Middleware\VerifyUser();
    $next = function($req) { return 'NEXT_CALLED'; };

    $u = new \App\Models\User();
    $u->account_activation = "1";
    $u->email_verify = "0";
    \App\Models\User::$mockUser = $u;
    \Illuminate\Support\Facades\Auth::$user = $u;

    $req = new FakeRequest();
    $GLOBALS['CURRENT_REQUEST'] = $req;
    $res = $middleware->handle($req, $next);
    ok("Unverified email blocked universally", $res !== 'NEXT_CALLED' && $res->data['code'] === 'email_unverified');

    // Historical bypass condition:
    $u->account_activation = "0";
    $GLOBALS['SETTINGS']['auto_account_activation'] = 'Yes';
    $res = $middleware->handle($req, $next);
    ok("Historical bypass closed (unverified email blocked even with account_activation=0 and auto_activation=Yes)", 
       $res !== 'NEXT_CALLED' && $res->data['code'] === 'email_unverified');

    // Verified user passes:
    $u->email_verify = "1";
    $u->account_activation = "1";
    $res = $middleware->handle($req, $next);
    ok("Verified user passes through", $res === 'NEXT_CALLED');

    echo "\n=== 3. Testing Server Log Correlation Middleware ===\n";
    $corrMiddleware = new \App\Http\Middleware\CorrelationIdMiddleware();
    $fakeReq = new FakeRequest([], true, ['x-request-id' => 'client-req-uuid-1234']);
    $GLOBALS['CURRENT_REQUEST'] = $fakeReq;
    $corrMiddleware->handle($fakeReq, function($r) {
        ok("Correlation ID preserved from header", $r->attributes->get('request_id') === 'client-req-uuid-1234');
        ok("Correlation ID bound to log context", \Illuminate\Support\Facades\Log::$context['request_id'] === 'client-req-uuid-1234');
        return response(['status' => 'ok']);
    });

    $fakeReqNoHeader = new FakeRequest();
    $GLOBALS['CURRENT_REQUEST'] = $fakeReqNoHeader;
    $corrMiddleware->handle($fakeReqNoHeader, function($r) {
        $genId = $r->attributes->get('request_id');
        ok("Correlation ID generated when missing in header", !empty($genId) && strlen($genId) >= 16);
        return response(['status' => 'ok']);
    });

    echo "\n=== 4. Testing Security Alert Service with Correlation ===\n";
    \App\Models\SecurityAlert::$alerts = [];
    $alert = \App\Services\SecurityAlertService::record(
        'unusual_transfer',
        'high',
        'Large Outflow',
        'Transfer of ₦1,000,000',
        null,
        '192.168.1.100',
        ['amount' => 1000000]
    );
    ok("Security alert recorded", $alert !== null && $alert->type === 'unusual_transfer');
    ok("Correlation ID attached to alert metadata", isset($alert->metadata['request_id']));

    echo "\n------------------------------------------------------------\n";
    echo "  $pass passed, $fail failed\n\n";

    if ($fail > 0) exit(1);
}
