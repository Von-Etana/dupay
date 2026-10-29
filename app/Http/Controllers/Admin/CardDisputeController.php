<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardDispute;
use App\Models\PhysicalCard;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

/**
 * Physical ATM card disputes (admin only).
 *
 * Chargebacks are filed with StroWallet:
 *   POST https://strowallet.com/api/naira_CreateDispute
 *        ?public_key=..&transactionId=..&reason=..&explanation=..&mode=..
 *   -> { success: true, message: "...", dispute_id: "6852c4ffe5aa2fd9" }
 *
 * A local row is written whatever happens. If StroWallet rejects the call the
 * row is kept with status = failed so the filing is never lost and the admin
 * can retry from the details page.
 */
class CardDisputeController extends Controller
{
    protected const DISPUTE_URL = 'https://strowallet.com/api/naira_CreateDispute';

    /* =====================================================================
     | Listing
     |==================================================================== */

    public function index()
    {
        $data = [
            "page_title"     => "ATM Disputes",
            "page_sub_title" => "Physical ATM Card Disputes",
            "breadcrumb"     => [
                ["title" => "Home", "url" => route('admin.index')],
                ["title" => "ATM Disputes", "url" => null],
            ],
            "ajaxUrl"        => route('admin.card_disputes.ajax'),
            "reasons"        => CardDispute::REASONS,
        ];

        return view('admin.card_disputes.index', $data);
    }

    public function ajax(Request $request)
    {
        $disputes = CardDispute::with(['user.profile', 'physicalCard'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('reason'), fn ($q) => $q->where('reason', $request->reason))
            ->orderByDesc('id');

        return DataTables::eloquent($disputes)
            ->addColumn('user_name', fn ($d) => optional(optional($d->user)->profile)->username
                ?? trim(optional(optional($d->user)->profile)->first_name . ' ' . optional(optional($d->user)->profile)->last_name)
                ?: 'N/A')
            ->addColumn('user_email', fn ($d) => optional($d->user)->email ?? 'N/A')
            ->editColumn('masked_pan', fn ($d) => $d->masked_pan ?: ($d->card_id ?: '—'))
            ->editColumn('amount', fn ($d) => $d->amount !== null
                ? (function_exists('getPrice') ? getPrice($d->amount) : number_format((float) $d->amount, 2))
                : '—')
            ->editColumn('reason', fn ($d) => $d->reason_label)
            ->editColumn('status', fn ($d) => $d->status_badge)
            ->editColumn('dispute_id', fn ($d) => $d->dispute_id ?: '—')
            ->editColumn('created_at', fn ($d) => $d->created_at ? $d->created_at->format('Y-m-d H:i') : '—')
            ->filterColumn('user_name', function ($query, $keyword) {
                $query->whereHas('user.profile', function ($q) use ($keyword) {
                    $q->where('username', 'like', "%{$keyword}%")
                      ->orWhere('first_name', 'like', "%{$keyword}%")
                      ->orWhere('last_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('user_email', function ($query, $keyword) {
                $query->whereHas('user', fn ($q) => $q->where('email', 'like', "%{$keyword}%"));
            })
            ->filterColumn('reason', function ($query, $keyword) {
                $query->where('reason', 'like', "%{$keyword}%");
            })
            ->addColumn('action', function ($d) {
                $show = route('admin.card_disputes.show', $d->id);

                return '<a href="' . $show . '" class="btn btn-sm btn-info">View</a>';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    /* =====================================================================
     | Creating a dispute
     |==================================================================== */

    public function create()
    {
        $data = [
            "page_title"     => "File ATM Dispute",
            "page_sub_title" => "File a Physical ATM Card Dispute",
            "breadcrumb"     => [
                ["title" => "Home", "url" => route('admin.index')],
                ["title" => "ATM Disputes", "url" => route('admin.card_disputes.index')],
                ["title" => "File Dispute", "url" => null],
            ],
            "reasons"        => CardDispute::REASONS,
        ];

        return view('admin.card_disputes.create', $data);
    }

    /**
     * Card picker. Searches by username, name, email, masked PAN or card id.
     */
    public function searchCards(Request $request)
    {
        $term = trim((string) $request->input('q'));

        $cards = PhysicalCard::with('user.profile')
            ->whereNotNull('card_id')
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('card_id', 'like', "%{$term}%")
                          ->orWhere('masked_pan', 'like', "%{$term}%")
                          ->orWhere('card_pan', 'like', "%{$term}%")
                          ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$term}%"))
                          ->orWhereHas('user.profile', function ($p) use ($term) {
                              $p->where('username', 'like', "%{$term}%")
                                ->orWhere('first_name', 'like', "%{$term}%")
                                ->orWhere('last_name', 'like', "%{$term}%");
                          });
                });
            })
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cards->map(function ($card) {
                $profile = optional($card->user)->profile;

                $name = optional($profile)->username
                    ?: trim(optional($profile)->first_name . ' ' . optional($profile)->last_name);

                return [
                    'id'         => $card->id,
                    'card_id'    => $card->card_id,
                    'masked_pan' => $card->masked_pan ?: $card->card_pan,
                    'brand'      => strtoupper((string) $card->brand),
                    'status'     => $card->status,
                    'user'       => $name ?: 'Unknown',
                    'email'      => optional($card->user)->email,
                ];
            }),
        ]);
    }

    /**
     * Pull the ATM history for a card so the admin can pick the exact
     * transaction being disputed. Uses the existing StroWallet helper.
     */
    public function cardTransactions(Request $request, $physicalCardId)
    {
        $card = PhysicalCard::with('user.profile')->find($physicalCardId);

        if (! $card) {
            return response()->json(['success' => false, 'message' => 'Card not found.'], 404);
        }

        if (! $card->card_id) {
            return response()->json([
                'success' => false,
                'message' => 'This card has no StroWallet card ID, so its history cannot be loaded. Enter the transaction ID manually.',
            ], 422);
        }

        $transactions = [];
        $message = null;

        if (function_exists('getVirtualNairaCardATMHistory')) {
            $response = getVirtualNairaCardATMHistory($card->card_id);

            if (isset($response['success']) && $response['success'] === true) {
                $transactions = $response['data'] ?? [];
            } else {
                $message = $response['message'] ?? 'StroWallet did not return any history for this card.';

                Log::warning('Card dispute: history fetch failed', [
                    'physical_card_id' => $card->id,
                    'card_id'          => $card->card_id,
                    'response'         => $response,
                ]);
            }
        } else {
            $message = 'Card history helper is unavailable.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'card'    => [
                'id'         => $card->id,
                'card_id'    => $card->card_id,
                'masked_pan' => $card->masked_pan ?: $card->card_pan,
                'currency'   => strtoupper((string) ($card->currency ?: 'NGN')),
                'user_id'    => $card->user_id,
            ],
            'data' => collect($transactions)->map(function ($t) {
                // StroWallet's naira_cardhistory rows expose the reference as
                // withdraw_id; other builds have used id / transaction_id /
                // reference, so accept whichever is present.
                $reference = $t['withdraw_id']
                    ?? $t['transaction_id']
                    ?? $t['transactionId']
                    ?? $t['reference']
                    ?? $t['id']
                    ?? null;

                $rawAmount = $t['amount'] ?? null;

                return [
                    'transaction_id' => $reference,
                    // StroWallet returns amounts pre-formatted, e.g. "84,364.00".
                    // `amount` is normalised for the form field (Laravel's
                    // numeric rule rejects the comma); `amount_display` keeps
                    // the readable version for the table.
                    'amount'         => $this->normalizeAmount($rawAmount),
                    'amount_display' => $rawAmount,
                    'fee'            => $t['fee'] ?? null,
                    'location'       => $t['location'] ?? null,
                    'narration'      => $t['narration'] ?? ($t['description'] ?? null),
                    'date'           => $t['created_at'] ?? ($t['date'] ?? null),
                ];
            })->filter(fn ($t) => ! empty($t['transaction_id']))->values(),
        ]);
    }

    /**
     * Strip thousands separators, currency symbols and stray whitespace so a
     * provider-formatted amount like "84,364.00" or "NGN 5,000" passes
     * Laravel's `numeric` rule. Returns null when nothing usable is left.
     */
    protected function normalizeAmount($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return ($clean !== '' && is_numeric($clean)) ? $clean : null;
    }

    public function store(Request $request)
    {
        // Defence in depth: normalise before validation in case the amount was
        // typed by hand or came from an older cached page.
        if ($request->filled('amount')) {
            $request->merge(['amount' => $this->normalizeAmount($request->input('amount'))]);
        }

        $validator = Validator::make($request->all(), [
            'physical_card_id' => 'required|exists:physical_cards,id',
            'transaction_id'   => 'required|string|max:191',
            'reason'           => 'required|string|in:' . implode(',', array_keys(CardDispute::REASONS)),
            'explanation'      => 'required|string|min:10|max:1000',
            'amount'           => 'nullable|numeric',
            'transaction_date' => 'nullable|string|max:100',
            'location'         => 'nullable|string|max:191',
            'admin_note'       => 'nullable|string|max:1000',
        ], [
            'explanation.min' => 'Give StroWallet at least a sentence explaining the issue.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $card = PhysicalCard::with('user')->findOrFail($request->physical_card_id);

        // Don't file the same transaction twice.
        $duplicate = CardDispute::where('transaction_id', $request->transaction_id)
            ->whereIn('status', ['pending', 'submitted', 'won'])
            ->first();

        if ($duplicate) {
            Session::flash('message', 'A dispute for that transaction already exists (#' . $duplicate->id . ').');
            Session::flash('type', 'error');
            Session::flash('title', 'Duplicate');

            return redirect()->route('admin.card_disputes.show', $duplicate->id);
        }

        $dispute = CardDispute::create([
            'user_id'          => $card->user_id,
            'physical_card_id' => $card->id,
            'card_id'          => $card->card_id,
            'masked_pan'       => $card->masked_pan ?: $card->card_pan,
            'transaction_id'   => trim($request->transaction_id),
            'amount'           => $request->amount,
            'currency'         => strtoupper((string) ($card->currency ?: 'NGN')),
            'transaction_date' => $request->transaction_date,
            'location'         => $request->location,
            'reason'           => $request->reason,
            'explanation'      => trim($request->explanation),
            'admin_note'       => $request->admin_note,
            'status'           => 'pending',
            'created_by'       => optional(auth()->guard('admin')->user())->id,
        ]);

        $this->submitToProvider($dispute);

        if ($dispute->status === 'submitted') {
            Session::flash('message', 'Dispute filed with StroWallet. Reference: ' . $dispute->dispute_id);
            Session::flash('type', 'success');
            Session::flash('title', 'Success');
        } else {
            Session::flash('message', 'Dispute saved but StroWallet rejected it: ' . $dispute->provider_message);
            Session::flash('type', 'error');
            Session::flash('title', 'Not submitted');
        }

        return redirect()->route('admin.card_disputes.show', $dispute->id);
    }

    /* =====================================================================
     | Single dispute
     |==================================================================== */

    public function show($id)
    {
        $dispute = CardDispute::with(['user.profile', 'physicalCard'])->findOrFail($id);

        $data = [
            "page_title"     => "Dispute #" . $dispute->id,
            "page_sub_title" => "ATM Dispute Details",
            "breadcrumb"     => [
                ["title" => "Home", "url" => route('admin.index')],
                ["title" => "ATM Disputes", "url" => route('admin.card_disputes.index')],
                ["title" => "Dispute #" . $dispute->id, "url" => null],
            ],
            "reasons"        => CardDispute::REASONS,
        ];

        return view('admin.card_disputes.show', array_merge($data, compact('dispute')));
    }

    /**
     * Retry a dispute StroWallet previously rejected.
     */
    public function retry($id)
    {
        $dispute = CardDispute::findOrFail($id);

        if ($dispute->status === 'submitted' || $dispute->dispute_id) {
            Session::flash('message', 'This dispute was already accepted by StroWallet.');
            Session::flash('type', 'error');
            Session::flash('title', 'Nothing to retry');

            return back();
        }

        $this->submitToProvider($dispute);

        if ($dispute->status === 'submitted') {
            Session::flash('message', 'Dispute filed with StroWallet. Reference: ' . $dispute->dispute_id);
            Session::flash('type', 'success');
            Session::flash('title', 'Success');
        } else {
            Session::flash('message', 'StroWallet rejected it again: ' . $dispute->provider_message);
            Session::flash('type', 'error');
            Session::flash('title', 'Not submitted');
        }

        return back();
    }

    /**
     * Record the outcome once StroWallet resolves the chargeback.
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status'     => 'required|in:pending,submitted,failed,won,lost,withdrawn',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $dispute = CardDispute::findOrFail($id);

        $dispute->update([
            'status'      => $request->status,
            'admin_note'  => $request->admin_note ?? $dispute->admin_note,
            'resolved_at' => in_array($request->status, ['won', 'lost', 'withdrawn'], true) ? now() : null,
        ]);

        Session::flash('message', 'Dispute updated.');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return back();
    }

    public function destroy($id)
    {
        $dispute = CardDispute::findOrFail($id);
        $dispute->delete();

        Session::flash('message', 'Dispute deleted.');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return redirect()->route('admin.card_disputes.index');
    }

    /* =====================================================================
     | StroWallet call
     |==================================================================== */

    /**
     * Files the dispute with StroWallet and writes the outcome onto the model.
     * Never throws — a failure is recorded as status = failed.
     */
    protected function submitToProvider(CardDispute $dispute): void
    {
        $publicKey = config('services.strowallet.public_key') ?: env('STROWALLET_PUBLIC_KEY');

        if (! $publicKey) {
            $dispute->update([
                'status'           => 'failed',
                'provider_message' => 'STROWALLET_PUBLIC_KEY is not set.',
            ]);

            return;
        }

        // Reuse the shared sandbox/live switch from StroWalletHelper.
        $modeParam = function_exists('stroModeParam')
            ? stroModeParam()
            : (env('STROWALLET_MODE', 'live') === 'sandbox' ? ['mode' => 'sandbox'] : []);

        try {
            $client = new Client(['timeout' => 30]);

            $response = $client->request('POST', self::DISPUTE_URL, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'query' => array_merge([
                    'public_key'    => $publicKey,
                    'transactionId' => $dispute->transaction_id,
                    'reason'        => $dispute->reason,
                    'explanation'   => $dispute->explanation,
                ], $modeParam),
                'http_errors' => false,
            ]);

            $raw = (string) $response->getBody();
            $body = json_decode($raw, true);

            if (! is_array($body)) {
                $dispute->update([
                    'status'            => 'failed',
                    'provider_message'  => 'Unreadable response from StroWallet (HTTP ' . $response->getStatusCode() . ').',
                    'provider_response' => $raw,
                ]);

                Log::error('Card dispute: unreadable StroWallet response', [
                    'dispute_id' => $dispute->id,
                    'status'     => $response->getStatusCode(),
                    'body'       => $raw,
                ]);

                return;
            }

            if (($body['success'] ?? false) === true) {
                $dispute->update([
                    'status'            => 'submitted',
                    'dispute_id'        => $body['dispute_id'] ?? null,
                    'provider_message'  => $body['message'] ?? 'Dispute created and submitted successfully.',
                    'provider_response' => json_encode($body),
                    'submitted_at'      => now(),
                ]);

                return;
            }

            $dispute->update([
                'status'            => 'failed',
                'provider_message'  => $body['message'] ?? 'StroWallet declined the dispute.',
                'provider_response' => json_encode($body),
            ]);

            Log::warning('Card dispute rejected by StroWallet', [
                'dispute_id' => $dispute->id,
                'response'   => $body,
            ]);

        } catch (\Throwable $e) {
            $dispute->update([
                'status'           => 'failed',
                'provider_message' => $e->getMessage(),
            ]);

            Log::error('Card dispute: StroWallet request threw', [
                'dispute_id' => $dispute->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}