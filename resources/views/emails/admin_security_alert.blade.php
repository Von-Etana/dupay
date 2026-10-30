<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dupay Security Alert</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f6f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #334155; line-height: 1.5; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f4f6f8; padding: 30px 0; }
        .main-card { width: 94%; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); }
        .header { background: #0f172a; padding: 24px 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 18px; color: #ffffff; letter-spacing: 0.5px; text-transform: uppercase; font-weight: 700; }
        .badge { display: inline-block; padding: 6px 14px; font-size: 12px; font-weight: 700; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 10px; }
        .badge-critical { background: #fee2e2; color: #991b1b; }
        .badge-high { background: #ffedd5; color: #9a3412; }
        .badge-medium { background: #e0f2fe; color: #075985; }
        .badge-low { background: #f1f5f9; color: #475569; }
        .content { padding: 30px; }
        .alert-box { background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 4px; padding: 16px 20px; margin-bottom: 24px; }
        .alert-box.high { background: #fffbeb; border-left-color: #f59e0b; }
        .alert-box.medium { background: #f0f9ff; border-left-color: #0284c7; }
        .alert-title { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0; }
        .alert-message { font-size: 14px; color: #475569; margin: 0; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .data-table td { padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .data-table td.label { color: #64748b; width: 38%; font-weight: 500; }
        .data-table td.value { color: #0f172a; font-weight: 600; text-align: right; word-break: break-all; }
        .user-tag { background: #eff6ff; color: #1d4ed8; padding: 3px 8px; border-radius: 4px; font-weight: 700; display: inline-block; }
        .btn { display: inline-block; background: #0f172a; color: #ffffff !important; font-weight: 600; font-size: 14px; padding: 12px 24px; text-decoration: none; border-radius: 6px; text-align: center; }
        .btn-wrapper { text-align: center; margin: 25px 0 10px 0; }
        .footer { background: #f8fafc; padding: 20px 30px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
        .footer p { margin: 4px 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main-card">
            <div class="header">
                <h1>Dupay Security Alert</h1>
                @php
                    $sev = strtolower($severity);
                    $badgeClass = $sev === 'critical' ? 'badge-critical' : ($sev === 'high' ? 'badge-high' : ($sev === 'medium' ? 'badge-medium' : 'badge-low'));
                @endphp
                <span class="badge {{ $badgeClass }}">
                    {{ $severity }} SEVERITY
                </span>
            </div>

            <div class="content">
                <div class="alert-box {{ $sev }}">
                    <div class="alert-title">{{ $title }}</div>
                    <p class="alert-message">{{ $message_text }}</p>
                </div>

                <table class="data-table">
                    @if(!empty($display_username))
                    <tr>
                        <td class="label">Target User</td>
                        <td class="value">
                            <span class="user-tag">{{ $display_username }}</span>
                            @if(!empty($user_name))
                                <div style="font-size: 12px; color: #64748b; font-weight: 400; margin-top: 2px;">{{ $user_name }}</div>
                            @endif
                        </td>
                    </tr>
                    @endif
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
                        <td class="label">Alert Type</td>
                        <td class="value"><code style="font-family: monospace; font-size: 13px;">{{ $type }}</code></td>
                    </tr>
                    <tr>
                        <td class="label">Severity</td>
                        <td class="value">{{ $severity }}</td>
                    </tr>
                    <tr>
                        <td class="label">Actor / Admin</td>
                        <td class="value">{{ !empty($admin_id) ? 'Admin #' . $admin_id : 'System / Guest' }}</td>
                    </tr>
                    <tr>
                        <td class="label">IP Address</td>
                        <td class="value"><code style="font-family: monospace; font-size: 13px;">{{ $ip_address ?? 'Unknown' }}</code></td>
                    </tr>
                    <tr>
                        <td class="label">Time</td>
                        <td class="value">{{ $time }}</td>
                    </tr>
                </table>

                <div class="btn-wrapper">
                    @if(!empty($user_url))
                        <a href="{{ $user_url }}" class="btn" style="background: #2563eb; margin-right: 8px;" target="_blank">
                            View User Profile &rarr;
                        </a>
                    @endif
                    <a href="{{ url('/adminoffice/security-alerts') }}" class="btn" target="_blank">
                        Security Alerts Log &rarr;
                    </a>
                </div>
            </div>

            <div class="footer">
                <p>This is an automated operational security notification sent by the Dupay platform.</p>
                <p>&copy; {{ date('Y') }} Dupay. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
