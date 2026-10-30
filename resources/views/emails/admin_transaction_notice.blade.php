<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dupay Admin Transaction Notification</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f6f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #334155; line-height: 1.5; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f4f6f8; padding: 30px 0; }
        .main-card { width: 94%; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); }
        .header { background: #0f172a; padding: 24px 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 18px; color: #ffffff; letter-spacing: 0.5px; text-transform: uppercase; font-weight: 700; }
        .badge { display: inline-block; padding: 6px 14px; font-size: 12px; font-weight: 700; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 10px; }
        .badge-credit { background: #dcfce7; color: #166534; }
        .badge-debit { background: #fef3c7; color: #92400e; }
        .content { padding: 30px; }
        .amount-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; text-align: center; padding: 20px; margin-bottom: 24px; }
        .amount-label { font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 4px; }
        .amount-value { font-size: 28px; font-weight: 800; color: #0f172a; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .data-table td { padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .data-table td.label { color: #64748b; width: 38%; font-weight: 500; }
        .data-table td.value { color: #0f172a; font-weight: 600; text-align: right; word-break: break-all; }
        .user-tag { background: #eff6ff; color: #1d4ed8; padding: 3px 8px; border-radius: 4px; font-weight: 700; display: inline-block; }
        .btn { display: inline-block; background: #2563eb; color: #ffffff !important; font-weight: 600; font-size: 14px; padding: 12px 24px; text-decoration: none; border-radius: 6px; text-align: center; }
        .btn-wrapper { text-align: center; margin: 25px 0 10px 0; }
        .footer { background: #f8fafc; padding: 20px 30px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
        .footer p { margin: 4px 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main-card">
            <div class="header">
                <h1>Dupay Admin Notification</h1>
                <span class="badge {{ strtolower($action) === 'credit' ? 'badge-credit' : 'badge-debit' }}">
                    {{ strtoupper($action) }}
                </span>
            </div>

            <div class="content">
                <div class="amount-box">
                    <div class="amount-label">Transaction Amount</div>
                    <div class="amount-value">{{ $currency }} {{ $amount }}</div>
                </div>

                <table class="data-table">
                    <tr>
                        <td class="label">Target User</td>
                        <td class="value">
                            <span class="user-tag">{{ $display_username }}</span>
                            @if(!empty($user_name))
                                <div style="font-size: 12px; color: #64748b; font-weight: 400; margin-top: 2px;">{{ $user_name }}</div>
                            @endif
                        </td>
                    </tr>
                    @if(!empty($user_email))
                    <tr>
                        <td class="label">User Email</td>
                        <td class="value">{{ $user_email }}</td>
                    </tr>
                    @endif
                    @if(!empty($user_id))
                    <tr>
                        <td class="label">User ID</td>
                        <td class="value">#{{ $user_id }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="label">Action</td>
                        <td class="value">{{ $action }}</td>
                    </tr>
                    <tr>
                        <td class="label">Currency</td>
                        <td class="value">{{ $currency }}</td>
                    </tr>
                    <tr>
                        <td class="label">Reference</td>
                        <td class="value"><code style="font-family: monospace; font-size: 13px;">{{ $reference }}</code></td>
                    </tr>
                    <tr>
                        <td class="label">Initiated By</td>
                        <td class="value">{{ $admin_info }}</td>
                    </tr>
                    <tr>
                        <td class="label">Time</td>
                        <td class="value">{{ $time }}</td>
                    </tr>
                </table>

                @if(!empty($user_url))
                <div class="btn-wrapper">
                    <a href="{{ $user_url }}" class="btn" target="_blank">
                        View User in Admin Panel &rarr;
                    </a>
                </div>
                @endif
            </div>

            <div class="footer">
                <p>This is an automated administrative transaction notice generated by the Dupay system.</p>
                <p>&copy; {{ date('Y') }} Dupay. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
