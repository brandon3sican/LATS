<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'LAIS') }}</title>

    <link rel="icon" href="{{ asset('images/denr_logo.png') }}" type="image/png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link href="{{ asset('css/lais.css') }}" rel="stylesheet">

<style>
.password-toggle-btn {
    border-left: 0;
}
.password-toggle-btn:focus {
    box-shadow: none;
}
</style>
</head>

<body class="lais-body"
      style="background: url('{{ asset('images/baguiobg.jpg') }}') no-repeat center center fixed;
             background-size: cover; position: relative;">

<div class="min-vh-100 d-flex align-items-center justify-content-center py-5"
     style="background-color: rgba(255, 255, 255, 0.4);">

    <div class="container" style="max-width: 1000px;">

        {{-- Unified Card Container --}}
        <div class="card border-0 shadow-lg overflow-hidden" style="border-radius: 20px; background-color: rgba(255, 255, 255, 0.95);">
            <div class="position-absolute top-0 start-0 p-4 p-md-2 d-none d-lg-flex align-items-center gap-1 z-3">
                <img src="{{ asset('images/denr_logo.png') }}"
                    alt="DENR Logo"
                    style="height: 70px; width: auto; filter: drop-shadow(0px 0px 5px rgba(255,255,255,0.8));">
                <div style="text-shadow: 0px 0px 10px rgba(255,255,255,0.9);">
                    <div class="h3 mb-0 fw-bold text-dark" >DENR-CAR</div>
                    <div class="h6 text-dark fw-bold">Leave Application Tracking System</div>
                </div>
            </div>
            <div class="row g-0 align-items-stretch">

                <div class="col-lg-6 d-none d-lg-block position-relative bg-dark">
                    <div id="denrCarousel" class="carousel slide carousel-fade h-100" data-bs-ride="carousel">
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#denrCarousel" data-bs-slide-to="0" class="active"></button>
                            <button type="button" data-bs-target="#denrCarousel" data-bs-slide-to="1"></button>
                            <button type="button" data-bs-target="#denrCarousel" data-bs-slide-to="2"></button>
                        </div>

                        <div class="carousel-inner h-100">
                            <div class="carousel-item active h-100">
                                <img src="{{ asset('images/denr_bg9.jpg') }}" class="d-block w-100 h-100" style="object-fit: cover; min-height: 500px;" alt="Slide 1">
                            </div>
                            <div class="carousel-item h-100">
                                <img src="{{ asset('images/denr_bg2.jpg') }}" class="d-block w-100 h-100" style="object-fit: cover; min-height: 500px;" alt="Slide 2">
                            </div>
                            <div class="carousel-item h-100">
                                <img src="{{ asset('images/denr_bg3.jpg') }}" class="d-block w-100 h-100" style="object-fit: cover; min-height: 500px;" alt="Slide 3">
                            </div>
                        </div>

                        <button class="carousel-control-prev" type="button" data-bs-target="#denrCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#denrCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                </div>

                <div class="col-lg-6 d-flex flex-column justify-content-center p-4 p-md-5">

                    {{-- Mobile Logo (Only shows on small screens) --}}
                    <div class="d-lg-none text-center mb-4">
                        <img src="{{ asset('images/denr_logo.png') }}" alt="DENR Logo" style="height: 70px; margin-bottom: 10px;">
                        <div class="fw-bold fs-4 text-dark">DENR LATS</div>
                    </div>

                    {{-- Form Content Injected Here --}}
                    <div class="w-100">
                        {{ $slot }}
                    </div>

                    <div class="text-center text-muted small mt-5 fw-semibold">
                        © {{ date('Y') }} LATS • DENR CAR
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const passwordInputs = document.querySelectorAll('input[type="password"]');

    passwordInputs.forEach(function (input, index) {
        if (input.dataset.eyeReady === '1') return;
        input.dataset.eyeReady = '1';

        let group = input.closest('.input-group');

        if (!group) {
            group = document.createElement('div');
            group.className = 'input-group';

            const parent = input.parentNode;
            parent.insertBefore(group, input);
            group.appendChild(input);
        }

        if (group.querySelector('.password-toggle-btn')) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline-secondary password-toggle-btn';
        button.setAttribute('aria-label', 'Show password');
        button.setAttribute('tabindex', '-1');
        button.innerHTML = '<i class="bi bi-eye"></i>';

        button.addEventListener('click', function () {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            button.innerHTML = isPassword
                ? '<i class="bi bi-eye-slash"></i>'
                : '<i class="bi bi-eye"></i>';
        });

        group.appendChild(button);
    });
});
</script>

</body>
</html>
