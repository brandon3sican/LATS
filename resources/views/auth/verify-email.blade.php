<x-guest-layout>
    <div class="mb-4">
        <div class="h4 fw-bold mb-1">Verify your email</div>
        <div class="text-muted">
            Thanks for signing up! Please verify your email by clicking the link we sent.
        </div>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <div class="d-flex gap-2">
        <form method="POST" action="{{ route('verification.send') }}" class="flex-grow-1">
            @csrf
            <button class="btn btn-primary w-100" type="submit">
                <i class="bi bi-envelope-check me-1"></i> Resend verification
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-outline-danger" type="submit">
                Logout
            </button>
        </form>
    </div>
</x-guest-layout>
