<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name', 'LAIS') }}</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <style>
    body { background: #f6f7fb; }
    .app-shell { min-height: 100vh; }
    .sidebar {
      width: 260px;
      background: #fff;
      border-right: 1px solid rgba(0,0,0,.08);
    }
    .sidebar a { text-decoration: none; }
    .nav-link {
      color: #222;
      border-radius: 10px;
      padding: .6rem .75rem;
    }
    .nav-link:hover { background: rgba(0,0,0,.04); }
    .nav-link.active { background: rgba(13,110,253,.12); color: #0d6efd; font-weight: 600; }
    .topbar {
      background: #fff;
      border-bottom: 1px solid rgba(0,0,0,.08);
    }
    .content-wrap { flex: 1; }
  </style>
</head>
<body>

<div class="d-flex app-shell">

  {{-- Sidebar --}}
  <aside class="sidebar p-3 d-none d-lg-block">
    <div class="d-flex align-items-center gap-2 mb-3">
      <div class="rounded-3 bg-primary text-white d-flex align-items-center justify-content-center"
           style="width:38px;height:38px;">
        <i class="bi bi-shield-check"></i>
      </div>
      <div>
        <div class="fw-bold">LATS</div>
        <div class="text-muted small">Admin Panel</div>
      </div>
    </div>

    <div class="nav flex-column gap-1">
      <a class="nav-link {{ request()->is('admin/reports*') ? 'active' : '' }}"
         href="{{ route('admin.reports.index') }}">
        <i class="bi bi-graph-up me-2"></i> Reports
      </a>

      <a class="nav-link {{ request()->is('admin/approval-steps*') ? 'active' : '' }}"
         href="{{ route('admin.approvalSteps.index') }}">
        <i class="bi bi-diagram-3 me-2"></i> Approval Steps
      </a>
    </div>

    <hr>

    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="btn btn-outline-secondary w-100">
        <i class="bi bi-box-arrow-right me-1"></i> Logout
      </button>
    </form>
  </aside>

  {{-- Main --}}
  <div class="content-wrap d-flex flex-column">

    {{-- Topbar --}}
    <div class="topbar px-3 py-2 d-flex align-items-center justify-content-between">
      <div class="d-lg-none">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.reports.index') }}">
          <i class="bi bi-list"></i>
        </a>
      </div>

      <div class="small text-muted">
        Logged in as: <span class="fw-semibold">{{ auth()->user()->name ?? 'User' }}</span>
      </div>
    </div>

    {{-- Flash status --}}
    <div class="container py-3">
      @if (session('status'))
        <div class="alert alert-success shadow-sm mb-3">
          {{ session('status') }}
        </div>
      @endif

      @if ($errors->any())
        <div class="alert alert-danger shadow-sm mb-3">
          <div class="fw-semibold mb-1">Please fix the following:</div>
          <ul class="mb-0">
            @foreach($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif
    </div>

    {{-- CONTENT --}}
    <main class="container pb-4">
      @yield('content')
    </main>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
