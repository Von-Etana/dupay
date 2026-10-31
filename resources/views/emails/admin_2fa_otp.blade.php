<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dupay Admin 2FA Code</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f6f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #334155; line-height: 1.5; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f4f6f8; padding: 30px 0; }
        .main-card { width: 94%; max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); }
        .header { background: #0f172a; padding: 24px 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 18px; color: #ffffff; letter-spacing: 0.5px; text-transform: uppercase; font-weight: 700; }
        .content { padding: 30px; text-align: center; }
        .greeting { font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 8px; }
        .intro { font-size: 14px; color: #64748b; margin-bottom: 24px; }
        .otp-box { background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 8px; padding: 20px; margin: 0 auto 24px auto; max-width: 300px; }
        .otp-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-weight: 600; margin-bottom: 6px; }
        .otp-code { font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #0f172a; font-family: monospace; }
        .meta-text { font-size: 13px; color: #64748b; line-height: 1.6; margin-bottom: 20px; }
        .warning-box { background: #fffbeb; border: 1px solid #fef3c7; border-radius: 6px; padding: 12px 16px; font-size: 12px; color: #92400e; text-align: left; }
        .footer { background: #f8fafc; padding: 20px 30px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
        .footer p { margin: 4px 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main-card">
            <div class="header">
                <h1>Dupay Admin Portal</h1>
            </div>

            <div class="content">
                <div class="greeting">Hello, {{ $admin->username ?? 'Admin' }}</div>
                <div class="intro">Use the verification code below to complete your Two-Factor Authentication sign-in.</div>

                <div class="otp-box">
                    <div class="otp-label">Your Security Code</div>
                    <div class="otp-code">{{ $otp }}</div>
                </div>

                <div class="meta-text">
                    This single-use code is valid for <strong>{{ $expires_in }}</strong>.<br>
                    Do not share this code with anyone.
                </div>

                <div class="warning-box">
                    <strong>Security Notice:</strong> If you did not initiate this login request, your admin credentials may be compromised. Please change your password immediately and notify the system administrator.
                </div>
            </div>

            <div class="footer">
                <p>This is an automated security email from the Dupay administrative system.</p>
                <p>&copy; {{ date('Y') }} Dupay. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
