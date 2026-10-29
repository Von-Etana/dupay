<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserSerializationSecurityTest extends TestCase
{
    public function test_user_json_exposes_pin_presence_without_secrets(): void
    {
        $user = new User([
            'transaction_pin' => 'hashed-pin-value',
            'email_code' => '123456',
            'phone_code' => '1234',
            'fcmToken' => 'device-token',
        ]);
        $user->setRelation('walletVerification', null);

        $serialized = $user->toArray();

        $this->assertTrue($serialized['has_transaction_pin']);
        $this->assertArrayNotHasKey('transaction_pin', $serialized);
        $this->assertArrayNotHasKey('email_code', $serialized);
        $this->assertArrayNotHasKey('phone_code', $serialized);
        $this->assertArrayNotHasKey('fcmToken', $serialized);
    }
}
