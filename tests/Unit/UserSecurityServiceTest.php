<?php

namespace Tests\Unit;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\UserSecurityService;
use App\Services\LoginChallengeService;
use App\Models\LoginChallenge;
use Illuminate\Support\Facades\Hash;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

class UserSecurityServiceTest extends TestCase
{
    private $db;
    private $service;
    private $user;
    private $request;

    protected function setUp(): void
    {
        $container = new Container;
        $this->db = new Manager($container);
        $this->db->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->db->bootEloquent();
        $container->instance('db', $this->db->getDatabaseManager());
        $container->bind('db.schema', fn () => $this->db->getConnection()->getSchemaBuilder());
        $container->instance('hash', new BcryptHasher(['rounds' => 4]));
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($container);
        (require __DIR__.'/../../database/migrations/2014_10_12_000000_create_users_table.php')->up();
        (require __DIR__.'/../../database/migrations/2026_11_01_000001_add_user_security.php')->up();
        (require __DIR__.'/../../database/migrations/2026_11_01_000002_create_login_challenges.php')->up();
        $this->user = User::create([
            'email' => 'test@example.com', 'phone_number' => '123456789',
            'password' => $container['hash']->make('correct-password'), 's_password' => '',
        ]);
        $this->service = new UserSecurityService;
        $this->request = Request::create('/login', 'POST', ['password' => 'secret'], [], [], [
            'REMOTE_ADDR' => '127.0.0.1', 'HTTP_USER_AGENT' => 'Security test',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        $this->db->getDatabaseManager()->disconnect();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        User::unsetConnectionResolver();
    }

    public function test_fifth_failure_locks_even_correct_password_until_expiry(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-01 12:00:00'));
        for ($i = 1; $i <= 5; $i++) {
            $this->assertFalse($this->service->verifyPassword($this->user, 'wrong', $this->request));
            $this->assertSame($i, $this->user->fresh()->password_failed_attempts);
            $this->assertSame($i === 5, $this->user->fresh()->password_locked_until !== null);
        }
        $this->assertFalse($this->service->verifyPassword($this->user, 'correct-password', $this->request));
        $this->assertSame(5, $this->user->fresh()->password_failed_attempts);
        $this->assertSame(1, SecurityEvent::where('event', 'password_locked')->count());
        Carbon::setTestNow(now()->addMinutes(15));
        $this->assertTrue($this->service->verifyPassword($this->user, 'correct-password', $this->request));
        $this->assertSame(0, $this->user->fresh()->password_failed_attempts);
        $this->assertNull($this->user->fresh()->password_locked_until);
    }

    public function test_success_resets_consecutive_failures_and_events_exclude_secrets(): void
    {
        $this->service->verifyPassword($this->user, 'wrong', $this->request);
        $this->assertTrue($this->service->verifyPassword($this->user, 'correct-password', $this->request));
        $this->service->verifyPassword($this->user, 'wrong', $this->request);
        $this->assertSame(1, $this->user->fresh()->password_failed_attempts);
        $event = SecurityEvent::first();
        $this->assertSame('127.0.0.1', $event->ip_address);
        $this->assertStringNotContainsString('secret', $event->toJson());
    }

    public function test_expired_lockout_starts_new_failure_count(): void
    {
        $this->user->forceFill(['password_failed_attempts' => 5, 'password_locked_until' => now()->subMinute()])->save();
        $this->service->verifyPassword($this->user, 'wrong', $this->request);
        $this->assertSame(1, $this->user->fresh()->password_failed_attempts);
        $this->assertNull($this->user->fresh()->password_locked_until);
    }

    public function test_migration_can_be_rolled_back(): void
    {
        (require __DIR__.'/../../database/migrations/2026_11_01_000002_create_login_challenges.php')->down();
        (require __DIR__.'/../../database/migrations/2026_11_01_000001_add_user_security.php')->down();
        $this->assertFalse($this->db->getConnection()->getSchemaBuilder()->hasTable('security_events'));
        $this->assertFalse($this->db->getConnection()->getSchemaBuilder()->hasTable('trusted_devices'));
        $this->assertFalse($this->db->getConnection()->getSchemaBuilder()->hasColumn('users', 'password_locked_until'));
    }

    public function test_valid_password_creates_challenge_without_a_token(): void
    {
        $this->request->merge(['device_id' => 'known-device-123456789']);
        $result = (new LoginChallengeService($this->service))->begin($this->user, 'correct-password', $this->request);
        $this->assertFalse($result['success']);
        $this->assertSame('otp_required', $result['code']);
        $this->assertNotEmpty($result['challenge_id']);
        $this->assertSame(1, LoginChallenge::count());
        $this->assertFalse($this->db->getConnection()->getSchemaBuilder()->hasTable('oauth_access_tokens'));
    }

    public function test_six_invalid_otp_attempts_lock_for_thirty_minutes(): void
    {
        $this->request->merge(['device_id' => 'new-device-123456789']);
        $service = new LoginChallengeService($this->service);
        $challenge = $service->begin($this->user, 'correct-password', $this->request, 'web');
        LoginChallenge::find(hash('sha256', $challenge['challenge_id']))->forceFill(['otp_hash' => Hash::make('123456'), 'otp_expires_at' => now()->addMinutes(10), 'sent_at' => now()])->save();
        for ($i = 1; $i <= 6; $i++) {
            $result = $service->verify(Request::create('/login/otp/verify', 'POST', ['challenge_id' => $challenge['challenge_id'], 'device_id' => 'new-device-123456789', 'otp' => '000000']), 'web');
            $this->assertSame($i === 6 ? 'account_locked' : 'invalid_otp', $result['code']);
            $this->assertSame($i, $this->user->fresh()->otp_failed_attempts);
        }
        $this->assertTrue($this->user->fresh()->otp_locked_until->between(now()->addMinutes(29), now()->addMinutes(31)));
    }

    public function test_successful_web_otp_consumes_challenge_and_registers_device(): void
    {
        $this->request->merge(['device_id' => 'verified-device-123456']);
        $service = new LoginChallengeService($this->service);
        $challenge = $service->begin($this->user, 'correct-password', $this->request, 'web');
        LoginChallenge::find(hash('sha256', $challenge['challenge_id']))->forceFill(['otp_hash' => Hash::make('123456'), 'otp_expires_at' => now()->addMinutes(10), 'sent_at' => now()])->save();
        $result = $service->verify(Request::create('/login/otp/verify', 'POST', ['challenge_id' => $challenge['challenge_id'], 'device_id' => 'verified-device-123456', 'otp' => '123456']), 'web');
        $this->assertTrue($result['success']);
        $this->assertSame(1, $this->db->getConnection()->table('trusted_devices')->count());
        $this->assertNotNull(LoginChallenge::first()->consumed_at);
    }
}
