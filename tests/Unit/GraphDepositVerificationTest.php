<?php

namespace Tests\Unit;

use App\Services\GraphDepositVerification;
use PHPUnit\Framework\TestCase;

class GraphDepositVerificationTest extends TestCase
{
    private function event(): array
    {
        return ['deposit_id' => 'deposit-123', 'account_id' => 'account-123',
            'currency' => 'USD', 'amount' => 14000];
    }

    private function response(): array
    {
        return ['status' => true, 'data' => ['id' => 'deposit-123',
            'account_id' => 'account-123', 'currency' => 'USD',
            'amount' => 14000, 'amount_settled' => 13900, 'status' => 'successful']];
    }

    public function test_credits_only_the_confirmed_settled_minor_units(): void
    {
        $this->assertSame(13900, GraphDepositVerification::settledMinorUnits($this->event(), $this->response()));
    }

    public function test_rejects_mismatched_or_unsettled_deposits(): void
    {
        $response = $this->response();
        $response['data']['account_id'] = 'another-account';
        $this->assertNull(GraphDepositVerification::settledMinorUnits($this->event(), $response));

        $response = $this->response();
        $response['data']['status'] = 'pending';
        $this->assertNull(GraphDepositVerification::settledMinorUnits($this->event(), $response));

        $response = $this->response();
        $response['data']['amount_settled'] = 15000;
        $this->assertNull(GraphDepositVerification::settledMinorUnits($this->event(), $response));
    }
}
