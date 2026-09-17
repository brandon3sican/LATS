@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-shield-lock me-2"></i> Google Authenticator Setup
                    </h4>
                </div>
                <div class="card-body">
                    @if($user->google2fa_enabled)
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            <strong>Google Authenticator is enabled</strong> for your account.
                        </div>

                        <div class="mb-4">
                            <h5 class="fw-bold">Disable Google Authenticator</h5>
                            <p class="text-muted">You can disable Google Authenticator and revert to email OTP verification.</p>
                            <button type="button" class="btn btn-danger" id="disableGoogle2faBtn">
                                <i class="bi bi-shield-x me-1"></i> Disable Google Authenticator
                            </button>
                        </div>

                        <div class="mb-4">
                            <h5 class="fw-bold">Recovery Codes</h5>
                            <p class="text-muted">These are your backup codes in case you lose access to your device.</p>
                            <a href="{{ route('approver.google2fa.recovery-codes') }}" class="btn btn-outline-primary">
                                <i class="bi bi-key me-1"></i> View Recovery Codes
                            </a>
                        </div>
                    @else
                        @if($secret && $qrCodeUrl)
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Setup in progress:</strong> Scan the QR code below and verify with a code from your Google Authenticator app.
                            </div>

                            <div class="text-center mb-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCodeUrl) }}" 
                                             alt="Google Authenticator QR Code" 
                                             class="img-fluid"
                                             style="max-width: 200px;">
                                    </div>
                                </div>
                                <p class="text-muted small mt-2">Scan this QR code with Google Authenticator app</p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Or enter this code manually:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace" value="{{ $secret }}" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copySecret()">
                                        <i class="bi bi-clipboard"></i> Copy
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Verify Setup</label>
                                <p class="text-muted small">Enter the 6-digit code from your Google Authenticator app to complete setup:</p>
                                <input type="text" id="verifyCode" class="form-control text-center fs-4 fw-bold"
                                       maxlength="6" placeholder="000000" style="letter-spacing: 8px;">
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success flex-grow-1" id="confirmSetupBtn">
                                    <i class="bi bi-check-circle me-1"></i> Enable Google Authenticator
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="cancelSetupBtn">
                                    <i class="bi bi-x-circle me-1"></i> Cancel
                                </button>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Google Authenticator is not enabled</strong> for your account.
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold">Why use Google Authenticator?</h5>
                                <ul class="text-muted">
                                    <li>More secure than email OTP</li>
                                    <li>Works offline - no internet needed</li>
                                    <li>Codes change every 30 seconds</li>
                                    <li>No email delivery delays</li>
                                </ul>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold">Requirements</h5>
                                <ul class="text-muted">
                                    <li>Install Google Authenticator app on your phone</li>
                                    <li>Scan QR code during setup</li>
                                    <li>Keep recovery codes safe as backup</li>
                                </ul>
                            </div>

                            <button type="button" class="btn btn-primary" id="enableGoogle2faBtn">
                                <i class="bi bi-shield-plus me-1"></i> Enable Google Authenticator
                            </button>
                        @endif
                    @endif
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="{{ route('approver.inbox') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Inbox
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function copySecret() {
    const secretInput = document.querySelector('input[value="{{ $secret }}"]');
    secretInput.select();
    document.execCommand('copy');
    
    const btn = document.querySelector('button[onclick="copySecret()"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
    setTimeout(() => {
        btn.innerHTML = originalText;
    }, 2000);
}

document.addEventListener('DOMContentLoaded', function() {
    // Enable Google Authenticator
    const enableBtn = document.getElementById('enableGoogle2faBtn');
    if (enableBtn) {
        enableBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Generating...';

            fetch('{{ route("approver.google2fa.enable") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-shield-plus me-1"></i> Enable Google Authenticator';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to enable Google Authenticator');
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-shield-plus me-1"></i> Enable Google Authenticator';
            });
        });
    }

    // Confirm Setup
    const confirmBtn = document.getElementById('confirmSetupBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            const code = document.getElementById('verifyCode').value;

            if (code.length !== 6) {
                alert('Please enter a 6-digit code');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Verifying...';

            fetch('{{ route("approver.google2fa.confirm") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ code: code })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Google Authenticator has been successfully enabled! Your recovery codes are: ' + 
                          data.recovery_codes.join(', ') + '\n\nPlease save these codes in a safe place.');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-check-circle me-1"></i> Enable Google Authenticator';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to verify code');
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-check-circle me-1"></i> Enable Google Authenticator';
            });
        });
    }

    // Cancel Setup
    const cancelBtn = document.getElementById('cancelSetupBtn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to cancel the setup?')) {
                window.location.reload();
            }
        });
    }

    // Disable Google Authenticator
    const disableBtn = document.getElementById('disableGoogle2faBtn');
    if (disableBtn) {
        disableBtn.addEventListener('click', function() {
            if (!confirm('Are you sure you want to disable Google Authenticator? You will revert to email OTP verification.')) {
                return;
            }

            this.disabled = true;
            this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Disabling...';

            fetch('{{ route("approver.google2fa.disable") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Google Authenticator has been disabled');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-shield-x me-1"></i> Disable Google Authenticator';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to disable Google Authenticator');
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-shield-x me-1"></i> Disable Google Authenticator';
            });
        });
    }
});
</script>
@endsection