<?php

namespace App\Services;

final class GraphDepositVerification
{
    public static function settledMinorUnits(array $event, array $providerResponse): ?int
    {
        $deposit = $providerResponse['data'] ?? null;
        if (empty($providerResponse['status']) || !is_array($deposit)
            || ($deposit['status'] ?? null) !== 'successful'
            || ($deposit['id'] ?? null) !== ($event['deposit_id'] ?? null)
            || ($deposit['account_id'] ?? null) !== ($event['account_id'] ?? null)
            || strtoupper((string) ($deposit['currency'] ?? '')) !== strtoupper((string) ($event['currency'] ?? ''))) {
            return null;
        }

        $gross = filter_var($deposit['amount'] ?? null, FILTER_VALIDATE_INT);
        $eventGross = filter_var($event['amount'] ?? null, FILTER_VALIDATE_INT);
        $settled = filter_var($deposit['amount_settled'] ?? null, FILTER_VALIDATE_INT);
        if ($gross === false || $eventGross === false || $settled === false
            || $gross <= 0 || $eventGross !== $gross || $settled <= 0 || $settled > $gross) {
            return null;
        }

        return $settled;
    }
}
