<?php

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Controller;
use App\Models\PhysicalCard;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\CardMaintenanceLog;

class CardMaintenanceController extends Controller
{
    public function chargeAtmCardMaintenance()
    {
        if (getSiteSettings('enable_atm_card_maintenance') != 1) {
            // Log::info('ATM Card Maintenance is disabled in settings.');
            return response()->json(['status' => false, 'message' => 'ATM maintenance is disabled']);
        }

        $fee = (float)getSiteSettings('atm_card_maintenance_fee', 0);
        if ($fee <= 0) {
            Log::warning('ATM Card Maintenance fee is not set or is invalid.');
            return response()->json(['status' => false, 'message' => 'Invalid maintenance fee']);
        }

        $cards = PhysicalCard::where('status', 'active')
            ->where('atm', true)
            ->with('user')
            ->get();

        $charged = 0;

        foreach ($cards as $card) {
            $user = $card->user;
        
            if (!$user) {
                Log::info("Skipping card ID {$card->id} - User not found.");
                continue;
            }
        
            $alreadyCharged = $user->transaction()
                ->where('service', 'ATM Card Maintenance')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->exists();
        
            if ($alreadyCharged) {
                // Log::info("User ID {$user->id} already charged this month.");
                continue;
            }
        
            $init_balance = $user->balance;
            $fee = (float)getSiteSettings('atm_card_maintenance_fee', 0);
        
            if ($init_balance < $fee) {
                Log::info("Skipping user ID {$user->id} - Insufficient balance for card maintenance. Balance: ₦{$init_balance}, Fee: ₦{$fee}");
                continue;
            }
        
            DB::beginTransaction();
            try {
                $final_balance = $init_balance - $fee;
                $reference = 'ATM-MF-' . strtoupper(uniqid());
        
                $user->balance = $final_balance;
                $user->save();
        
                $user->transaction()->create([
                    'wallet_id'        => 1,
                    'user_level_id'    => $user->user_level_id,
                    'service'          => 'ATM Card Maintenance',
                    'service_type'     => 'Card Maintenance Fee',
                    'description'      => 'Monthly ATM Card Maintenance Fee',
                    'amount'           => $fee,
                    'real_price'       => $fee,
                    'charge'           => 0,
                    'phone_number'     => null,
                    'type'             => '0',
                    'initial_balance'  => $init_balance,
                    'final_balance'    => $final_balance,
                    'reference'        => $reference,
                    'status'           => 'success',
                ]);
                
                CardMaintenanceLog::create([
                    'user_id'   => $user->id,
                    'card_id'   => $card->id,
                    'reference' => $reference,
                    'amount'    => $fee,
                    'status'    => 'success',
                ]);
        
                DB::commit();
                $charged++;
        
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to charge user ID {$user->id}: " . $e->getMessage());
            }
        }

        return response()->json([
            'status'  => true,
            'message' => "ATM Maintenance charged for {$charged} users."
        ]);
    }
}
