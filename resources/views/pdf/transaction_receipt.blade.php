<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Transaction Receipt' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #222;
            background: #fff;
            margin: 0;
            padding: 24px;
            font-size: 13px;
            line-height: 1.5;
        }
        .receipt-box {
            max-width: 520px;
            margin: 0 auto;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 28px;
            background: #ffffff;
        }
        .header {
            text-align: center;
            padding-bottom: 16px;
            border-bottom: 2px dashed #d1d5db;
        }
        .brand-name {
            font-size: 24px;
            font-weight: 800;
            color: #34544A;
            letter-spacing: 1px;
            margin: 0 0 4px 0;
        }
        .receipt-title {
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .amount-section {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px dashed #e5e7eb;
        }
        .amount-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .amount-val {
            font-size: 28px;
            font-weight: 800;
            color: #111827;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .details-table td {
            padding: 9px 0;
            vertical-align: top;
            border-bottom: 1px solid #f3f4f6;
        }
        .details-table tr:last-child td {
            border-bottom: none;
        }
        .label {
            font-weight: 600;
            color: #4b5563;
            width: 40%;
        }
        .value {
            text-align: right;
            color: #111827;
            font-weight: 500;
            word-break: break-all;
        }
        .value.bold {
            font-weight: 700;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-failed  { background: #fee2e2; color: #b91c1c; }
        .badge-pending { background: #fef3c7; color: #b45309; }
        .badge-refund  { background: #dbeafe; color: #1d4ed8; }
        .footer {
            margin-top: 24px;
            padding-top: 14px;
            border-top: 1px dashed #d1d5db;
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
@php
    $tx = $transaction ?? $data ?? null;
    $status = strtolower($tx->status ?? 'pending');
    $badgeClass = ($status === 'success' || $status === 'successful' || $status === '1')
        ? 'badge-success'
        : (($status === 'failed' || $status === 'unsuccessful' || $status === '0')
            ? 'badge-failed'
            : ($status === 'refund' ? 'badge-refund' : 'badge-pending'));
    $siteTitle = function_exists('getSiteSettings') ? (getSiteSettings('title') ?: 'DUPAY') : 'DUPAY';
@endphp
<div class="receipt-box">
    <div class="header">
        <h1 class="brand-name">{{ $siteTitle }}</h1>
        <p class="receipt-title">Transaction Receipt</p>
    </div>

    @if($tx)
    <div class="amount-section">
        <div class="amount-label">Amount Transacted</div>
        <div class="amount-val">
            @if(!str_contains($tx->amount, '₦') && !str_contains($tx->amount, '$') && !str_contains($tx->amount, '€'))
                ₦{{ number_format((float)str_replace(',', '', $tx->amount), 2) }}
            @else
                {{ $tx->amount }}
            @endif
            @if($tx->currency && !str_contains($tx->amount, $tx->currency))
                <span style="font-size: 16px; color: #6b7280;">{{ $tx->currency }}</span>
            @endif
        </div>
    </div>

    <table class="details-table">
        @if($tx->reference)
        <tr>
            <td class="label">Reference</td>
            <td class="value bold">{{ $tx->reference }}</td>
        </tr>
        @endif
        @if(!empty($tx->session_id))
        <tr>
            <td class="label">Session ID</td>
            <td class="value bold">{{ $tx->session_id }}</td>
        </tr>
        @endif
        @if(!empty($tx->service_type) || !empty($tx->service))
        <tr>
            <td class="label">Service</td>
            <td class="value">{{ $tx->service_type ?: $tx->service }}</td>
        </tr>
        @endif
        @if(!empty($tx->description))
        <tr>
            <td class="label">Description</td>
            <td class="value">{{ $tx->description }}</td>
        </tr>
        @endif
        @if(!empty($tx->phone_number))
        <tr>
            <td class="label">Phone / Beneficiary</td>
            <td class="value">{{ $tx->phone_number }}</td>
        </tr>
        @endif
        @if(isset($tx->user) && $tx->user)
        <tr>
            <td class="label">Customer</td>
            <td class="value">{{ $tx->user->name ?? $tx->user->username ?? 'User' }}</td>
        </tr>
        @endif
        @if(!empty($tx->initial_balance))
        <tr>
            <td class="label">Initial Balance</td>
            <td class="value">₦{{ number_format((float)str_replace(',', '', $tx->initial_balance), 2) }}</td>
        </tr>
        @endif
        @if(!empty($tx->final_balance))
        <tr>
            <td class="label">Final Balance</td>
            <td class="value">₦{{ number_format((float)str_replace(',', '', $tx->final_balance), 2) }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Status</td>
            <td class="value">
                <span class="badge {{ $badgeClass }}">{{ ucfirst($tx->status) }}</span>
            </td>
        </tr>
        <tr>
            <td class="label">Date &amp; Time</td>
            <td class="value bold">
                @if($tx->created_at)
                    {{ \Carbon\Carbon::parse($tx->created_at)->format('d M, Y h:i A') }}
                @else
                    {{ date('d M, Y h:i A') }}
                @endif
            </td>
        </tr>
    </table>
    @endif

    <div class="footer">
        <p>Generated automatically by {{ $siteTitle }}. For support, contact support@mydupay.com.</p>
    </div>
</div>
</body>
</html>
