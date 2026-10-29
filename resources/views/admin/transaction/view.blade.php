@extends('admin.layouts.app')

@section('style')
<style>
/* Mobile-app style receipt */
.rcpt-wrap { position:absolute; left:-99999px; top:0; width:480px; }  /* off-screen: capture source only, no visible preview */
.rcpt-card { background:#fff; border-radius:16px; padding:22px 18px; box-shadow:0 6px 24px rgba(0,0,0,.08); border:1px solid #eee; }
.rcpt-title { text-align:center; font-weight:700; font-size:16px; color:#1a1a1a; font-family:'Inter',Arial,sans-serif; }
.rcpt-logo { width:70px; height:70px; border-radius:50%; background:#f1f1f1; display:flex; align-items:center; justify-content:center; margin:12px auto; overflow:hidden; }
.rcpt-logo img { width:46px; height:46px; object-fit:contain; }
.rcpt-dash { border:0; border-top:1px dashed #cfcfcf; margin:10px 0; }
.rcpt-row { width:100%; border-collapse:collapse; font-family:'Inter',Arial,sans-serif; }
.rcpt-row td { padding:6px 0; vertical-align:top; }
.rcpt-row td.k { font-weight:600; color:#222; font-size:14px; white-space:nowrap; text-align:left; width:38%; }
.rcpt-row td.v { font-weight:500; color:#222; font-size:13.5px; text-align:right; word-break:break-word; }
.rcpt-row.bold td.k, .rcpt-row.bold td.v { font-weight:700; }
.rcpt-card { width:440px; }
.rcpt-badge { padding:4px 14px; border-radius:8px; font-weight:700; font-size:13px; text-transform:capitalize; }
.rcpt-badge.success { background:rgba(29,191,115,.12); color:#1DBF73; }
.rcpt-badge.fail { background:rgba(244,67,54,.12); color:#e53935; }
.rcpt-badge.refund { background:rgba(33,150,243,.12); color:#2196F3; }
.rcpt-badge.pending { background:rgba(255,152,0,.12); color:#fb8c00; }
.rcpt-dl-btn { display:inline-flex; align-items:center; gap:8px; margin:16px auto 0; }
</style>
@endsection

@section('content')

<div class="layout-px-spacing">
    <div class="middle-content container-xxl p-0">

        <!-- Breadcrumb -->
        <div class="page-meta mb-4">
            <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    @foreach ($breadcrumb as $nav)
                        @if ($nav['url'])
                            <li class="breadcrumb-item">
                                <a href="{{ $nav['url'] }}">{{ $nav['title'] }}</a>
                            </li>
                        @else
                            <li class="breadcrumb-item active" aria-current="page">{{ $nav['title'] }}</li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        </div>

        <!-- Transaction Header Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="transaction-icon bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" 
                             style="width: 60px; height: 60px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="white" viewBox="0 0 24 24">
                                <path d="M17 3H7a2 2 0 0 0-2 2v16l3-3 3 3 3-3 3 3 3-3V5a2 2 0 0 0-2-2zm0 14H7V5h10v12z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold">Transaction Details</h4>
                            <p class="text-muted mb-0">Reference: <strong>{{ $transaction->reference }}</strong></p>
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="d-flex flex-column flex-md-row justify-content-md-end align-items-md-center gap-2">
                            <div>
                                @php $status = strtolower($transaction->status); @endphp
                                @if ($status === 'success')
                                    <span class="badge bg-success px-3 py-2 fs-6">Success</span>
                                @elseif ($status === 'failed')
                                    <span class="badge bg-danger px-3 py-2 fs-6">Failed</span>
                                @else
                                    <span class="badge bg-warning text-dark px-3 py-2 fs-6">{{ ucfirst($transaction->status) }}</span>
                                @endif
                            </div>
                            <div class="text-muted">
                                {{ $transaction->created_at->format('d M, Y h:i A') }}
                            </div>
                            <button type="button" id="downloadReceiptBtn" class="btn btn-primary btn-sm mt-2">
                                <i class="fa fa-download me-1"></i> Download Receipt
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Transaction Info -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0"><i class="fa fa-info-circle me-2 text-primary"></i>Transaction Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">

                            <!-- User & Wallet -->
                            @if($transaction->user_id || $transaction->wallet_id)
                            <div class="col-md-6">
                                <div class="info-card">
                                    <h6 class="info-title"><i class="fa fa-user me-2 text-primary"></i>User Info</h6>
                                    <div class="info-content">
                                        @if($transaction->user_id)
                                            <div class="info-item"><span>User:</span><span>{{ $transaction->user->profile->first_name.' '.$transaction->user->profile->last_name }}</span></div>
                                        @endif
                                        @if($transaction->wallet_id)
                                            <div class="info-item"><span>Wallet ID:</span><span>{{ $transaction->wallet_id }}</span></div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Service Info -->
                            @if($transaction->service || $transaction->service_type || $transaction->service_plan_id || $transaction->phone_number)
                            <div class="col-md-6">
                                <div class="info-card">
                                    <h6 class="info-title"><i class="fa fa-cogs me-2 text-primary"></i>Service Info</h6>
                                    <div class="info-content">
                                        @if($transaction->service)<div class="info-item"><span>Service:</span><span>{{ $transaction->service }}</span></div>@endif
                                        @if($transaction->service_type)<div class="info-item"><span>Type:</span><span>{{ $transaction->service_type }}</span></div>@endif
                                        @if($transaction->service_plan_id)<div class="info-item"><span>Plan ID:</span><span>{{ $transaction->service_plan_id }}</span></div>@endif
                                        @if($transaction->phone_number)<div class="info-item"><span>Phone:</span><span>{{ $transaction->phone_number }}</span></div>@endif
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Financial -->
                            <div class="col-12">
                                <div class="row g-2 align-items-stretch">
                                    @if($transaction->amount)
                                    <div class="col-6 col-md">
                                        <div class="amount-card bg-primary bg-opacity-10 rounded text-center">
                                            <p class="amount-label text-white">Amount</p>
                                            <div class="amount-value text-white">@if(!is_null($transaction->getRawOriginal('amount'))) {{ $transaction->amount }} @endif</div>
                                        </div>
                                    </div>
                                    @endif

                                    @if($transaction->charge)
                                    <div class="col-6 col-md">
                                        <div class="amount-card bg-warning bg-opacity-10 rounded text-center">
                                            <p class="amount-label text-white">Charge</p>
                                            <div class="amount-value text-white">@if(!is_null($transaction->getRawOriginal('charge'))) {{ $transaction->charge }} @endif</div>
                                        </div>
                                    </div>
                                    @endif

                                    @if($transaction->real_price)
                                    <div class="col-6 col-md">
                                        <div class="amount-card bg-success bg-opacity-10 rounded text-center">
                                            <p class="amount-label text-white">Real Price</p>
                                            @php
                                                $rawAmount = $transaction->getRawOriginal('amount');
                                                $rawCharge = $transaction->getRawOriginal('charge');
                                                $realPrice = $rawAmount + $rawCharge;
                                            @endphp
                                            <div class="amount-value text-white">{{ number_format($realPrice, 2) }}</div>
                                        </div>
                                    </div>

                                    @if($transaction->vat)
                                    <div class="col-6 col-md">
                                        <div class="amount-card bg-primary bg-opacity-10 rounded text-center">
                                            <p class="amount-label text-white">VAT</p>
                                            <div class="amount-value text-white">@if(!is_null($transaction->vat)) {{ '₦' . $transaction->vat }} @endif</div>
                                        </div>
                                    </div>
                                    @endif

                                    @endif
                                </div>
                            </div>

                            <!-- Balance -->
                            @if($transaction->initial_balance || $transaction->final_balance)
                            <div class="col-md-6">
                                <div class="info-card">
                                    <h6 class="info-title"><i class="fa fa-wallet me-2 text-primary"></i>Balance Info</h6>
                                    <div class="info-content">
                                        @if($transaction->initial_balance)<div class="info-item"><span>Initial:</span><span>@if(!is_null($transaction->getRawOriginal('initial_balance'))) {{ $transaction->initial_balance }} @endif</span></div>@endif
                                        @if($transaction->final_balance)<div class="info-item"><span>Final:</span><span>@if(!is_null($transaction->getRawOriginal('final_balance'))) {{ $transaction->final_balance }} @endif</span></div>@endif
                                        @if($transaction->currency)<div class="info-item"><span>Currency:</span><span>{{ $transaction->currency }}</span></div>@endif
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Sender -->
                            @if($transaction->sender_bank_name || $transaction->sender_account_name || $transaction->sender_account_number)
                            <div class="col-md-6">
                                <div class="info-card">
                                    <h6 class="info-title"><i class="fa fa-user-tie me-2 text-primary"></i>Sender Info</h6>
                                    <div class="info-content">
                                        @if($transaction->sender_bank_name)<div class="info-item"><span>Bank:</span><span>{{ $transaction->sender_bank_name }}</span></div>@endif
                                        @if($transaction->sender_account_name)<div class="info-item"><span>Name:</span><span>{{ $transaction->sender_account_name }}</span></div>@endif
                                        @if($transaction->sender_account_number)<div class="info-item"><span>Account:</span><span>{{ $transaction->sender_account_number }}</span></div>@endif
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            <!-- Receiver -->
                            @if($transaction->receiver_bank_name || $transaction->receiver_account_name || $transaction->receiver_account_number)
                            <div class="col-md-6">
                                <div class="info-card">
                                    <h6 class="info-title"><i class="fa fa-user-tie me-2 text-primary"></i>Receiver Info</h6>
                                    <div class="info-content">
                                        @if($transaction->receiver_bank_name)<div class="info-item"><span>Bank:</span><span>{{ $transaction->receiver_bank_name }}</span></div>@endif
                                        @if($transaction->receiver_account_name)<div class="info-item"><span>Name:</span><span>{{ $transaction->receiver_account_name }}</span></div>@endif
                                        @if($transaction->receiver_account_number)<div class="info-item"><span>Account:</span><span>{{ $transaction->receiver_account_number }}</span></div>@endif
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0"><i class="fa fa-history me-2 text-primary"></i>Timeline</h5>
                    </div>
                    <div class="card-body p-4">
                        <ul class="timeline list-unstyled">
                            <li class="mb-3">
                                <strong>Created:</strong> <br>
                                <small class="text-muted">{{ $transaction->created_at->format('d M, Y h:i A') }}</small>
                            </li>
                            @if($transaction->updated_at && $transaction->updated_at != $transaction->created_at)
                            <li>
                                <strong>Last Updated:</strong> <br>
                                <small class="text-muted">{{ $transaction->updated_at->format('d M, Y h:i A') }}</small>
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>

                @if($transaction->message)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0"><i class="fa fa-comment me-2 text-primary"></i>Message</h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="mb-0">{{ $transaction->message }}</p>
                    </div>
                </div>
                @endif

                @if($transaction->description)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0"><i class="fa fa-align-left me-2 text-primary"></i>Description</h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="mb-0">{{ $transaction->description }}</p>
                    </div>
                </div>
                @endif
                
                @if($transaction->session_id)
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0"><i class="fa fa-align-left me-2 text-primary"></i>Session ID</h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="mb-0">{{ $transaction->session_id }}</p>
                    </div>
                </div>
                @endif
                
                @if($transaction->api_reference)
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0"><i class="fa fa-align-left me-2 text-primary"></i>Reference</h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="mb-0">{{ $transaction->api_reference }}</p>
                    </div>
                </div>
                @endif
                
            </div>
            
            @php
                $merchant = $transaction->merchant ? json_decode($transaction->merchant, true) : null;
            @endphp
            
            @if(!empty($merchant))
            <div class="col-md-6">
                <div class="info-card">
                    <h6 class="info-title">
                        <i class="fa fa-store me-2 text-primary"></i>Merchant Info
                    </h6>
                    <div class="info-content">
            
                        @if(!empty($merchant['name']))
                            <div class="info-item">
                                <span>Merchant Name:</span>
                                <span>{{ $merchant['name'] }}</span>
                            </div>
                        @endif
            
                        @if(!empty($merchant['merchantId']))
                            <div class="info-item">
                                <span>Merchant ID:</span>
                                <span>{{ $merchant['merchantId'] }}</span>
                            </div>
                        @endif
            
                        @if(!empty($merchant['category']))
                            <div class="info-item">
                                <span>Category:</span>
                                <span>{{ $merchant['category'] }}</span>
                            </div>
                        @endif
            
                        @if(!empty($merchant['city']) || !empty($merchant['state']) || !empty($merchant['country']))
                            <div class="info-item">
                                <span>Location:</span>
                                <span>
                                    {{ $merchant['city'] ?? '' }}
                                    {{ isset($merchant['state']) ? ', '.$merchant['state'] : '' }}
                                    {{ isset($merchant['country']) ? ' ('.$merchant['country'].')' : '' }}
                                </span>
                            </div>
                        @endif
            
                    </div>
                </div>
            </div>
            @endif

        </div>

        <div class="mt-4">
            <a href="{{ route('admin.showTransaction') }}" class="btn btn-outline-primary">
                <i class="fa fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>
</div>

<style>
    .info-card { margin-bottom: 1.2rem; }
    .info-title { border-bottom: 1px solid #eee; padding-bottom: .5rem; font-weight: 600; }
    .info-item { display: flex; justify-content: space-between; padding: .4rem 0; border-bottom: 1px dashed #f0f0f0; }
    .info-item:last-child { border-bottom: none; }
    .timeline { border-left: 3px solid #e0e0e0; padding-left: 1rem; }

    /* Financial summary cards: smaller text + stretch to fill the widget */
    .amount-card {
        display: flex;
        flex-direction: column;
        justify-content: center;
        height: 100%;
        min-height: 84px;
        padding: .65rem .5rem;
    }
    .amount-card .amount-label {
        font-size: .72rem;
        letter-spacing: .02em;
        text-transform: uppercase;
        margin-bottom: .2rem;
        opacity: .85;
    }
    .amount-card .amount-value {
        font-size: .95rem;
        font-weight: 700;
        line-height: 1.25;
        margin: 0;
        word-break: break-word;
    }
    @media (min-width: 768px) {
        .amount-card .amount-value { font-size: 1.02rem; }
    }
</style>
<div class="rcpt-wrap">
  <div class="rcpt-card" id="receiptCard">
    <div class="rcpt-title">Transaction Receipt</div>
    <div class="rcpt-logo">
      @if(getSiteSettings('logo'))<img src="{{ asset(getSiteSettings('logo')) }}" alt="logo" crossorigin="anonymous">@endif
    </div>
    <hr class="rcpt-dash">
    @if($transaction->reference)
      <table class="rcpt-row bold"><tr><td class="k">Reference</td><td class="v">{{ $transaction->reference }}</td></tr></table>
      <hr class="rcpt-dash">
    @endif
    @if($transaction->session_id)
      <table class="rcpt-row bold"><tr><td class="k">Session ID</td><td class="v">{{ $transaction->session_id }}</td></tr></table>
      <hr class="rcpt-dash">
    @endif
    @if($transaction->service)
      <table class="rcpt-row"><tr><td class="k">Service</td><td class="v">{{ $transaction->service }}</td></tr></table>
      <hr class="rcpt-dash">
    @endif
    @if($transaction->description)
      <table class="rcpt-row"><tr><td class="k">Description</td><td class="v">{{ $transaction->description }}</td></tr></table>
      <hr class="rcpt-dash">
    @endif
    @if($transaction->phone_number)
      <table class="rcpt-row"><tr><td class="k">Phone Number</td><td class="v">{{ $transaction->phone_number }}</td></tr></table>
      <hr class="rcpt-dash">
    @endif
    <table class="rcpt-row"><tr><td class="k">Amount</td><td class="v">{{ $transaction->amount }} {{ $transaction->currency }}</td></tr></table>
    <hr class="rcpt-dash">
    @php $rs = strtolower($transaction->status); @endphp
    @php $cls = $rs === "success" ? "success" : (($rs === "failed" || $rs === "unsuccessful") ? "fail" : ($rs === "refund" ? "refund" : "pending")); @endphp
    <table class="rcpt-row"><tr><td class="k">Status</td><td class="v"><span class="rcpt-badge {{ $cls }}">{{ $transaction->status }}</span></td></tr></table>
    <hr class="rcpt-dash">
    <table class="rcpt-row bold"><tr><td class="k">Date</td><td class="v">{{ $transaction->created_at->format("g:i A - d M, Y") }}</td></tr></table>
  </div>
</div>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
(function () {
  function downloadReceipt() {
    var card = document.getElementById('receiptCard');
    if (!card || typeof html2canvas === 'undefined') { alert('Receipt library not loaded.'); return; }
    html2canvas(card, {
      scale: 3,
      backgroundColor: '#ffffff',
      useCORS: true,
      width: card.offsetWidth,
      height: card.offsetHeight,
      windowWidth: card.offsetWidth,
      scrollX: 0, scrollY: 0
    }).then(function (canvas) {
      var link = document.createElement('a');
      link.download = 'receipt_{{ $transaction->reference ?: $transaction->id }}.png';
      link.href = canvas.toDataURL('image/png');
      document.body.appendChild(link); link.click(); document.body.removeChild(link);
    }).catch(function (e) { console.error('Receipt capture failed:', e); alert('Could not generate the receipt image.'); });
  }
  ['downloadReceiptBtn'].forEach(function (id) {
    var b = document.getElementById(id);
    if (b) b.addEventListener('click', downloadReceipt);
  });
})();
</script>
@endsection