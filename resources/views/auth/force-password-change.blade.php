<x-guest-layout>
    {{-- Merged Header & Warning to save vertical space --}}
    <div class="mb-4">
        <div class="d-flex align-items-center mb-1">
            <i class="bi bi-shield-exclamation text-warning fs-4 me-2"></i>
            <div class="h4 fw-bold mb-0">Action Required</div>
        </div>
        <div class="text-muted small">For security, please update your default credentials before continuing.</div>
    </div>

    @if (session('status'))
        <div class="alert alert-success py-2">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            <div class="fw-semibold mb-1 small">Please fix the following:</div>
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('force-password.update') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Email / Username <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email"
                       name="email"
                       class="form-control"
                       value="{{ old('email', auth()->user()->email) }}"
                       required autofocus autocomplete="username"
                       placeholder="you@office.gov.ph">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">New Password <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password"
                       name="password"
                       class="form-control"
                       required autocomplete="new-password"
                       placeholder="Min. 8 characters">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password"
                       name="password_confirmation"
                       class="form-control"
                       required autocomplete="new-password"
                       placeholder="••••••••">
            </div>
        </div>

        <button class="btn btn-primary w-100 py-2" type="submit">
            <i class="bi bi-shield-check me-1"></i> Save and Continue
        </button>
    </form>

    <div class="text-center mt-3">
        <span class="text-muted">Want to do this later?</span>
        <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-link p-0 m-0 align-baseline text-decoration-none fw-semibold">Log Out</button>
        </form>
    </div>
</x-guest-layout>
