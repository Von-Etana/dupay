@extends('admin.layouts.app')

@section('content')
<div class="layout-px-spacing">
    <div class="middle-content container-xxl p-0">
        <!-- BREADCRUMB -->
        <div class="page-meta my-4">
            <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);">Security</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Two-Factor Authentication</li>
                </ol>
            </nav>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <strong>Success!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <strong>Error:</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- FRESH RECOVERY CODES DISPLAY --}}
        @if(!empty($freshRecoveryCodes))
            <div class="card border-warning mb-4 shadow-sm">
                <div class="card-header bg-warning-subtle text-dark py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold text-dark">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2 text-warning"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            Save Your Backup Recovery Codes
                        </h5>
                        <small class="text-muted">If you lose access to your phone or authenticator app, these single-use codes are the only way to recover access.</small>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-dark me-2" onclick="copyRecoveryCodes()">Copy All</button>
                        <button class="btn btn-sm btn-dark" onclick="downloadRecoveryCodes()">Download (.txt)</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-2" id="recoveryCodesContainer">
                        @foreach($freshRecoveryCodes as $rCode)
                            <div class="col-md-3 col-sm-6">
                                <div class="p-2 border rounded bg-light text-center font-monospace fw-bold fs-6">
                                    {{ $rCode }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="alert alert-light border mt-3 mb-0 small text-muted">
                        <strong>Important:</strong> Store these codes in a password manager (1Password, Bitwarden, LastPass) or safe physical location. They will not be displayed again.
                    </div>
                </div>
            </div>
        @endif

        <!-- MAIN 2FA CARD -->
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12">
                <div class="widget-content widget-content-area br-8 p-4">
                    
                    <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom flex-wrap gap-2">
                        <div>
                            <h4 class="mb-1 fw-bold">Admin Two-Factor Authentication (2FA)</h4>
                            <p class="text-muted mb-0 small">Protect your administrator privileges by requiring a second verification factor at login.</p>
                        </div>
                        <div>
                            @if($isEnabled)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                    2FA Enabled & Active
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 fs-6">
                                    2FA Disabled
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($isEnabled)
                        {{-- 2FA ACTIVE STATE --}}
                        <div class="row">
                            <div class="col-md-7 mb-4">
                                <div class="card border rounded-3 p-3 h-100">
                                    <h5 class="fw-bold mb-3">Active 2FA Configuration</h5>
                                    
                                    <table class="table table-bordered mb-4">
                                        <tr>
                                            <th class="bg-light text-muted" style="width: 35%;">Method</th>
                                            <td>
                                                @if($admin->two_factor_type === 'email')
                                                    <span class="badge bg-info text-dark">Email Verification OTP</span>
                                                    <div class="text-muted small mt-1">Codes are emailed to: <code>{{ $admin->email }}</code></div>
                                                @else
                                                    <span class="badge bg-primary">Authenticator App (TOTP)</span>
                                                    <div class="text-muted small mt-1">Google Authenticator, Authy, Microsoft Authenticator, etc.</div>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light text-muted">Backup Codes</th>
                                            <td>
                                                <span class="fw-bold text-{{ $remainingRecoveryCodes > 2 ? 'success' : 'danger' }}">
                                                    {{ $remainingRecoveryCodes }} of 8
                                                </span> codes remaining
                                                <div class="mt-2">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#regenerateCodesModal">
                                                        Regenerate Recovery Codes
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light text-muted">Confirmed At</th>
                                            <td>{{ $admin->two_factor_confirmed_at ? $admin->two_factor_confirmed_at->format('d M Y, H:i:s') : 'N/A' }}</td>
                                        </tr>
                                    </table>

                                    <h6 class="fw-bold mb-2">Change Preferred Method</h6>
                                    <form method="POST" action="{{ route('admin.security.2fa.switch') }}" class="d-flex gap-2 mb-3">
                                        @csrf
                                        @if($admin->two_factor_type === 'totp')
                                            <input type="hidden" name="method" value="email">
                                            <button type="submit" class="btn btn-outline-info btn-sm">
                                                Switch to Email Verification OTP
                                            </button>
                                        @else
                                            <input type="hidden" name="method" value="totp">
                                            <button type="submit" class="btn btn-outline-primary btn-sm" @if(empty($admin->two_factor_secret)) disabled title="Configure Authenticator App first" @endif>
                                                Switch to Authenticator App (TOTP)
                                            </button>
                                        @endif
                                    </form>
                                </div>
                            </div>

                            <div class="col-md-5 mb-4">
                                <div class="card border-danger border-opacity-25 rounded-3 p-3 bg-danger-subtle bg-opacity-10 h-100">
                                    <h5 class="fw-bold text-danger mb-2">Disable 2FA</h5>
                                    <p class="text-muted small mb-3">
                                        Disabling Two-Factor Authentication makes your admin account vulnerable to password theft. You will only need your username and password to log in.
                                    </p>
                                    <button type="button" class="btn btn-outline-danger btn-sm mt-auto" data-bs-toggle="modal" data-bs-target="#disable2faModal">
                                        Disable Two-Factor Authentication
                                    </button>
                                </div>
                            </div>
                        </div>

                    @else
                        {{-- 2FA INACTIVE / SETUP STATE --}}
                        <div class="row">
                            <div class="col-lg-7 col-md-12 mb-4">
                                <div class="card border rounded-3 p-4 h-100">
                                    <h5 class="fw-bold mb-1 text-primary">Option 1: Authenticator App (Recommended)</h5>
                                    <p class="text-muted small mb-4">Use Google Authenticator, Microsoft Authenticator, Authy, 1Password, or Apple Keychain.</p>

                                    <div class="d-flex flex-column flex-sm-row gap-4 align-items-center mb-4">
                                        <div class="border p-2 rounded bg-white text-center">
                                            @if(!empty($qrCodeUrl))
                                                <img src="{{ $qrCodeUrl }}" alt="2FA QR Code" width="180" height="180" class="img-fluid rounded">
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <label class="form-label small text-muted text-uppercase fw-bold mb-1">Manual Setup Key</label>
                                            <div class="input-group mb-2">
                                                <input type="text" class="form-control font-monospace fw-bold" id="manualSecretKey" value="{{ chunk_split($tempSecret, 4, ' ') }}" readonly>
                                                <button class="btn btn-outline-secondary" type="button" onclick="copySecretKey()">Copy</button>
                                            </div>
                                            <small class="text-muted d-block">
                                                Account: <strong>{{ $admin->username }}</strong><br>
                                                Issuer: <strong>{{ getSiteSettings('title') ?: 'Dupay' }}</strong>
                                            </small>
                                        </div>
                                    </div>

                                    <div class="border-top pt-3">
                                        <h6 class="fw-bold mb-2">Step 2: Enter 6-digit confirmation code</h6>
                                        <p class="text-muted small mb-3">After scanning the QR code, enter the 6-digit code shown in your authenticator app below to complete setup:</p>

                                        <form method="POST" action="{{ route('admin.security.2fa.confirm') }}">
                                            @csrf
                                            <div class="row g-2 align-items-center">
                                                <div class="col-sm-6">
                                                    <input type="text" name="code" class="form-control text-center font-monospace fw-bold fs-5" placeholder="000 000" maxlength="6" required pattern="\d{6}">
                                                </div>
                                                <div class="col-sm-6">
                                                    <button type="submit" class="btn btn-primary w-100">
                                                        Verify & Enable 2FA
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>

                                </div>
                            </div>

                            <div class="col-lg-5 col-md-12 mb-4">
                                <div class="card border rounded-3 p-4 h-100 d-flex flex-column">
                                    <h5 class="fw-bold mb-1 text-info">Option 2: Email Verification (Quick Setup)</h5>
                                    <p class="text-muted small mb-4">Receive a single-use 6-digit verification code to your registered admin email address every time you sign in.</p>

                                    <div class="p-3 bg-light rounded-3 mb-4">
                                        <div class="text-muted small">Registered Admin Email:</div>
                                        <div class="fw-bold text-dark fs-6">{{ $admin->email }}</div>
                                    </div>

                                    <form method="POST" action="{{ route('admin.security.2fa.email') }}" class="mt-auto">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-info w-100">
                                            Enable Email 2FA Instead
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>

    </div>
</div>

<!-- REGENERATE RECOVERY CODES MODAL -->
<div class="modal fade" id="regenerateCodesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.security.2fa.recovery') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Regenerate Backup Recovery Codes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Generating new recovery codes will immediately invalidate any previously generated backup codes. Enter your current admin password to proceed:
                    </p>
                    <div class="mb-3">
                        <label for="recovery_password" class="form-label small fw-semibold">Current Admin Password</label>
                        <input type="password" name="password" id="recovery_password" class="form-control" required placeholder="Enter your password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Regenerate Codes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DISABLE 2FA MODAL -->
<div class="modal fade" id="disable2faModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.security.2fa.disable') }}">
                @csrf
                <div class="modal-header bg-danger-subtle">
                    <h5 class="modal-title fw-bold text-danger">Disable Two-Factor Authentication</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Are you sure you want to turn off Two-Factor Authentication? Your account will no longer be protected by a second security factor.
                    </p>
                    <div class="mb-3">
                        <label for="disable_password" class="form-label small fw-semibold">Confirm with Current Admin Password</label>
                        <input type="password" name="password" id="disable_password" class="form-control" required placeholder="Enter your password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Disable 2FA</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function copySecretKey() {
        const input = document.getElementById('manualSecretKey');
        const rawKey = input.value.replace(/\s+/g, '');
        navigator.clipboard.writeText(rawKey).then(() => {
            alert('Secret key copied to clipboard!');
        });
    }

    function copyRecoveryCodes() {
        const codes = [];
        document.querySelectorAll('#recoveryCodesContainer .font-monospace').forEach(el => {
            codes.push(el.innerText.trim());
        });
        navigator.clipboard.writeText(codes.join('\n')).then(() => {
            alert('Recovery codes copied to clipboard!');
        });
    }

    function downloadRecoveryCodes() {
        const codes = [];
        document.querySelectorAll('#recoveryCodesContainer .font-monospace').forEach(el => {
            codes.push(el.innerText.trim());
        });
        const blob = new Blob([
            "DUPAY ADMIN - TWO-FACTOR AUTHENTICATION BACKUP CODES\n" +
            "Generated: " + new Date().toISOString() + "\n\n" +
            "Each code can only be used once.\n\n" +
            codes.join('\n') + "\n"
        ], { type: 'text/plain' });

        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'dupay-admin-2fa-recovery-codes.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
</script>
@endsection
