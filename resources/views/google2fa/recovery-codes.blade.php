@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="bi bi-key me-2"></i> Recovery Codes
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Important:</strong> Save these recovery codes in a safe place. You can use them to access your account if you lose your Google Authenticator device.
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold">Your Recovery Codes</h5>
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row">
                                    @foreach($recoveryCodes as $code)
                                        <div class="col-6 mb-2">
                                            <code class="fs-5">{{ $code }}</code>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <p class="text-muted small mt-2">
                            <strong>{{ count($recoveryCodes) }}</strong> recovery codes remaining
                        </p>
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold">Instructions</h5>
                        <ul class="text-muted">
                            <li>Store these codes in a secure location (password manager, safe, etc.)</li>
                            <li>Each code can only be used once</li>
                            <li>Use a recovery code when you can't access your Google Authenticator app</li>
                            <li>Generate new codes if you suspect any have been compromised</li>
                        </ul>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary" id="regenerateCodesBtn">
                            <i class="bi bi-arrow-clockwise me-1"></i> Regenerate Codes
                        </button>
                        <a href="{{ route('approver.google2fa.setup') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Back to Setup
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const regenerateBtn = document.getElementById('regenerateCodesBtn');
    if (regenerateBtn) {
        regenerateBtn.addEventListener('click', function() {
            if (!confirm('Are you sure you want to regenerate recovery codes? Your old codes will no longer work.')) {
                return;
            }

            this.disabled = true;
            this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Regenerating...';

            fetch('{{ route("approver.google2fa.regenerate-codes") }}', {
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
                    alert('New recovery codes have been generated. Please save them in a safe place:\n\n' + 
                          data.recovery_codes.join('\n'));
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Regenerate Codes';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to regenerate recovery codes');
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Regenerate Codes';
            });
        });
    }
});
</script>
@endsection