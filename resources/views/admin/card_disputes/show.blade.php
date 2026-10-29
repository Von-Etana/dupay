@extends('admin.layouts.app')

@section('title', $page_title)

@section('content')
<div class="layout-px-spacing">
    <div class="middle-content container-xxl p-0">

        <!-- BREADCRUMB -->
        <div class="page-meta">
            <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    @foreach($breadcrumb as $crumb)
                        @if($crumb['url'])
                            <li class="breadcrumb-item"><a href="{{ $crumb['url'] }}">{{ $crumb['title'] }}</a></li>
                        @else
                            <li class="breadcrumb-item active" aria-current="page">{{ $crumb['title'] }}</li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        </div>
        <!-- /BREADCRUMB -->

        <div class="row layout-spacing layout-top-spacing">

            <!-- DETAILS -->
            <div class="col-lg-7">
                <div class="statbox widget box box-shadow">
                    <div class="widget-content widget-content-area p-4">

                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="mb-0">Dispute #{{ $dispute->id }}</h5>
                            <div>{!! $dispute->status_badge !!}</div>
                        </div>

                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <th style="width: 40%;">Customer</th>
                                    <td>
                                        {{ optional(optional($dispute->user)->profile)->username
                                            ?? trim(optional(optional($dispute->user)->profile)->first_name . ' ' . optional(optional($dispute->user)->profile)->last_name)
                                            ?: 'N/A' }}
                                        <br>
                                        <small class="text-muted">{{ optional($dispute->user)->email }}</small>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Card</th>
                                    <td>{{ $dispute->masked_pan ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th>StroWallet card ID</th>
                                    <td><code>{{ $dispute->card_id ?: '—' }}</code></td>
                                </tr>
                                <tr>
                                    <th>Transaction ID</th>
                                    <td><code>{{ $dispute->transaction_id }}</code></td>
                                </tr>
                                <tr>
                                    <th>Amount</th>
                                    <td>
                                        @if($dispute->amount !== null)
                                            {{ $dispute->currency }} {{ number_format((float) $dispute->amount, 2) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Transaction date</th>
                                    <td>{{ $dispute->transaction_date ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Location</th>
                                    <td>{{ $dispute->location ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Reason</th>
                                    <td>{{ $dispute->reason_label }} <small class="text-muted">({{ $dispute->reason }})</small></td>
                                </tr>
                                <tr>
                                    <th>Explanation</th>
                                    <td>{{ $dispute->explanation }}</td>
                                </tr>
                                <tr>
                                    <th>StroWallet reference</th>
                                    <td>
                                        @if($dispute->dispute_id)
                                            <code>{{ $dispute->dispute_id }}</code>
                                        @else
                                            <span class="text-muted">Not issued</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Provider message</th>
                                    <td>{{ $dispute->provider_message ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Filed</th>
                                    <td>{{ optional($dispute->created_at)->format('Y-m-d H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Submitted to StroWallet</th>
                                    <td>{{ optional($dispute->submitted_at)->format('Y-m-d H:i') ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Resolved</th>
                                    <td>{{ optional($dispute->resolved_at)->format('Y-m-d H:i') ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Internal note</th>
                                    <td>{{ $dispute->admin_note ?: '—' }}</td>
                                </tr>
                            </tbody>
                        </table>

                        @if($dispute->provider_response)
                            <details class="mt-3">
                                <summary class="text-muted" style="cursor:pointer;">Raw StroWallet response</summary>
                                <pre class="mt-2 p-3" style="background: rgba(127,127,127,.14); border-radius: 6px; white-space: pre-wrap; word-break: break-word;">{{ $dispute->provider_response }}</pre>
                            </details>
                        @endif

                    </div>
                </div>
            </div>

            <!-- ACTIONS -->
            <div class="col-lg-5">

                @if($dispute->status === 'failed')
                    <div class="statbox widget box box-shadow mb-3">
                        <div class="widget-content widget-content-area p-4">
                            <h6>Retry</h6>
                            <p class="text-muted mb-3">
                                StroWallet did not accept this filing. Nothing was lost — retry once the
                                problem in the message above is dealt with.
                            </p>
                            <form method="POST" action="{{ route('admin.card_disputes.retry', $dispute->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-warning w-100">Retry with StroWallet</button>
                            </form>
                        </div>
                    </div>
                @endif

                <div class="statbox widget box box-shadow mb-3">
                    <div class="widget-content widget-content-area p-4">
                        <h6>Record the outcome</h6>
                        <p class="text-muted">Update this once StroWallet resolves the chargeback.</p>

                        <form method="POST" action="{{ route('admin.card_disputes.status', $dispute->id) }}">
                            @csrf

                            <label class="form-label" for="status">Status</label>
                            <select name="status" id="status" class="form-select mb-3">
                                @foreach(['pending' => 'Pending', 'submitted' => 'Submitted', 'failed' => 'Failed', 'won' => 'Won (customer refunded)', 'lost' => 'Lost', 'withdrawn' => 'Withdrawn'] as $key => $label)
                                    <option value="{{ $key }}" {{ $dispute->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>

                            <label class="form-label" for="admin_note">Internal note</label>
                            <textarea name="admin_note" id="admin_note" rows="3" class="form-control mb-3">{{ $dispute->admin_note }}</textarea>

                            <button type="submit" class="btn btn-primary w-100">Save</button>
                        </form>
                    </div>
                </div>

                @can('delete_card_disputes')
                <div class="statbox widget box box-shadow">
                    <div class="widget-content widget-content-area p-4">
                        <h6>Delete</h6>
                        <p class="text-muted">Removes the local record only. It does not withdraw a dispute already filed with StroWallet.</p>
                        <form method="POST" action="{{ route('admin.card_disputes.destroy', $dispute->id) }}"
                              onsubmit="return confirm('Delete this dispute record?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100">Delete record</button>
                        </form>
                    </div>
                </div>
                @endcan

            </div>
        </div>

        <a href="{{ route('admin.card_disputes.index') }}" class="btn btn-secondary mb-4">Back to disputes</a>

    </div>
</div>
@endsection
