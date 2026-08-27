<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name','LAIS') }}</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('css/lais.css') }}" rel="stylesheet">
</head>
<body class="lais-body">

<div class="container py-5">
  <div class="row align-items-center g-4">

    <div class="col-lg-6">
      <div class="d-flex align-items-center gap-2 mb-3">
        <span class="lais-logo">LA</span>
        <div>
          <div class="h3 fw-bold mb-0">LAIS</div>
          <div class="text-muted">Leave Application Information System</div>
        </div>
      </div>

      <div class="h1 fw-bold mb-3" style="line-height:1.1;">
        Modern leave workflow for multi-office approvals
      </div>

      <p class="text-muted fs-5 mb-4">
        Submit leave requests, upload attachments, route approvals (Division Chief → Personnel → Chief Personnel → ARD-MS),
        and track status in one system.
      </p>

      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
          <i class="bi bi-box-arrow-in-right me-2"></i> Log in
        </a>

        @if (Route::has('register'))
          <a href="{{ route('register') }}" class="btn btn-outline-secondary btn-lg">
            <i class="bi bi-person-plus me-2"></i> Register
          </a>
        @endif
      </div>

      <div class="text-muted small mt-4">
        © {{ date('Y') }} LAIS • DENR CAR
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card border-0 shadow-sm" style="border-radius: 18px;">
        <div class="card-body p-4 p-md-5">
          <div class="fw-semibold mb-3">What you can do</div>

          <div class="d-flex gap-3 mb-3">
            <div class="fs-3"><i class="bi bi-send-check"></i></div>
            <div>
              <div class="fw-semibold">Apply Leave</div>
              <div class="text-muted small">Fill up leave details and attach requirements.</div>
            </div>
          </div>

          <div class="d-flex gap-3 mb-3">
            <div class="fs-3"><i class="bi bi-diagram-3"></i></div>
            <div>
              <div class="fw-semibold">Approval Routing</div>
              <div class="text-muted small">Multi-step office workflow per DENR process.</div>
            </div>
          </div>

          <div class="d-flex gap-3">
            <div class="fs-3"><i class="bi bi-clock-history"></i></div>
            <div>
              <div class="fw-semibold">Track Status</div>
              <div class="text-muted small">Timeline of approvals + remarks in one place.</div>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
