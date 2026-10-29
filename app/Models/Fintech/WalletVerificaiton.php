<?php

namespace App\Models\Fintech;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletVerificaiton extends Model
{
    use HasFactory;
    public $guarded = [];
    
  

    public function rules($id = 0, $merge = [])
    {  
        
        $isUpdate = $id ? true : false;
        
        return array_merge(
            [
                'bvn' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                'other_names' => 'required',
                'mobile_no' => 'required',
                'date_of_birth' => 'required',
                'state' => 'required',
                'address' => 'required',
                'city' => 'required',
                  'id_image' => $isUpdate ? 'nullable|image|mimes:jpg,jpeg,png|max:2048' : 'required|image|mimes:jpg,jpeg,png|max:2048',
            'user_photo' => $isUpdate ? 'nullable|image|mimes:jpg,jpeg,png|max:2048' : 'required|image|mimes:jpg,jpeg,png|max:2048',
       
            ],
            $merge
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }




    public  function column()
    {
        $option  =
            [
                [
                    "name" => "Verified",
                    "id" => "1"
                ],
                [
                    "name" => "Pending",
                    "id" => "0"
                ],
                [
                    "name" => "Unverified",
                    "id" => "2"
                ]
            ];
        $column = [
            "username" => [
                "name" => "User",
                "description" => "Please Select",
                "column_name" => "username",
                "input_type" => "1",
                "required" => "",
                "value" => "",
                "model" => "user->profile->username",
                "link" => route('admin.user.show', '@@this@@'),
                'edit' => false
            ],
            "bvn" => [
                "name" => "BVN",
                "description" => "Enter BVN",
                "column_name" => "bvn",
                "input_type" => "0",
                "value" => "",
                "required" => "required",

            ],
            "name" => [
                "name" => "Name",
                "description" => "Enter  name",
                "column_name" => "name",
                "input_type" => "0",
                "required" => "",
                "value" => "",

            ],
            "first_name" => [
                "name" => "First Name",
                "description" => "Enter first name",
                "column_name" => "first_name",
                "input_type" => "0",
                "required" => "required",
                "value" => "",

            ],
            "last_name" => [
                "name" => "Last Name",
                "description" => "Enter last name",
                "column_name" => "last_name",
                "input_type" => "0",
                "required" => "required",
                "value" => "",

            ],
            "other_names" => [
                "name" => "Other Names",
                "description" => "Enter other names",
                "column_name" => "other_names",
                "input_type" => "0",
                "required" => "required",
                "value" => "",

            ],
            "mobile_no" => [
                "name" => "Phone Number",
                "description" => "Enter Number",
                "column_name" => "mobile_no",
                "input_type" => "0",
                "editable" => true,
                "value" => "",
                "required" => "required",
            ],
            "date_of_birth" => [
                "name" => "DOB",
                "description" => "Enter date of birth",
                "column_name" => "date_of_birth",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            
            "state" => [
                "name" => "State",
                "description" => "Enter State",
                "column_name" => "state",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            
            "city" => [
                "name" => "City",
                "description" => "Enter City",
                "column_name" => "city",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            
            "address" => [
                "name" => "Address",
                "description" => "Enter address line1",
                "column_name" => "address",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            
            "house_number" => [
                "name" => "House Number",
                "description" => "Enter House Number",
                "column_name" => "house_number",
                "input_type" => "0",
                "value" => "",
                "required" => "",
            ],
            
            "zip_code" => [
                "name" => "Zip Code",
                "description" => "Enter Zip Code",
                "column_name" => "zip_code",
                "input_type" => "0",
                "value" => "",
                "required" => "",
            ],
            
            "issued_country" => [
                "name" => "Issued Country",
                "description" => "Enter Issued Country",
                "column_name" => "issued_country",
                "input_type" => "0",
                "value" => "",
                "required" => "",
            ],
            
            "issued_date" => [
                "name" => "Issued Date",
                "description" => "Enter Issued Date",
                "column_name" => "issued_date",
                "input_type" => "0",
                "value" => "",
                "required" => "",
            ],
            
            "expiration_date" => [
                "name" => "Expiration Date",
                "description" => "Enter Expiration Date",
                "column_name" => "expiration_date",
                "input_type" => "0",
                "value" => "",
                "required" => "",
            ],
            
            "id_image" => [
                "name" => "ID Image",
                "description" => "Upload ID Image",
                "column_name" => "id_image",
                "input_type" => "7",
                "value" => "",
                "required" => "required",
            ],
            
            "user_photo" => [
                "name" => "User Photo",
                "description" => "Upload User Photo",
                "column_name" => "user_photo",
                "input_type" => "7",
                "value" => "",
                "required" => "required",
            ],

            "result" => [
                "name" => "Result",
                "description" => "Enter result",
                "column_name" => "result",
                "input_type" => "0",
                "value" => "",
                "editable" => true,
                "view" => false,
                "required" => ""
            ],
            "comment" => [
                "name" => "Comment",
                "description" => "Enter comment",
                "column_name" => "comment",
                "input_type" => "0",
                "value" => "",
                "editable" => true,
                "required" => "",
                "view" => false,
            ],

            "date" => [
                "name" => "Date",
                "description" => "Enter Date",
                "column_name" => "created_at",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "create" => false,
                "edit" => false
            ],
            "status" => [
                "name" => "status",
                "description" => "Select Status",
                "column_name" => "status",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $option,
            ]

        ];
        return json_decode(json_encode($column));
    }
    
    // status is a 3-state value: 0 = Pending, 1 = Verified/Approved, 2 = Rejected.
    // NOTE: it must NOT be cast to boolean. A boolean cast turns 2 into `true`,
    // which then reads back as "verified" everywhere and is the reason a
    // rejected KYC was showing as Verified.
    protected $casts = [
        'status' => 'integer',
    ];

    /**
     * ALWAYS write status as a STRING.
     *
     * `status` is an ENUM('0','1','2') in the live schema. MySQL treats a
     * NUMERIC value against an ENUM as a member INDEX, not a literal — so
     * writing integer 0 selects index 0, which is the invalid/empty member,
     * and the write fails with:
     *
     *     SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status'
     *
     * Integer 1 would be worse: it silently resolves to index 1, i.e. the
     * member '0' (Pending) — a wrong value written with no error at all.
     *
     * The `status => integer` cast above only applies on READ, so it does not
     * protect writes. This mutator does, for every writer: the KYC API,
     * AuthController::register(), the admin screens and the guard below.
     */
    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = (string) (int) $value;
    }

    /**
     * Human readable status label used by admin + API so all screens agree.
     * 0 => Pending, 1 => Approved (Verified), 2 => Rejected.
     */
    public function getStatusLabelAttribute()
    {
        return [
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Rejected',
        ][(int) $this->status] ?? 'Unknown';
    }

    /* ------------------------------------------------------------------
     | Identity method (BVN / NIN)
     |-------------------------------------------------------------------
     | The user chooses this when they submit, but the admin can override it
     | on the Verifications page before we send the record to SafeHaven —
     | some users hold both a BVN and a NIN, and the wrong one was being
     | pushed as `identityType`.
     */
    public const METHODS = ['BVN', 'NIN'];

    public function getMethodAttribute($value): string
    {
        $value = strtoupper(trim((string) $value));

        return in_array($value, self::METHODS, true) ? $value : 'BVN';
    }

    public function setMethodAttribute($value): void
    {
        $value = strtoupper(trim((string) $value));

        $this->attributes['method'] = in_array($value, self::METHODS, true) ? $value : 'BVN';
    }

    /**
     * The number that must go to SafeHaven as `identityNumber`, based on the
     * currently selected method. Falls back to the BVN column, which is where
     * every number used to be stored before the `nin` column existed.
     */
    public function getIdentityNumberAttribute(): ?string
    {
        if ($this->method === 'NIN') {
            return $this->nin ?: $this->bvn;
        }

        return $this->bvn ?: $this->nin;
    }

    /* ------------------------------------------------------------------
     | Status lifecycle guard  (0 = Pending, 1 = Approved, 2 = Rejected)
     |-------------------------------------------------------------------
     | TWO BUGS, ONE ROOT CAUSE.
     |
     |   a) "After I reject a KYC, when the user resends it, it does not go
     |       back to the admin."
     |   b) "KYC is automatically approving instead of pending."
     |
     | Both come from the same place. The API writes submissions with
     |
     |     $user->walletVerification()->updateOrCreate(['user_id' => $id], [...])
     |
     | and that payload does NOT include `status`. updateOrCreate only writes
     | the columns it is handed, so on a re-submission the row keeps whatever
     | status the ADMIN last set:
     |
     |   * previously rejected -> stays 2  -> never appears in the Pending
     |                                        queue -> "not going back to admin"
     |   * previously approved -> stays 1  -> "automatically approving"
     |
     | Sorting was fixed earlier, which is why this looked like a sorting
     | problem at first. It is not. The record genuinely is not pending.
     |
     | Fixing this in the model rather than in the controller is deliberate:
     | wallet_verificaitons is written from at least three places
     | (AuthController::register, verification/wallet-kyc, and the admin
     | screens). A guard here holds for all of them, including any that get
     | added later, and does not require editing the 845-line
     | KYCVerificationController.
     */

    /**
     * Fields that ONLY a user submission changes.
     *
     * `method`, `bvn` and `nin` are deliberately excluded: the admin edits
     * those from the Verify modal and from updateMethod() without that being
     * a re-submission.
     */
    public const SUBMISSION_FIELDS = [
        'name',
        'first_name',
        'last_name',
        'other_names',
        'mobile_no',
        'date_of_birth',
        'gender',
        'address',
        'house_number',
        'city',
        'state',
        'zip_code',
        'issued_country',
        'issued_date',
        'expiration_date',
        'id_image',
        'user_photo',
    ];

    /**
     * Explicit hook for the API to call when it knows it is handling a
     * submission. Not required — the saving() guard below catches it anyway —
     * but clearer at the call site if you want it:
     *
     *     $verification->fill($data)->markAsResubmitted()->save();
     */
    public function markAsResubmitted(): self
    {
        $this->status       = 0;
        $this->comment      = null;
        $this->submitted_at = now();

        foreach (['reviewed_at', 'reviewed_by'] as $col) {
            if (array_key_exists($col, $this->attributes) || $this->isFillable($col)) {
                $this->{$col} = null;
            }
        }

        return $this;
    }

    protected static function booted()
    {
        static::saving(function (self $verification) {

            // ── New record ──────────────────────────────────────────────────
            // A submission is pending by definition. Only an explicit write of
            // 1 or 2 (i.e. an admin acting) may say otherwise.
            if (! $verification->exists) {
                $verification->submitted_at = now();

                if (! $verification->isDirty('status') || $verification->status === null) {
                    $verification->status = 0;
                }

                return;
            }

            // ── Existing record ─────────────────────────────────────────────
            // A reviewer decision is a save that sets status to 1 or 2. Any
            // other save that touches the user's own submitted fields is a
            // RE-SUBMISSION, and must drop the record back into the pending
            // queue with the old rejection comment cleared.
            $reviewerDecision = $verification->isDirty('status')
                && (int) $verification->status !== 0;

            if ($reviewerDecision) {
                return;
            }

            if ($verification->isDirty(self::SUBMISSION_FIELDS)) {
                $verification->status       = 0;
                $verification->comment      = null;
                $verification->submitted_at = now();

                // Only null these if the columns actually exist — the live
                // schema has drifted from the migrations more than once.
                foreach (['reviewed_at', 'reviewed_by'] as $col) {
                    if (array_key_exists($col, $verification->getAttributes())) {
                        $verification->{$col} = null;
                    }
                }

                return;
            }

            // Anything else that is already pending just re-stamps recency, so
            // the admin list keeps sorting correctly.
            if ((int) $verification->status === 0 && $verification->isDirty()) {
                $verification->submitted_at = now();
            }
        });
    }
}