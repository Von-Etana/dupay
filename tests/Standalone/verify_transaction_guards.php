<?php
// Standalone harness: runs the real TransactionLimitService / UserBlockService
// against stubbed Laravel plumbing so the arithmetic and the bypass cases can be
// verified without a database.

namespace Carbon { class Carbon { public static function today(){ return '2026-09-16'; } } }

namespace Illuminate\Support\Facades {
    class Log { public static function error($m){ echo "  [log] $m\n"; } }
}

namespace {
    $GLOBALS['SITE_LIMIT_ENABLED'] = 'Yes';
    function getSiteSettings($k){ return $k === 'limit_daily_transaction' ? $GLOBALS['SITE_LIMIT_ENABLED'] : null; }
    function optional($v){ return $v ?? new class { public function __get($n){ return null; } }; }

    class FakeQuery {
        public $rows;
        public function __construct($rows){ $this->rows = $rows; }
        public function where($col, $val){
            $this->rows = array_filter($this->rows, fn($r) => (string)($r[$col] ?? '') === (string)$val);
            return $this;
        }
        public function whereNotIn($col, $vals){
            $this->rows = array_filter($this->rows, fn($r) => !in_array($r[$col] ?? '', $vals, true));
            return $this;
        }
        public function whereDate($col, $date){
            $this->rows = array_filter($this->rows, fn($r) => ($r['date'] ?? '') === $date);
            return $this;
        }
        public function sum($col){ return array_sum(array_map(fn($r) => (float)($r[$col] ?? 0), $this->rows)); }
    }

    class FakeLevel {
        public $daily_transaction_limit, $verified_daily_transaction_limit;
        public function __construct($l = '-', $v = '-'){ $this->daily_transaction_limit = $l; $this->verified_daily_transaction_limit = $v; }
    }

    class FakeVerification { public $status; public function __construct($s){ $this->status = $s; } }

    class FakeUser {
        public $id = 1, $daily_transaction_limit = null, $verified_daily_transaction_limit = null;
        public $level, $walletVerification = null, $blocked_status = '0', $ban_status = '0';
        public $rows = [];
        public function __construct(){ $this->level = new FakeLevel(); }
        public function transaction(){ return new FakeQuery($this->rows); }
    }

    require __DIR__ . '/../../app/Services/TransactionLimitService.php';
    require __DIR__ . '/../../app/Services/UserBlockService.php';

    use App\Services\TransactionLimitService as L;
    use App\Services\UserBlockService as B;

    $pass = 0; $fail = 0;
    function ok($name, $cond) {
        global $pass, $fail;
        if ($cond) { $pass++; echo "  PASS  $name\n"; }
        else { $fail++; echo "  FAIL  $name\n"; }
    }

    $today = '2026-09-16';
    $debit = fn($amt, $extra = []) => array_merge(
        ['type' => '0', 'currency' => 'NGN', 'status' => 'success', 'date' => $today, 'amount' => $amt], $extra);

    echo "\n--- The reported bypass: one huge transaction from a clean slate ---\n";
    $u = new FakeUser(); $u->level = new FakeLevel('50000');
    $r = L::check($u, 10000000);
    ok('₦10,000,000 refused against a ₦50,000 limit with ₦0 spent', $r['success'] === false);

    echo "\n--- Boundary arithmetic ---\n";
    $u = new FakeUser(); $u->level = new FakeLevel('50000');
    $u->rows = [$debit(49000)];
    ok('₦1,000 allowed at ₦49,000 spent (total exactly at limit)', L::check($u, 1000)['success'] === true);
    ok('₦1,001 refused at ₦49,000 spent (one naira over)',        L::check($u, 1001)['success'] === false);
    ok('remaining reported as ₦1,000',                             L::check($u, 1001)['remaining'] == 1000.0);

    echo "\n--- Splitting into many small transactions ---\n";
    $u = new FakeUser(); $u->level = new FakeLevel('50000');
    $u->rows = array_map(fn($i) => $debit(10000), range(1, 5));
    ok('6th x ₦10,000 refused once 5 have used the full limit', L::check($u, 10000)['success'] === false);

    echo "\n--- Every channel counts, not just withdrawals and airtime ---\n";
    $u = new FakeUser(); $u->level = new FakeLevel('50000');
    $u->rows = [
        $debit(20000, ['service_type' => 'Fund Transfer']),
        $debit(15000, ['service_type' => 'Bill Payment']),
        $debit(10000, ['service_type' => 'Data Topup']),
    ];
    ok('P2P + bill + data all counted (₦45,000 spent)', L::spentToday($u) == 45000.0);
    ok('₦6,000 refused on top of them',                 L::check($u, 6000)['success'] === false);

    echo "\n--- Rows that must NOT count ---\n";
    $u = new FakeUser(); $u->level = new FakeLevel('50000');
    $u->rows = [
        $debit(40000, ['status' => 'refund']),
        $debit(40000, ['status' => 'cancel']),
        $debit(40000, ['status' => 'unsuccessful']),
        $debit(40000, ['currency' => 'USD']),
        $debit(40000, ['type' => '1']),
        $debit(40000, ['date' => '2026-09-15']),
    ];
    ok('refunded / cancelled / failed / USD / credits / yesterday all excluded', L::spentToday($u) == 0.0);
    ok('full limit still available',                                            L::check($u, 50000)['success'] === true);

    echo "\n--- Verified users get the higher tier ---\n";
    $u = new FakeUser(); $u->level = new FakeLevel('50000', '500000');
    $u->walletVerification = new FakeVerification('1');
    ok('verified: ₦400,000 allowed on the ₦500,000 tier', L::check($u, 400000)['success'] === true);
    ok('verified: ₦600,000 still refused (not unlimited)', L::check($u, 600000)['success'] === false);
    $u->walletVerification = new FakeVerification('0');
    ok('unverified on same level: ₦400,000 refused',       L::check($u, 400000)['success'] === false);

    echo "\n--- Limit resolution order ---\n";
    $u = new FakeUser(); $u->level = new FakeLevel('50000'); $u->daily_transaction_limit = '200000';
    ok('per-user limit overrides the level', L::limitFor($u) == 200000.0);
    $u->daily_transaction_limit = 0;
    ok('per-user 0 falls back to level (not a zero limit)', L::limitFor($u) == 50000.0);
    $u->daily_transaction_limit = null;
    ok('per-user null falls back to level',                 L::limitFor($u) == 50000.0);
    $u->level = new FakeLevel('-');
    ok('level "-" means unlimited',                         L::limitFor($u) === null);
    ok('unlimited allows any amount',                       L::check($u, 999999999)['success'] === true);

    echo "\n--- Master switch ---\n";
    $GLOBALS['SITE_LIMIT_ENABLED'] = 'No';
    $u = new FakeUser(); $u->level = new FakeLevel('50000');
    ok('site setting off disables the limit', L::check($u, 10000000)['success'] === true);
    $GLOBALS['SITE_LIMIT_ENABLED'] = 'Yes';

    echo "\n--- Blocked accounts ---\n";
    $u = new FakeUser();
    ok('normal account not blocked',        B::isBlocked($u) === false);
    $u->blocked_status = '1';
    ok('blocked_status = 1 is blocked',     B::isBlocked($u) === true);
    $u->blocked_status = '0'; $u->ban_status = '1';
    ok('ban_status = 1 is blocked',         B::isBlocked($u) === true);
    $u->ban_status = '0';
    ok('string "0" is not blocked',         B::isBlocked($u) === false);
    $u->blocked_status = null; $u->ban_status = null;
    ok('null flags are not blocked',        B::isBlocked($u) === false);

    echo "\n" . str_repeat('-', 60) . "\n  $pass passed, $fail failed\n\n";
    exit($fail > 0 ? 1 : 0);
}
