<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Fintech\WalletVerificaiton;
use Illuminate\Http\Request;
use App\TraitsFolder\MailTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use File;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use iSmartFlutterWave;
use iSmartMonnify;

class KYCVerificationController extends Controller
{

    use MailTrait;

    /**
     * Where wallet KYC images live. MUST match AuthController::register(),
     * which stores registration KYC under uploads/kyc/* on the public disk.
     * They were out of sync before: register wrote uploads/kyc/id_images/x.jpg
     * while a re-submission wrote id_images/x.jpg, so the admin preview broke
     * on every resubmitted record.
     */
    private const ID_IMAGE_DIR   = 'uploads/kyc/id_images';
    private const USER_PHOTO_DIR = 'uploads/kyc/user_photos';

    public function __construct()
    {
        $this->middleware('verifyUser');
        $this->middleware('auth:api');
    }

    /**
     *
     * verify Bank Details
     *
     */
    public function verifyBankDetails(Request $request)
    {
        if (getSiteSettings("enable_withdrawal") != "Yes") {
            return response()->json([
                'success' => false,
                'message' => 'Oops!! Sorry Withdrawal is disabled at the moment try again later',
            ]);
        }

        $data = $request->all();
        $validatedData = Validator::make($data, [
            'account_number' => 'required',
            'bank' => 'required',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }

        return safeHavenNameEnquiry($request->bank, $request->account_number);
    }

    /*****
     *
     *  WALLET KYC (BVN / NIN)
     *
     */

    public function walletKYCDetails()
    {
        $user = Auth::user();

        if ($user->walletVerification == null) {
            return response()->json([
                'success' => true,
                'message' => 'You are yet to submit verification details',
                'kyc_status' => 'none',
                'status_label' => 'Not Submitted',
                'can_resubmit' => true,
                'comment' => null,
                'data' => [],
                'user' => $user
            ]);
        }

        // KYC status: 0 = Pending, 1 = Approved (Verified), 2 = Rejected.
        $status = (int) $user->walletVerification->status;
        $labels = [0 => 'Pending', 1 => 'Approved', 2 => 'Rejected'];

        return response()->json([
            'success' => true,
            'kyc_status' => (string) $status,
            'status_label' => $labels[$status] ?? 'Unknown',
            // Only a REJECTED record may be sent again. The app should use this
            // flag to decide whether to show the "Resubmit KYC" button.
            'can_resubmit' => $status === 2,
            // The admin's rejection reason, so the user knows what to fix.
            'comment' => $status === 2 ? $user->walletVerification->comment : null,
            'data' => $user->walletVerification,
            'user' => $user,
        ]);
    }

    public function verifyUserWalletUsingMonnifyBVN(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'bvn' => 'required|digits:11',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'other_names' => 'nullable|string',
            'method' => 'nullable|string|in:NIN,BVN',
            'gender' => 'nullable|string|in:Male,Female',
            'date_of_birth' => 'required|date',
            'mobile_no' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zip_code' => 'required|string|max:10',
            'house_number' => 'nullable|string|max:50',
            'issued_country' => 'nullable|string|max:100',
            'issued_date' => 'nullable|date',
            'expiration_date' => 'nullable|date',
            'id_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'user_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validatedData->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(' ', $validatedData->errors()->all()),
            ]);
        }

        $user = Auth::user();
        $existing = $user->walletVerification;

        /**
         * ──────────────────────────────────────────────────────────────
         * GUARD 1 — THIS USER'S OWN RECORD
         * ──────────────────────────────────────────────────────────────
         * 0 = Pending  -> locked, already in the admin queue
         * 1 = Approved -> locked, nothing to do
         * 2 = Rejected -> ALLOWED through, this is the resubmission case
         *
         * This used to be handled by the BVN lookup below, which did not
         * exclude the current user and did not distinguish "approved for
         * someone else" from "my own rejected record". That is why a
         * rejected user got "KYC Already Verified" and could never get a
         * new submission back in front of the admin.
         */
        if ($existing != null) {
            $currentStatus = (int) $existing->status;

            if ($currentStatus === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your KYC is pending review. You cannot submit again until it has been reviewed.',
                    'kyc_status' => '0',
                    'status_label' => 'Pending',
                    'can_resubmit' => false,
                    'data' => $existing,
                    'user' => $user,
                ]);
            }

            if ($currentStatus === 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your KYC has already been approved.',
                    'kyc_status' => '1',
                    'status_label' => 'Approved',
                    'can_resubmit' => false,
                    'data' => $existing,
                    'user' => $user,
                ]);
            }
            // status 2 (rejected) falls through — resubmission allowed.
        }

        /**
         * ──────────────────────────────────────────────────────────────
         * GUARD 2 — BVN ALREADY APPROVED FOR A DIFFERENT USER
         * ──────────────────────────────────────────────────────────────
         * Note the user_id exclusion. Without it, any approved record
         * carrying the same BVN — including the applicant's own, and
         * including test BVNs such as 11111111111 reused across staging
         * accounts — blocked the submission outright.
         */
        $bvnTakenByAnotherUser = WalletVerificaiton::where('bvn', $request->bvn)
            ->where('status', 1)
            ->where('user_id', '!=', $user->id)
            ->exists();

        if ($bvnTakenByAnotherUser) {
            return response()->json([
                'success' => false,
                'message' => 'This BVN is already linked to a verified account.',
            ]);
        }

        $charge = getSiteSettings("wallet_verification_charge");

        $idImagePath = $request->hasFile('id_image')
            ? $request->file('id_image')->store(self::ID_IMAGE_DIR, 'public')
            : null;

        $userPhotoPath = $request->hasFile('user_photo')
            ? $request->file('user_photo')->store(self::USER_PHOTO_DIR, 'public')
            : null;

        // Remove the superseded files so rejected uploads don't pile up on disk.
        $this->deleteOldKycImages($existing, $idImagePath, $userPhotoPath);

        /**
         * ──────────────────────────────
         * MANUAL VERIFICATION PATH
         * ──────────────────────────────
         */
        if (getSiteSettings("auto_wallet_verification") !== "Yes") {

            if (getSiteSettings("charge_manual_wallet_verification") === "Yes") {
                if (!checkUserWallet($user, $charge)) {
                    return response()->json(['success' => false, 'message' => "Insufficient Funds"]);
                }

                $balanceAction = userBalanceAction($user, $charge, "1", "-");
                if (!$balanceAction["success"]) {
                    return response()->json(['success' => false, 'message' => "Wallet balance error"]);
                }

                $user->transaction()->create([
                    'wallet_id' => 1,
                    'user_level_id' => $user->user_level_id,
                    'service' => "Verification",
                    'service_type' => "Wallet KYC",
                    'description' => "Wallet KYC verification charge",
                    'amount' => $charge,
                    'phone_number' => $request->mobile_no,
                    'type' => "0",
                    'initial_balance' => $balanceAction["init_balance"],
                    'final_balance' => $balanceAction["final_balance"],
                    'reference' => strtoupper(Str::random(16)),
                    'status' => "success"
                ]);
            }

            $user->walletVerification()->updateOrCreate(
                ['user_id' => $user->id],
                $this->submissionPayload($request, $idImagePath, $userPhotoPath, [
                    'method' => $request->method ?? 'NIN',
                    'status' => '0',
                ])
            );

            $user->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Verification Information Submitted Successfully (Pending Review)',
                'kyc_status' => '0',
                'status_label' => 'Pending',
                'can_resubmit' => false,
                'data' => $user->walletVerification,
                'user' => $user,
            ]);
        }

        /**
         * ──────────────────────────────
         * NIN VERIFICATION PATH (auto)
         * ──────────────────────────────
         */
        if ($request->method === "NIN") {

            $user->walletVerification()->updateOrCreate(
                ['user_id' => $user->id],
                $this->submissionPayload($request, $idImagePath, $userPhotoPath, [
                    'method' => 'NIN',
                    'status' => '1',
                ])
            );

            (new iSmartMonnify())->updateVirtualAccount($user->id);
            $user->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Information Verified Successfully via NIN',
                'kyc_status' => '1',
                'status_label' => 'Approved',
                'can_resubmit' => false,
                'data' => $user->walletVerification,
                'user' => $user,
            ]);
        }

        /**
         * ──────────────────────────────
         * BVN VERIFICATION PATH (auto)
         * ──────────────────────────────
         */
        if (!checkUserWallet($user, $charge)) {
            return response()->json(['success' => false, 'message' => "Insufficient Funds"]);
        }

        $payload = [
            "bvn" => $request->bvn,
            "name" => "{$request->first_name} {$request->last_name} {$request->other_names}",
            "dateOfBirth" => $request->date_of_birth,
            "mobileNo" => $request->mobile_no,
        ];

        $res = (new iSmartMonnify())->verifyBVN($payload);

        if (!$res || !$res->requestSuccessful) {
            return response()->json([
                'success' => false,
                'message' => $res->responseMessage ?? 'Unable to process request, try again later',
            ]);
        }

        $matchType = getSiteSettings("wallet_verification_type");

        $nameStatus = $res->responseBody->name->matchStatus ?? 'NO_MATCH';
        $dobStatus = $res->responseBody->dateOfBirth ?? 'NO_MATCH';
        $phoneStatus = $res->responseBody->mobileNo ?? 'NO_MATCH';

        $noMatch = ($matchType === "ALL" && ($nameStatus === "NO_MATCH" || $dobStatus === "NO_MATCH" || $phoneStatus === "NO_MATCH")) ||
                   ($matchType === "PHONE" && $phoneStatus === "NO_MATCH") ||
                   ($matchType === "DOB" && $dobStatus === "NO_MATCH") ||
                   ($matchType === "NAME" && $nameStatus === "NO_MATCH");

        if ($noMatch) {
            return response()->json([
                'success' => false,
                'message' => 'Verification data does not match BVN records',
            ]);
        }

        $balanceAction = userBalanceAction($user, $charge, "1", "-");
        if (!$balanceAction["success"]) {
            return response()->json(['success' => false, 'message' => "Wallet balance error"]);
        }

        $user->transaction()->create([
            'wallet_id' => 1,
            'user_level_id' => $user->user_level_id,
            'service' => "Verification",
            'service_type' => "Wallet KYC",
            'description' => "Wallet KYC verification charge",
            'amount' => $charge,
            'phone_number' => $request->mobile_no,
            'type' => "0",
            'initial_balance' => $balanceAction["init_balance"],
            'final_balance' => $balanceAction["final_balance"],
            'reference' => strtoupper(Str::random(16)),
            'status' => "success"
        ]);

        $user->walletVerification()->updateOrCreate(
            ['user_id' => $user->id],
            $this->submissionPayload($request, $idImagePath, $userPhotoPath, [
                'method' => 'BVN',
                'status' => '1',
            ])
        );

        (new iSmartMonnify())->updateVirtualAccount($user->id);
        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Information Verified Successfully via BVN',
            'kyc_status' => '1',
            'status_label' => 'Approved',
            'can_resubmit' => false,
            'data' => $user->walletVerification,
            'user' => $user,
        ]);
    }

    /**
     * One payload builder for all three paths.
     *
     * The important part is the tail: comment / reviewed_at / reviewed_by are
     * explicitly cleared. updateOrCreate only writes the keys it is handed, so
     * without this a resubmitted record kept the old rejection reason and the
     * old reviewer stamp, and the admin list still read as a reviewed record.
     */
    private function submissionPayload(Request $request, $idImagePath, $userPhotoPath, array $overrides = []): array
    {
        $payload = [
            'bvn' => $request->bvn,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'other_names' => $request->other_names,
            'date_of_birth' => $request->date_of_birth,
            'mobile_no' => $request->mobile_no,
            'gender' => $request->gender,
            'state' => $request->state,
            'city' => $request->city,
            'zip_code' => $request->zip_code,
            'address' => $request->address,
            'house_number' => $request->house_number,
            'issued_country' => $request->issued_country,
            'issued_date' => $request->issued_date,
            'expiration_date' => $request->expiration_date,
            'id_image' => $idImagePath,
            'user_photo' => $userPhotoPath,
            'submitted_at' => now(),
            'comment' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
        ];

        return array_merge($payload, $overrides);
    }

    /**
     * Delete the previous id_image / user_photo once a replacement has landed.
     */
    private function deleteOldKycImages($existing, $newIdImage, $newUserPhoto): void
    {
        if ($existing == null) {
            return;
        }

        $pairs = [
            [$existing->id_image, $newIdImage],
            [$existing->user_photo, $newUserPhoto],
        ];

        foreach ($pairs as [$old, $new]) {
            if ($old && $new && $old !== $new && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }
        }
    }

    /*****
     *
     * Profile KYC
     *
     */

    public function profileKYCDetails()
    {
        $user = Auth::user();

        if ($user->profileKYC == null) {
            return response()->json([
                'success' => true,
                'message' => 'You are yet to submit verification details',
                'kyc_status' => 'none',
                'status_label' => 'Not Submitted',
                'can_resubmit' => true,
                'user' => $user
            ]);
        }

        // KYC status: "0" = pending (under review), "1" = approved, "2" = rejected.
        $status = (string) $user->profileKYC->status;
        $labels = ['0' => 'Pending', '1' => 'Approved', '2' => 'Rejected'];

        return response()->json([
            'success' => true,
            'data' => $user->profileKYC,
            'kyc_status' => $status,
            'status_label' => $labels[$status] ?? 'Unknown',
            'can_resubmit' => $status === '2',
            'user' => $user,
        ]);
    }

    public function submitProfileKYC(Request $request)
    {
        $data = $request->all();
        $validatedData = Validator::make($data, [
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'image' => 'image',
            'id_card' => 'image',
            'cac' => 'image',
            'nepa_bill' => 'image',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }

        $user = Auth::user();

        // A user may only (re)submit when they have NO record yet, or when the
        // previous submission was REJECTED.
        if ($user->profileKYC != null) {
            $currentStatus = (string) $user->profileKYC->status;

            if ($currentStatus === "0") {
                return response()->json([
                    'success' => false,
                    'message' => 'Your KYC is pending review. You cannot submit again until it has been reviewed.',
                    'kyc_status' => '0',
                    'status_label' => 'Pending',
                    'can_resubmit' => false,
                    'data' => $user->profileKYC,
                    'user' => $user,
                ]);
            }

            if ($currentStatus === "1") {
                return response()->json([
                    'success' => false,
                    'message' => 'Your KYC has already been approved.',
                    'kyc_status' => '1',
                    'status_label' => 'Approved',
                    'can_resubmit' => false,
                    'data' => $user->profileKYC,
                    'user' => $user,
                ]);
            }
            // status "2" (rejected) falls through and is allowed to resubmit.
        }

        $item = request()->except('image', 'id_card', 'cac', 'nepa_bill');

        if (request('image')) {
            $imagePath = request('image')->store('verification/doc/image', 'public');
            $image = Image::make(public_path("storage/{$imagePath}"));
            $image->save();
            $item["image"] = $imagePath;
            if ($user->profileKYC != null && $user->profileKYC->image != null) {
                if (File::exists(public_path("storage/{$user->profileKYC->image}"))) {
                    File::delete(public_path("storage/{$user->profileKYC->image}"));
                }
            }
        }

        if (request('id_card')) {
            $imagePath = request('id_card')->store('verification/doc/idCard', 'public');
            $image = Image::make(public_path("storage/{$imagePath}"));
            $image->save();
            $item["id_card"] = $imagePath;
            if ($user->profileKYC != null && $user->profileKYC->id_card != null) {
                if (File::exists(public_path("storage/{$user->profileKYC->id_card}"))) {
                    File::delete(public_path("storage/{$user->profileKYC->id_card}"));
                }
            }
        }

        if (request('cac')) {
            $imagePath = request('cac')->store('verification/doc/cac', 'public');
            $image = Image::make(public_path("storage/{$imagePath}"));
            $image->save();
            $item["cac"] = $imagePath;
            if ($user->profileKYC != null && $user->profileKYC->cac != null) {
                if (File::exists(public_path("storage/{$user->profileKYC->cac}"))) {
                    File::delete(public_path("storage/{$user->profileKYC->cac}"));
                }
            }
        }

        if (request('nepa_bill')) {
            $imagePath = request('nepa_bill')->store('verification/doc/nepaBill', 'public');
            $image = Image::make(public_path("storage/{$imagePath}"));
            $image->save();
            $item["nepa_bill"] = $imagePath;
            if ($user->profileKYC != null && $user->profileKYC->nepa_bill != null) {
                if (File::exists(public_path("storage/{$user->profileKYC->nepa_bill}"))) {
                    File::delete(public_path("storage/{$user->profileKYC->nepa_bill}"));
                }
            }
        }

        if ($user->profileKYC != null) {
            $item["status"] = "0";
            $item["comment"] = null;
            $user->profileKYC->fill($item)->save();
        } else {
            $user->profileKYC()->create($item);
        }

        return response()->json([
            'success' => true,
            'data' => $user->profileKYC,
            'message' => 'KYC Information Submitted Successfully',
            'user' => $user,
        ]);
    }
}