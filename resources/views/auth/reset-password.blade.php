<x-guest-layout>
    <div class="mb-4">
        <div class="h4 fw-bold mb-1">Reset password</div>
        <div class="text-muted">Set a new password for your account.</div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <label class="form-label">Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" value="{{ old('email', $request->email) }}" required autofocus>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">New Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" required autocomplete="new-password">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
            </div>
        </div>

        <button class="btn btn-primary w-100 py-2" type="submit">
            <i class="bi bi-check2-circle me-1"></i> Reset password
        </button>
    </form>
</x-guest-layout>
