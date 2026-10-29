@extends('admin.layouts.app')

@section('style')
<style>
    .dispute-picker-row { cursor: pointer; }
    .dispute-picker-row.selected { background: rgba(79, 142, 247, 0.15); }
    .dispute-step-label {
        font-size: 12px; text-transform: uppercase; letter-spacing: .08em;
        opacity: .7; margin-bottom: 6px;
    }
</style>
@endsection

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

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.card_disputes.store') }}" id="disputeForm">
            @csrf

            <div class="row layout-spacing layout-top-spacing">

                <!-- STEP 1: pick the card -->
                <div class="col-lg-5">
                    <div class="statbox widget box box-shadow">
                        <div class="widget-content widget-content-area p-3">
                            <div class="dispute-step-label">Step 1</div>
                            <h5>Find the card</h5>

                            <div class="input-group my-3">
                                <input type="text" id="cardSearch" class="form-control"
                                       placeholder="Username, name, email or masked PAN">
                                <button class="btn btn-primary" type="button" id="cardSearchBtn">Search</button>
                            </div>

                            <div id="cardResults" class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                <p class="text-muted mb-0">Search to list physical cards.</p>
                            </div>

                            <input type="hidden" name="physical_card_id" id="physical_card_id" value="{{ old('physical_card_id') }}">

                            <div id="selectedCard" class="alert alert-info mt-3 d-none"></div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: pick the transaction -->
                <div class="col-lg-7">
                    <div class="statbox widget box box-shadow">
                        <div class="widget-content widget-content-area p-3">
                            <div class="dispute-step-label">Step 2</div>
                            <h5>Pick the disputed transaction</h5>

                            <div id="txnMessage" class="alert alert-warning mt-3 d-none"></div>

                            <div id="txnResults" class="table-responsive mt-3" style="max-height: 320px; overflow-y: auto;">
                                <p class="text-muted mb-0">Select a card first.</p>
                            </div>

                            <hr>

                            <label class="form-label" for="transaction_id">
                                Transaction ID <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="transaction_id" id="transaction_id"
                                   class="form-control" value="{{ old('transaction_id') }}"
                                   placeholder="Click a transaction above, or type the reference">
                            <small class="text-muted">
                                Sent to StroWallet as <code>transactionId</code>. You can type it by hand if the
                                history does not load.
                            </small>

                            <input type="hidden" name="amount" id="amount" value="{{ old('amount') }}">
                            <input type="hidden" name="transaction_date" id="transaction_date" value="{{ old('transaction_date') }}">
                            <input type="hidden" name="location" id="location" value="{{ old('location') }}">
                        </div>
                    </div>
                </div>

                <!-- STEP 3: reason -->
                <div class="col-lg-12 mt-3">
                    <div class="statbox widget box box-shadow">
                        <div class="widget-content widget-content-area p-3">
                            <div class="dispute-step-label">Step 3</div>
                            <h5>Describe the problem</h5>

                            <div class="row mt-3">
                                <div class="col-md-5">
                                    <label class="form-label" for="reason">
                                        Reason <span class="text-danger">*</span>
                                    </label>
                                    <select name="reason" id="reason" class="form-select">
                                        <option value="">Select a reason</option>
                                        @foreach($reasons as $key => $label)
                                            <option value="{{ $key }}" {{ old('reason') === $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">These are the only reasons StroWallet accepts.</small>
                                </div>

                                <div class="col-md-7">
                                    <label class="form-label" for="explanation">
                                        Explanation <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="explanation" id="explanation" rows="4" class="form-control"
                                              placeholder="What happened? Include ATM location, time, and whether the customer was debited without receiving cash.">{{ old('explanation') }}</textarea>
                                    <small class="text-muted">Goes to StroWallet as <code>explanation</code>. Minimum 10 characters.</small>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <label class="form-label" for="admin_note">Internal note (not sent to StroWallet)</label>
                                    <textarea name="admin_note" id="admin_note" rows="2" class="form-control">{{ old('admin_note') }}</textarea>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary" id="submitDispute">
                                    File dispute with StroWallet
                                </button>
                                <a href="{{ route('admin.card_disputes.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>
</div>
@endsection

@section('scripts')
<script>
var searchCardsUrl = "{{ route('admin.card_disputes.cards') }}";
var txnUrlTemplate = "{{ route('admin.card_disputes.transactions', ['physicalCardId' => 'PLACEHOLDER']) }}";

function escapeHtml(value) {
    return $('<div>').text(value == null ? '' : value).html();
}

// ---------- Step 1: card search ----------
function searchCards() {
    var term = $('#cardSearch').val();

    $('#cardResults').html('<p class="text-muted mb-0">Searching...</p>');

    $.get(searchCardsUrl, { q: term })
        .done(function (res) {
            var rows = res.data || [];

            if (!rows.length) {
                $('#cardResults').html('<p class="text-muted mb-0">No physical cards matched that search.</p>');
                return;
            }

            var html = '<table class="table table-sm table-hover mb-0"><tbody>';

            rows.forEach(function (card) {
                html += '<tr class="dispute-picker-row card-row"'
                     +  ' data-id="' + card.id + '"'
                     +  ' data-pan="' + escapeHtml(card.masked_pan) + '"'
                     +  ' data-user="' + escapeHtml(card.user) + '">'
                     +  '<td><strong>' + escapeHtml(card.user) + '</strong><br>'
                     +  '<small class="text-muted">' + escapeHtml(card.email) + '</small></td>'
                     +  '<td>' + escapeHtml(card.masked_pan) + '<br>'
                     +  '<small class="text-muted">' + escapeHtml(card.brand) + ' · ' + escapeHtml(card.status) + '</small></td>'
                     +  '</tr>';
            });

            html += '</tbody></table>';
            $('#cardResults').html(html);
        })
        .fail(function () {
            $('#cardResults').html('<p class="text-danger mb-0">Could not load cards. Try again.</p>');
        });
}

$('#cardSearchBtn').on('click', searchCards);

$('#cardSearch').on('keypress', function (e) {
    if (e.which === 13) {
        e.preventDefault();
        searchCards();
    }
});

// ---------- Step 2: load that card's ATM history ----------
$(document).on('click', '.card-row', function () {
    var $row = $(this);

    $('.card-row').removeClass('selected');
    $row.addClass('selected');

    var cardId = $row.data('id');
    $('#physical_card_id').val(cardId);

    $('#selectedCard')
        .removeClass('d-none')
        .html('<strong>Selected:</strong> ' + escapeHtml($row.data('pan')) + ' — ' + escapeHtml($row.data('user')));

    $('#txnMessage').addClass('d-none').text('');
    $('#txnResults').html('<p class="text-muted mb-0">Loading transactions...</p>');

    $.get(txnUrlTemplate.replace('PLACEHOLDER', cardId))
        .done(function (res) {
            if (res.message) {
                $('#txnMessage').removeClass('d-none').text(res.message);
            }

            var rows = res.data || [];

            if (!rows.length) {
                $('#txnResults').html('<p class="text-muted mb-0">No transactions returned. Enter the transaction ID manually below.</p>');
                return;
            }

            var html = '<table class="table table-sm table-hover mb-0">'
                     + '<thead><tr><th>Date</th><th>Amount</th><th>Location</th><th>Reference</th></tr></thead><tbody>';

            rows.forEach(function (t) {
                // data-amount carries the NORMALISED value (no thousands
                // separator) because it feeds a field validated as numeric.
                // The cell shows the provider's formatted string.
                html += '<tr class="dispute-picker-row txn-row"'
                     +  ' data-txn="' + escapeHtml(t.transaction_id) + '"'
                     +  ' data-amount="' + escapeHtml(t.amount) + '"'
                     +  ' data-date="' + escapeHtml(t.date) + '"'
                     +  ' data-location="' + escapeHtml(t.location) + '">'
                     +  '<td>' + escapeHtml(t.date) + '</td>'
                     +  '<td>' + escapeHtml(t.amount_display || t.amount) + '</td>'
                     +  '<td>' + escapeHtml(t.location) + '</td>'
                     +  '<td><code>' + escapeHtml(t.transaction_id) + '</code></td>'
                     +  '</tr>';
            });

            html += '</tbody></table>';
            $('#txnResults').html(html);
        })
        .fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Could not load this card\'s history.';
            $('#txnMessage').removeClass('d-none').text(msg);
            $('#txnResults').html('<p class="text-muted mb-0">Enter the transaction ID manually below.</p>');
        });
});

// ---------- Pick a transaction ----------
$(document).on('click', '.txn-row', function () {
    var $row = $(this);

    $('.txn-row').removeClass('selected');
    $row.addClass('selected');

    $('#transaction_id').val($row.data('txn'));
    $('#amount').val($row.data('amount'));
    $('#transaction_date').val($row.data('date'));
    $('#location').val($row.data('location'));
});

// ---------- Guard the submit ----------
$('#disputeForm').on('submit', function (e) {
    if (!$('#physical_card_id').val()) {
        e.preventDefault();
        alert('Select the card this dispute belongs to.');
        return;
    }

    if (!$('#transaction_id').val().trim()) {
        e.preventDefault();
        alert('Pick a transaction or enter its ID.');
        return;
    }

    if (!$('#reason').val()) {
        e.preventDefault();
        alert('Select a reason.');
        return;
    }

    $('#submitDispute').prop('disabled', true).text('Filing...');
});

// Load an initial page of cards so the screen isn't empty.
$(document).ready(searchCards);
</script>
@endsection