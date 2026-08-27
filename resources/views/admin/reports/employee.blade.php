@extends('layouts.app')

@section('content')
<div class="container py-4">

  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
      <h3 class="mb-0">Employee Report</h3>
      <div class="text-muted">Filter by employee and month.</div>
    </div>
  </div>

  {{-- FILTER CARD --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form class="row g-2 align-items-end" method="GET" action="{{ route('admin.reports.employee') }}">

        <div class="col-md-5">
          <label class="form-label">Employee</label>
          <select class="form-select" name="employee_id" id="select-employee" required autocomplete="off">
            <option value="">-- Select or Type Employee --</option>
            @foreach($employees as $emp)
              <option value="{{ $emp->id }}" {{ (string)$emp->id === (string)request('employee_id') ? 'selected' : '' }}>
                {{ $emp->user->last_name }}, {{ $emp->user->first_name }} ({{ $emp->division->name ?? 'N/A' }})
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Month</label>
          <select class="form-select" name="month">
            @for($m=1; $m<=12; $m++)
              <option value="{{ $m }}" {{ $m == request('month', now()->month) ? 'selected' : '' }}>
                {{ DateTime::createFromFormat('!m', $m)->format('F') }}
              </option>
            @endfor
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Year</label>
          <select class="form-select" name="year">
            @for($y=date('Y'); $y>=2020; $y--)
              <option value="{{ $y }}" {{ $y == request('year', now()->year) ? 'selected' : '' }}>
                {{ $y }}
              </option>
            @endfor
          </select>
        </div>

        <div class="col-md-3">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-filter me-1"></i> Generate
          </button>
        </div>
      </form>
    </div>
  </div>

  @if($employee)
    @php
        $monthLabel = \Carbon\Carbon::create(null, request('month'), 1)->format('F Y');
    @endphp

    {{-- SUMMARY CARDS --}}
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <div class="fw-semibold fs-5">{{ $employee->user->first_name }} {{ $employee->user->last_name }}</div>
            <div class="text-muted small">Division: {{ $employee->division->name ?? '—' }}</div>
            <div class="text-muted small">Month: {{ $monthLabel }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small">Total Applications</div>
            <div class="fs-3 fw-bold">{{ $totals['count'] ?? 0 }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small">Total Days</div>
            <div class="fs-3 fw-bold">{{ $totals['days'] ?? 0 }}</div>
          </div>
        </div>
      </div>
    </div>

    {{-- TABLE RESULTS --}}
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <div class="fw-semibold">Applications List</div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Date Filed</th>
              <th>Leave Type</th>
              <th>Inclusive Dates</th>
              <th class="text-end">No. of Days</th>
              <th>Status</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($leaves as $l)
              <tr>
                <td>{{ optional($l->date_filed)->format('Y-m-d') }}</td>
                <td>{{ $l->leaveType->name ?? '—' }}</td>
                <td>
                  {{ optional($l->start_date)->format('Y-m-d') }} to {{ optional($l->end_date)->format('Y-m-d') }}
                </td>
                <td class="text-end fw-bold">{{ $l->number_of_days }}</td>
                <td>
                    <span class="badge {{ $l->status === 'approved' ? 'bg-success' : ($l->status === 'pending' ? 'bg-warning' : 'bg-secondary') }}">
                        {{ strtoupper($l->status) }}
                    </span>
                </td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.leaves.form6.pdf', $l->id) }}" target="_blank">
                    <i class="bi bi-printer"></i> PDF
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-5 text-muted">No applications found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  @endif
</div>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        new TomSelect("#select-employee", { create: false, placeholder: "Select or search employee..." });
    });
</script>
@endpush
