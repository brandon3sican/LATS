<x-guest-layout>
    <div class="mb-4">
        <div class="h4 fw-bold mb-1">Create account</div>
        <div class="text-muted">Fill in your details to register.</div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Please fix the following:</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">First Name</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input id="first_name" type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required autofocus autocomplete="given-name">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Middle Name</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input id="middle_name" type="text" name="middle_name" class="form-control" value="{{ old('middle_name') }}" required autofocus autocomplete="additional-name">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Last Name</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input id="last_name" type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required autofocus autocomplete="family-name">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autocomplete="username">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" required autocomplete="new-password">
            </div>
            <div class="form-text">Use at least 8 characters.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
            </div>
        </div>

        <button class="btn btn-primary w-100 py-2" type="submit">
            <i class="bi bi-person-plus me-1"></i> Register
        </button>

        <div class="text-center mt-3">
            <span class="text-muted">Already have an account?</span>
            <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Login</a>
        </div>
    </form>
</x-guest-layout>
