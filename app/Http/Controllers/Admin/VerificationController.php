<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Fintech\WalletVerificaiton;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\TraitsFolder\MailTrait;
use App\Models\ErrorLog;

class VerificationController extends Controller
{
    use MailTrait;

    public function index()
    {
        $data["page_title"] = "Verification";
        $data["page_sub_title"] = "Verification";
        $data["breadcrumb"] = [
            [
                "title" => "Home",
                "url" => route('admin.index')
            ],
            [
                "title" => $data["page_sub_title"],
                "url" => null
            ],
        ];

        return view('admin.verifications.index', $data);
    }

    public function fetch(Request $request)
    {
        $query = WalletVerificaiton::with('user.profile');

        // Filter: status
        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter: method (BVN / NIN)
        if ($request->method !== null && $request->method !== '') {
            $query->where('method', strtoupper($request->method));
        }

        // Filter: date range
        if ($request->date && str_contains($request->date, ' to ')) {
            [$start, $end] = explode(' to ', $request->date);
            $query->whereBetween('created_at', [trim($start) . ' 00:00:00', trim($end) . ' 23:59:59']);
        }

        /*
         * ORDERING — newest submission first.
         *
         * The API stores KYC with updateOrCreate(['user_id' => ...]), so when a
         * user re-submits, the ORIGINAL row is updated and created_at never
         * moves. Sorting by created_at (the old ->latest()) therefore pushed
         * fresh re-submissions down the list where they were missed.
         *
         * We now sort on submitted_at, which the WalletVerificaiton model
         * stamps on every submission and re-submission, and fall back to
         * updated_at / created_at for any legacy row.
         */
        $recency = "COALESCE(submitted_at, updated_at, created_at)";

        if ($request->sort === 'pending_first') {
            // Pending (0) first, then rejected (2), then verified (1).
            $query->orderByRaw("FIELD(status, '0', '2', '1')");
        }

        $query->orderByRaw("$recency DESC")->orderByDesc('id');

        $data = $query->get();

        return response()->json([
            'data' => $data->map(function ($item) {
                $profile = optional($item->user)->profile;

                $fullName = trim(optional($profile)->first_name . ' ' . optional($profile)->last_name);

                $method = $item->method; // accessor normalises to BVN or NIN
                $methodBadge = $method === 'NIN'
                    ? '<span class="badge bg-primary">NIN</span>'
                    : '<span class="badge bg-dark">BVN</span>';

                // Only a pending record can be acted on.
                $isPending = (int) $item->status === 0;

                $actions = '<button class="btn btn-info btn-sm viewBtn" data-id="' . $item->id . '">View</button> ';

                if ($isPending) {
                    $actions .= '<button class="btn btn-success btn-sm verifyBtn"
                                    data-id="' . $item->id . '"
                                    data-method="' . e($method) . '"
                                    data-bvn="' . e((string) $item->bvn) . '"
                                    data-nin="' . e((string) $item->nin) . '"
                                    data-name="' . e(trim($item->first_name . ' ' . $item->last_name)) . '"
                                    data-dob="' . e((string) $item->date_of_birth) . '">Verify</button> ';
                    $actions .= '<button class="btn btn-danger btn-sm rejectBtn" data-id="' . $item->id . '">Reject</button>';
                }

                return [
                    'user'      => $fullName !== '' ? $fullName : (optional($item->user)->email ?? '—'),
                    'method'    => $methodBadge,
                    'id_number' => $item->identity_number ?: '—',
                    'name'      => trim($item->first_name . ' ' . $item->last_name),
                    'phone'     => $item->mobile_no,
                    'status'    => $this->statusBadge($item->status),
                    'date'      => optional($item->submitted_at ?? $item->created_at)->format('Y-m-d H:i'),
                    'actions'   => $actions,
                ];
            })
        ]);
    }

    private function statusBadge($status)
    {
        $status = (int) $status;

        return match ($status) {
            1 => '<span class="badge bg-success">Verified</span>',
            0 => '<span class="badge bg-warning text-dark">Pending</span>',
            2 => '<span class="badge bg-danger">Rejected</span>',
            default => '<span class="badge bg-secondary">Unknown</span>',
        };
    }

    /**
     * Approve a KYC record and create the SafeHaven subaccount.
     *
     * The admin now confirms which identity document to send (BVN or NIN) and
     * the number that goes with it, because a user may hold both and may have
     * picked the wrong one at submission time. Whatever the admin confirms is
     * saved back to the record BEFORE the API call, so the stored data always
     * matches what SafeHaven received.
     */
    public function verify(Request $request, $id)
    {
        $validated = Validator::make($request->all(), [
            'method'          => 'required|in:BVN,NIN',
            'identity_number' => 'required|digits:11',
            // Optional overrides. SafeHaven matches the phone and DOB against
            // the identity provider's records, NOT against the user's app
            // profile. When the account phone/record DOB differ from what is
            // registered to the BVN/NIN, the admin enters the registered
            // values here. Left blank -> fall back to the account phone and
            // the DOB already on the record (previous behaviour).
            'bvn_phone'       => 'nullable|string|min:10|max:15',
            'bvn_dob'         => 'nullable|date',
        ], [
            'method.required'          => 'Select whether this is a BVN or a NIN.',
            'method.in'                => 'Method must be either BVN or NIN.',
            'identity_number.required' => 'Enter the 11 digit identity number.',
            'identity_number.digits'   => 'The identity number must be exactly 11 digits.',
            'bvn_dob.date'             => 'The registered date of birth is not a valid date.',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(' ', $validated->errors()->all()),
            ], 422);
        }

        $method = strtoupper($request->method);
        $identityNumber = trim($request->identity_number);

        $verification = WalletVerificaiton::with('user.profile')->findOrFail($id);
        $user = $verification->user;

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'This verification is not linked to a user account.'
            ], 422);
        }

        if ((int) $verification->status === 1) {
            return response()->json([
                'success' => false,
                'message' => 'This verification has already been approved.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // The DOB sent to SafeHaven: the admin-supplied registered DOB if
            // given, otherwise the DOB already on the record.
            $dobSource = $request->filled('bvn_dob')
                ? $request->bvn_dob
                : $verification->date_of_birth;

            try {
                $formattedDate = Carbon::parse($dobSource)->format('Y-m-d');
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Invalid date of birth" . ($request->filled('bvn_dob') ? " entered." : " on this record.")
                ], 422);
            }

            // The phone sent to SafeHaven: the admin-supplied registered phone
            // if given, otherwise the phone captured at KYC (mobile_no), which
            // the app collects as the BVN-registered number. Only if that is
            // empty do we fall back to the user's app account phone.
            $phoneForSafeHaven = $request->filled('bvn_phone')
                ? trim($request->bvn_phone)
                : ($verification->mobile_no ?: $user->phone_number);

            // Persist the admin's choice before calling SafeHaven, so the row
            // always reflects exactly what we sent.
            $verification->method = $method;

            if ($method === 'NIN') {
                $verification->nin = $identityNumber;
            } else {
                $verification->bvn = $identityNumber;
            }

            // If the admin corrected the phone/DOB, store them on the record so
            // it matches what SafeHaven verified against.
            if ($request->filled('bvn_phone')) {
                $verification->mobile_no = $phoneForSafeHaven;
            }
            if ($request->filled('bvn_dob')) {
                $verification->date_of_birth = $formattedDate;
            }

            $verification->save();

            $virtualAccountResult = createSafeHavenSubaccount([
                'user_id'           => $user->id,
                'phoneNumber'       => $phoneForSafeHaven,
                'emailAddress'      => $user->email,
                'identityType'      => $method,           // <- BVN or NIN, chosen by admin
                'identityNumber'    => $identityNumber,
                'externalReference' => rand(1000000, 999999999),
                'dob'               => $formattedDate,
            ]);

            if (!$virtualAccountResult || ($virtualAccountResult['success'] ?? false) === false) {

                DB::rollBack();

                $errorMessage = $virtualAccountResult['message'] ?? "Unable to create virtual account.";

                ErrorLog::create([
                    "user_id"   => $user->id,
                    "user_name" => optional($user->profile)->first_name . ' ' . optional($user->profile)->last_name,
                    "message"   => $errorMessage,
                    "model"     => "Create Virtual Account ({$method})",
                    "data"      => json_encode($virtualAccountResult),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "Virtual account creation failed using {$method}. Api Error = " . $errorMessage
                ], 500);
            }

            $verification->update([
                'status'      => 1,
                'reviewed_at' => now(),
                'reviewed_by' => optional(auth()->guard('admin')->user())->id,
            ]);

            try {
                $message = "
                    <p>Your wallet verification has been approved.</p>
                    <p>Your account is now fully active.</p>
                ";

                $this->sendMail(
                    $user->email,
                    $user->profile->username ?? 'User',
                    "Verification Approved",
                    $message
                );
            } catch (\Throwable $th) {}

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "User verified successfully using {$method}"
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return response()->json([
                'success' => false,
                'message' => "Something went wrong"
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $verification = WalletVerificaiton::with('user.profile')->findOrFail($id);
        $user = $verification->user;

        $verification->update([
            'status'      => '2',
            'comment'     => $request->reason,
            'reviewed_at' => now(),
            'reviewed_by' => optional(auth()->guard('admin')->user())->id,
        ]);

        try {
            $message = "
                <p>Your account verification was rejected.</p>
                <p><strong>Reason:</strong> {$request->reason}</p>
                <p>Please update your details and try again.</p>
            ";

            $this->sendMail(
                $user->email,
                $user->profile->username ?? 'User',
                "Verification Rejected",
                $message
            );
        } catch (\Throwable $th) {}

        return response()->json([
            'success' => true,
            'message' => "Verification rejected"
        ]);
    }

    /**
     * Change the identity method on a record without approving it.
     * Used by the "Set BVN / NIN" control on the list.
     */
    public function updateMethod(Request $request, $id)
    {
        $validated = Validator::make($request->all(), [
            'method'          => 'required|in:BVN,NIN',
            'identity_number' => 'nullable|digits:11',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(' ', $validated->errors()->all()),
            ], 422);
        }

        $verification = WalletVerificaiton::findOrFail($id);
        $method = strtoupper($request->method);

        $verification->method = $method;

        if ($request->filled('identity_number')) {
            if ($method === 'NIN') {
                $verification->nin = trim($request->identity_number);
            } else {
                $verification->bvn = trim($request->identity_number);
            }
        }

        $verification->save();

        return response()->json([
            'success' => true,
            'message' => "Identity method set to {$method}",
        ]);
    }

    public function show($id)
    {
        $verification = WalletVerificaiton::findOrFail($id);

        // Expose the computed fields the details modal needs.
        $payload = $verification->toArray();
        $payload['method'] = $verification->method;
        $payload['identity_number'] = $verification->identity_number;
        $payload['status_label'] = $verification->status_label;

        return response()->json($payload);
    }

    public function createBankAccount(Request $request, $userId)
    {
        $user = User::with('WalletVerificaiton')->findOrFail($userId);

        // Check if virtual account already exists
        if ($user->virtualAccount) {
            return response()->json([
                'success' => false,
                'message' => 'User already has a virtual account.'
            ], 400);
        }

        // Check if wallet verification exists
        if (!$user->WalletVerificaiton) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet verification record not found for this user.'
            ], 404);
        }

        $verification = $user->WalletVerificaiton;

        // The admin may override the method on this call too.
        $method = strtoupper($request->input('method', $verification->method));
        $method = in_array($method, ['BVN', 'NIN'], true) ? $method : 'BVN';

        $identityNumber = $request->input('identity_number');

        if (! $identityNumber) {
            $identityNumber = $method === 'NIN'
                ? ($verification->nin ?: $verification->bvn)
                : ($verification->bvn ?: $verification->nin);
        }

        if (! $identityNumber) {
            return response()->json([
                'success' => false,
                'message' => "No {$method} on file for this user.",
            ], 422);
        }

        // Validate and format date of birth
        try {
            $formattedDate = Carbon::parse($verification->date_of_birth)->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Invalid date format in wallet verification record"
            ], 422);
        }

        // Call the external API to create virtual account
        $virtualAccountResult = createSafeHavenSubaccount([
            'user_id'           => $user->id,
            'phoneNumber'       => $user->phone_number,
            'emailAddress'      => $user->email,
            'identityType'      => $method,
            'identityNumber'    => $identityNumber,
            'externalReference' => rand(1000000, 999999999),
            'dob'               => $formattedDate,
        ]);

        // Validate API response
        if (
            !$virtualAccountResult ||
            !isset($virtualAccountResult['success']) ||
            $virtualAccountResult['success'] === false
        ) {
            return response()->json([
                'success' => false,
                'message' => "Unable to create virtual account. Contact help desk.",
                'data' => $virtualAccountResult,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Virtual account created successfully.',
            'data' => $virtualAccountResult
        ]);
    }
}