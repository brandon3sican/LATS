@extends('layouts.app')

@section('content')
<div class="container py-4">

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-0">Reports</h3>
      <div class="text-muted">Generate summaries and export to Excel/PDF.</div>
    </div>
  </div>

  <div class="row g-3">

    <div class="col-md-4">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-calendar3"></i>
            <div class="fw-semibold">Monthly Summary</div>
          </div>
          <div class="text-muted small mb-3">
            Summary of filed leaves for a selected month with status/type breakdown.
          </div>
          <a class="btn btn-primary w-100" href="{{ route('admin.reports.monthly') }}">
            Open Monthly Report
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-person-badge"></i>
            <div class="fw-semibold">Per Employee</div>
          </div>
          <div class="text-muted small mb-3">
            View an employee’s all the leave history for a selected month.
          </div>
          <a class="btn btn-primary w-100" href="{{ route('admin.reports.employee') }}">
            Open Employee Report
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-diagram-2"></i>
            <div class="fw-semibold">Per Division</div>
          </div>
          <div class="text-muted small mb-3">
            View division-level leave totals and a breakdown per employee.
          </div>
          <a class="btn btn-primary w-100" href="{{ route('admin.reports.division') }}">
            Open Division Report
          </a>
        </div>
      </div>
    </div>

  </div>

</div>
@endsection
