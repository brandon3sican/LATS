<x-guest-layout>
    <div class="mb-4">
        <div class="h4 fw-bold mb-1">Forgot password</div>
        <div class="text-muted">Enter your email and we’ll send a reset link.</div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
            </div>
        </div>

        <button class="btn btn-primary w-100 py-2" type="submit">
            <i class="bi bi-send me-1"></i> Send reset link
        </button>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="text-decoration-none">Back to login</a>
        </div>
    </form>
</x-guest-layout>
