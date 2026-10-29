<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\UserController;
use App\Http\Middleware\DeduplicateMoneyRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MoneyRequestControlsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Schema::create('money_request_keys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->char('endpoint_hash', 64);
            $table->char('key_hash', 64);
            $table->char('payload_hash', 64);
            $table->timestamp('created_at');
            $table->unique(['user_id', 'endpoint_hash', 'key_hash']);
        });
    }

    private function request(string $body, ?string $key = null, int $userId = 7, string $path = '/api/v1/fund/transfer'): Request
    {
        $headers = ['CONTENT_TYPE' => 'application/json'];
        if ($key !== null) {
            $headers['HTTP_IDEMPOTENCY_KEY'] = $key;
        }
        $request = Request::create($path, 'POST', [], [], [], $headers, $body);
        $request->setUserResolver(fn () => new class($userId) {
            public function __construct(private int $id) {}
            public function getAuthIdentifier(): int { return $this->id; }
        });
        return $request;
    }

    public function test_repeated_key_is_rejected_before_money_moves(): void
    {
        $middleware = new DeduplicateMoneyRequest();
        $calls = 0;
        $next = function () use (&$calls) { $calls++; return response()->json(['success' => true]); };
        $key = 'transfer-request-123456';

        $this->assertSame(200, $middleware->handle($this->request('{"amount":"100"}', $key), $next)->status());
        $this->assertSame(409, $middleware->handle($this->request('{"amount":"100"}', $key), $next)->status());
        $this->assertSame(409, $middleware->handle($this->request('{"amount":"200"}', $key), $next)->status());
        $this->assertSame(1, $calls);
        $this->assertSame(1, DB::table('money_request_keys')->count());
    }

    public function test_legacy_duplicate_and_invalid_key_are_rejected(): void
    {
        $middleware = new DeduplicateMoneyRequest();
        $calls = 0;
        $next = function () use (&$calls) { $calls++; return response()->json(['success' => true]); };

        $this->assertSame(200, $middleware->handle($this->request('{"amount":"100"}'), $next)->status());
        $this->assertSame(409, $middleware->handle($this->request('{"amount":"100"}'), $next)->status());
        $this->assertSame(400, $middleware->handle($this->request('{"amount":"100"}', 'bad'), $next)->status());
        $this->assertSame(1, $calls);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $token = new class {
            public bool $revoked = false;
            public function revoke(): bool { $this->revoked = true; return true; }
        };
        $user = new class($token) {
            public function __construct(private $token) {}
            public function token() { return $this->token; }
        };
        $request = Request::create('/api/v1/logout', 'POST');
        $request->setUserResolver(fn () => $user);

        $response = (new UserController())->logoutCurrentToken($request);

        $this->assertTrue($token->revoked);
        $this->assertSame(200, $response->status());
    }

    public function test_bank_session_id_is_durable_even_without_header(): void
    {
        $middleware = new DeduplicateMoneyRequest();
        $next = fn () => response()->json(['success' => true]);
        $request = $this->request('{"amount":"100","session_id":"bank-session-42"}', null, 7, '/api/v1/fund/withdraw');

        $this->assertSame(200, $middleware->handle($request, $next)->status());
        $this->assertSame(409, $middleware->handle($request, $next)->status());
    }
}
