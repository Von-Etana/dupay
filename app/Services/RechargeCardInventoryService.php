<?php

namespace App\Services;

use App\Models\RechargeCardPin;
use Illuminate\Support\Facades\Hash;

class RechargeCardInventoryService
{
    public static function available(int $networkId, $value): int
    {
        return RechargeCardPin::where('network_id', $networkId)
            ->where('value', $value)->where('status', 'available')->count();
    }

    public static function reserve(int $networkId, $value, int $quantity, int $userId, string $reference): array
    {
        $rows = RechargeCardPin::where('network_id', $networkId)
            ->where('value', $value)->where('status', 'available')
            ->lockForUpdate()->limit($quantity)->get();

        if ($rows->count() < $quantity) return [];
        $cards = [];
        foreach ($rows as $row) {
            $cards[] = ['pin' => decrypt($row->pin), 'serial' => $row->serial];
            $row->update(['status' => 'sold', 'sold_to' => $userId, 'sold_reference' => $reference, 'sold_at' => now()]);
        }
        return $cards;
    }
}
