<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
class LoginChallengeFlowTest extends TestCase {
 use RefreshDatabase;
 public function test_valid_password_requires_otp_and_returns_no_token(): void { Mail::fake(); $u=User::factory()->create(['email'=>'otp@example.com','password'=>Hash::make('secret')]); $r=$this->postJson('/api/v1/login',['email'=>$u->email,'password'=>'secret','device_id'=>'device-a']); $r->assertOk()->assertJsonPath('code','otp_required')->assertJsonMissingPath('access_token'); $this->assertDatabaseCount('login_challenges',1); Mail::assertSent(function($mail){return str_contains($mail->subject,'login code');}); }
 public function test_expired_challenge_is_rejected(): void { $this->withoutExceptionHandling(); $u=User::factory()->create(); $c=\App\Models\LoginChallenge::create(['user_id'=>$u->id,'otp_hash'=>Hash::make('123456'),'expires_at'=>now()->subMinute(),'sent_at'=>now()->subMinutes(11)]); $this->postJson('/api/v1/login/otp/verify',['challenge_id'=>$c->id,'otp'=>'123456'])->assertStatus(422)->assertJsonPath('code','otp_expired'); }
 public function test_three_failures_lock_otp_and_success_issues_token(): void { Mail::fake(); $u=User::factory()->create(['email'=>'otp2@example.com','password'=>Hash::make('secret')]); $login=$this->postJson('/api/v1/login',['email'=>$u->email,'password'=>'secret','device_id'=>'device-b'])->json(); for($i=0;$i<2;$i++)$this->postJson('/api/v1/login/otp/verify',['challenge_id'=>$login['challenge_id'],'otp'=>'000000'])->assertStatus(422); $this->postJson('/api/v1/login/otp/verify',['challenge_id'=>$login['challenge_id'],'otp'=>'000000'])->assertStatus(423)->assertJsonPath('code','otp_retry_limit'); $this->assertNotNull($u->fresh()->otp_locked_until); }
}
