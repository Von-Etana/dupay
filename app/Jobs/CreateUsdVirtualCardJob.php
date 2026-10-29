<?php

namespace App\Jobs;

use App\Models\VirtualCardUSD;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\TraitsFolder\MailTrait;
use Illuminate\Support\Facades\Log;

/**
 * Creates a USD virtual card with StroWallet, off the HTTP request cycle.
 *
 * WHY THIS EXISTS
 * StroWallet's bitvcard/create-card is slow/asynchronous and sometimes returns
 * nothing for 60s+ (cURL 28). Blocking the user's request on it timed out the
 * app AND, because the old code rolled back the DB transaction + refunded on
 * timeout, any card StroWallet *did* create was orphaned. This job moves the
 * slow call into the background:
 *
 *   store() creates a `pending` VirtualCardUSD row + charges the user, commits,
 *   then dispatches this job. The job talks to StroWallet with a long timeout:
 *     - success            -> fill in card_id + details, status 'active'
 *     - definite decline   -> status 'failed' + refund (if enabled)
 *     - timeout/no response -> leave 'pending', DO NOT refund/retry
 *                             (the card may exist; the create.success/ .failed
 *                              webhook or a manual reconcile finalises it)
 *
 * tries=1 on purpose: create-card is NOT idempotent, so we never auto-retry.
 */
class CreateUsdVirtualCardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, MailTrait;

    /** Do not auto-retry — a retry could create a duplicate card. */
    public $tries = 1;

    /** Longer than the 180s HTTP timeout inside createUsdCard(). */
    public $timeout = 220;

    public $cardRowId;
    public $chargedAmount;
    public $walletId;
    public $loadAmount;

    public function __construct($cardRowId, $chargedAmount, $walletId, $loadAmount)
    {
        $this->cardRowId     = $cardRowId;
        $this->chargedAmount = $chargedAmount;
        $this->walletId      = $walletId;
        $this->loadAmount    = $loadAmount;
    }

    public function handle()
    {
        $card = VirtualCardUSD::find($this->cardRowId);

        if (!$card) {
            Log::channel('strowallet')->warning('CreateUsdVirtualCardJob: pending card row missing', [
                'card_row_id' => $this->cardRowId,
            ]);
            return;
        }

        // Idempotency: only act on a row still awaiting creation.
        if ($card->status !== 'pending' || !empty($card->card_id)) {
            return;
        }

        $user = $card->user;
        if (!$user) {
            Log::channel('strowallet')->warning('CreateUsdVirtualCardJob: user missing for card row', [
                'card_row_id' => $this->cardRowId,
            ]);
            return;
        }

        // ── Create the card via the NEW self-contained NFC endpoint ─────────
        // One call, no create-user step (that's the old flow that hung). All
        // KYC fields + amount go in this single request.
        $wallet = $user->walletVerification;
        if (!$wallet) {
            $this->declineAndRefund(
                $card,
                $user,
                ['message' => 'KYC verification record missing.'],
                'Your USD card could not be set up because your verification details are missing.'
            );
            return;
        }

        $first = $user->profile->first_name ?? '';
        $last  = $user->profile->last_name ?? '';

        $data = [
            'public_key'  => env('STROWALLET_PUBLIC_KEY'),
            'brand'       => 'Visa',
            'name'        => trim($first . ' ' . $last),
            'first_name'  => $first,
            'last_name'   => $last,
            // NFC wants mm/dd/yyyy.
            'dob'         => \Carbon\Carbon::parse($wallet->date_of_birth)->format('m/d/Y'),
            // USD cards require NIN (enforced in store()); the number lives in
            // the `bvn` column for NIN-verified users.
            'id_type'     => 'national_id',
            'id_number'   => $wallet->bvn,
            'id_image'    => asset('storage/' . $wallet->id_image),
            'email'       => $user->email,
            // international format, no '+'.
            'phone'       => '234' . ltrim($user->phone_number, '0'),
            'line1'       => $wallet->address,
            'city'        => $wallet->city,
            'state'       => $wallet->state,
            'postal_code' => $wallet->zip_code ?: '100001',
            'country'     => 'NGA',
            'amount_usd'  => (string) $this->loadAmount,
        ];

        $res = createNfcCard($data);

        // ── Success ────────────────────────────────────────────────────────
        if (!empty($res['status'])) {
            $resp = $res['data'] ?? [];

            $card->update([
                'card_id'       => $resp['card_id']    ?? $card->card_id,
                'card_brand'    => $resp['card_brand'] ?? $card->card_brand,
                'reference'     => $resp['reference']  ?? $card->reference,
                'response_data' => json_encode($resp),
                'status'        => 'active',
            ]);

            $this->notify(
                $user,
                'Virtual card created successfully',
                'Your USD virtual card has been created. You can now find the card details in your account.'
            );
            return;
        }

        // ── Inconclusive (timeout / no response) ───────────────────────────
        // The card may have been created on StroWallet's side. Never refund or
        // retry here — leave the row pending so the create.success / .failed
        // webhook (or a manual reconcile) can finalise it.
        if (!empty($res['timeout'])) {
            Log::channel('strowallet')->warning('USD card creation inconclusive (timeout) — left pending', [
                'card_row_id' => $card->id,
                'user_id'     => $user->id,
                'errors'      => $res['errors'] ?? null,
            ]);

            $this->notify(
                $user,
                'Your virtual card is still being created',
                'Your USD virtual card is taking a little longer than usual. We will notify you as soon as it is ready — no need to try again.'
            );
            return;
        }

        // ── Definite decline ───────────────────────────────────────────────
        $this->declineAndRefund($card, $user, $res['errors'] ?? $res);
    }

    /**
     * Mark the pending card failed, refund the charge (if refunds are enabled)
     * and notify the user.
     */
    private function declineAndRefund($card, $user, $errors, ?string $userMessage = null): void
    {
        $card->update([
            'status'        => 'failed',
            'response_data' => json_encode($errors),
        ]);

        if (getSiteSettings('enable_virtual_card_creation_refund') === 'Yes') {
            $refund = userBalanceAction($user, $this->chargedAmount, $this->walletId, '+');

            if (!empty($refund['success'])) {
                $user->transaction()->create([
                    'user_level_id'   => $user->user_level_id,
                    'wallet_id'       => $this->walletId,
                    'service'         => 'System',
                    'service_type'    => 'Virtual Card',
                    'description'     => 'USD Virtual Card Creation Refund',
                    'amount'          => $this->chargedAmount,
                    'phone_number'    => $user->phone_number,
                    'type'            => '1', // Credit
                    'initial_balance' => $refund['init_balance'],
                    'final_balance'   => $refund['final_balance'],
                    'reference'       => strtoupper(\Illuminate\Support\Str::random(16)),
                    'status'          => 'refund',
                ]);

                try {
                    (new \FirebaseUserUpdate)->updateUser($user);
                } catch (\Throwable $e) {
                    // Non-critical.
                }
            }
        }

        $this->notify(
            $user,
            'Virtual card creation failed',
            $userMessage ?? 'We were unable to create your USD virtual card and any charge has been reversed. Please try again later.'
        );
    }

    private function notify($user, string $subject, string $body): void
    {
        try {
            $this->sendMail($user->email, $user->profile->username ?? '', $subject, $body);
        } catch (\Throwable $e) {
            // Never let a mail failure fail the job.
        }
    }

    /**
     * If the job itself blows up/exhausts, do NOT refund automatically — the
     * card may have been created. Just record it for reconciliation.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('strowallet')->error('CreateUsdVirtualCardJob failed', [
            'card_row_id' => $this->cardRowId,
            'error'       => $exception->getMessage(),
        ]);
    }
}