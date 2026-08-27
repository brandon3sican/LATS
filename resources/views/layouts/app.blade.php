<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'LAIS') }}</title>

    <link rel="icon" href="{{ asset('images/denr_logo.png') }}" type="image/png">

    <!-- Bootstrap + Icons (modern + clean, no Laravel default UI) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Your custom theme -->
    <link href="{{ asset('css/lais.css') }}" rel="stylesheet">

    {{-- Keep JS from Vite if needed (optional). If it causes style conflicts, remove app.css --}}
    @vite(['resources/js/app.js'])
</head>

<body class="lais-body">
    @include('layouts.partials.topbar')

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar desktop -->
            <aside class="col-lg-2 d-none d-lg-block lais-sidebar position-sticky"
                style="top: 60px; height: calc(100vh - 60px); overflow-y: auto; overflow-x: hidden;">
                @include('layouts.partials.sidebar')
            </aside>

            <!-- Main -->
            <main class="col-lg-10 lais-main">
                <div class="lais-content">

                    @if (session('status'))
                        <div class="alert alert-success lais-alert">{{ session('status') }}</div>
                    @endif

                    {{-- This supports both: Blade pages using @section('content') and Breeze slot pages --}}
                    @yield('content')
                    {{ $slot ?? '' }}

                </div>

                <footer class="lais-footer">
                    <div class="text-muted small">
                        © {{ date('Y') }} LATS • DENR Leave Application Tracking System
                    </div>
                </footer>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>
