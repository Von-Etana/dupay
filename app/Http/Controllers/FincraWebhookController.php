<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TraitsFolder\MailTrait;
use App\Models\ForeignAccount;
use App\Models\ForeignAccountRequest;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class FincraWebhookController extends Controller
{
    use MailTrait;
    
    public function handle(Request $request)
{
    if (!validateFincraWebhook($request)) {
        Log::warning('Fincra Webhook: Invalid signature');
        return response()->json(['message' => 'Invalid signature'], 403);
    }

    $payload = $request->all();
    $event   = $payload['event'] ?? null;
    $data    = $payload['data'] ?? [];

    if (in_array($event, ['virtualaccount.approved', 'virtualaccount.changed'])) {

        $email = $data['email'] ?? ($data['accountInformation']['email'] ?? null);

        if (!$email) {
            Log::error("Fincra Webhook: No email found in payload");
            return response()->json(['message' => 'Email not provided'], 422);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            Log::error("Fincra Webhook: User not found", ['email' => $email, 'id' => $data['id']]);
            return response()->json(['message' => 'User not found'], 404);
        }

        $account = ForeignAccount::updateOrCreate(
            [
                'user_id'        => $user->id,
                'account_number' => $data['accountInformation']['accountNumber'],
            ],
            [
                "bank_name"    => $data['accountInformation']['bankName'],
                "bank_code"    => $data['accountInformation']['bankCode'] ?? null,
                "country_code" => $data['accountInformation']['countryCode'] ?? null,
                "email"        => $email,
                "other_info"   => json_encode($data['accountInformation']['otherInfo'] ?? []),
                "payload"      => json_encode($data),
            ]
        );

        // Only send email notification when account is approved
        if ($data['status'] === 'approved' && getSiteSettings("email_notify") == "Yes") {
            $message = "Dear {$user->profile->username},\n\n"
                . "We are pleased to inform you that your foreign account has been successfully created/updated. 🎉\n\n"
                . "Account Details:\n"
                . "Account Number: {$account->account_number}\n"
                . "Bank Name: {$account->bank_name}\n"
                . "Currency: {$data['currency']}\n\n"
                . "You can now start receiving payments directly into this account. "
                . "Please keep your account details safe and secure.\n\n"
                . "Thank you for choosing " . config('app.name') . ".";

            $this->sendMail(
                $user->email,
                $user->profile->first_name ?? "",
                "Your Foreign Account Has Been Successfully Created",
                $message
            );
        }

        Log::info("Fincra Webhook: Virtual Account {$event}", ['user_id' => $user->id, 'status' => $data['status']]);
        return response()->json(['message' => 'Virtual account processed successfully'], 200);

    } elseif ($event === 'virtualaccount.declined') {

        $email = $data['email'] ?? null;

        if (!$email) {
            Log::error("Fincra Webhook Declined: No email found");
            return response()->json(['message' => 'Email not provided'], 422);
        }

        $user = User::where('email', $email)->first();

        if ($user && getSiteSettings("email_notify") == "Yes") {
            $reason = $data['reason'] ?? "No reason provided";

            $message = "Dear {$user->profile->first_name},\n\n"
                . "We regret to inform you that your foreign account request has been declined.\n\n"
                . "Reason: {$reason}\n\n"
                . "If you believe this is a mistake or you’d like further clarification, please contact our support team.\n\n"
                . "Thank you for your understanding.\n\n"
                . "Regards,\n" . config('app.name');

            $this->sendMail(
                $user->email,
                $user->profile->first_name ?? "",
                "Your Foreign Account Request Was Declined",
                $message
            );
        }

        Log::warning("Fincra Webhook: Virtual Account Declined", ['email' => $email, 'reason' => $data['reason'] ?? null]);
        return response()->json(['message' => 'Declined event processed'], 200);
    }

    Log::notice("Fincra Webhook: Unsupported event", ['event' => $event]);
    return response()->json(['message' => 'Event ignored'], 200);
}


}
