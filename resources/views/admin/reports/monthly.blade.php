@extends('layouts.app')

@section('content')
<div class="container py-4">

  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
      <h3 class="mb-0">Monthly Summary</h3>
      <div class="text-muted">
        Showing results for <span class="fw-semibold">{{ $from->format('F Y') }}</span>
      </div>
    </div>

    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index') }}">Back</a>

      <a class="btn btn-outline-success"
         href="{{ route('admin.reports.monthly.excel', request()->query()) }}">
        <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
      </a>

      <a class="btn btn-outline-danger"
         href="{{ route('admin.reports.monthly.pdf', request()->query()) }}"
         target="_blank">
        <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
      </a>
    </div>
  </div>

  {{-- FILTER CARD --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form class="row g-2 align-items-end" method="GET" action="{{ route('admin.reports.monthly') }}">

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

  {{-- PRE-CALCULATE TOTALS --}}
  @php
      $calculatedTotalDays = 0;
      foreach($leaves as $l) {
          $d = $l->number_of_days;
          if ($d <= 0 && $l->start_date && $l->end_date) {
              $start = \Carbon\Carbon::parse($l->start_date);
              $end = \Carbon\Carbon::parse($l->end_date);
              $d = $start->diffInWeekdays($end->copy()->addDay());
          }
          $calculatedTotalDays += $d;
      }

      $totalDaysDisplay = (floatval($calculatedTotalDays) == intval($calculatedTotalDays))
                          ? intval($calculatedTotalDays)
                          : number_format($calculatedTotalDays, 1);
  @endphp

  {{-- SUMMARY CARDS --}}
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="fw-semibold fs-5">Monthly Overview</div>
          <div class="text-muted small">
            Period: {{ $from->format('F Y') }}
          </div>
          <div class="text-muted small">
            All Divisions / All Employees
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small">Total Applications</div>
          <div class="fs-3 fw-bold">{{ $leaves->count() }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small">Total Days</div>
          <div class="fs-3 fw-bold">{{ $totalDaysDisplay }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card shadow-sm">
    <div class="card-header bg-white d-flex align-items-center justify-content-between">
      <div class="fw-semibold">Applications List</div>
      <div class="text-muted small">{{ $leaves->count() }} record(s)</div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Date Filed</th>
            <th>Employee</th>
            <th>Division</th>
            <th>Leave Type</th>
            <th>Inclusive Dates</th>
            <th class="text-end">No. of Days</th>
            <th>Status</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($leaves as $l)
            @php
              $st = $l->status;
              $badge = match($st) {
                'approved' => 'success',
                'pending' => 'warning',
                'returned' => 'info',
                'disapproved' => 'danger',
                default => 'secondary'
              };

              // Zero Days Fix for Row
              $val = $l->number_of_days;
              if ($val <= 0 && $l->start_date && $l->end_date) {
                  $start = \Carbon\Carbon::parse($l->start_date);
                  $end = \Carbon\Carbon::parse($l->end_date);
                  $val = $start->diffInWeekdays($end->copy()->addDay());
              }
              $displayDays = (floatval($val) == intval($val)) ? intval($val) : number_format($val, 1);
            @endphp
            <tr>
              <td>{{ optional($l->date_filed)->format('Y-m-d') }}</td>
              <td>
                <div class="fw-semibold">{{ $l->employee->user->name ?? '—' }}</div>
                <div class="small text-muted">{{ $l->employee->position_title ?? '' }}</div>
              </td>
              <td>{{ $l->employee->division->name ?? '—' }}</td>
              <td>{{ $l->leaveType->name ?? '—' }}</td>
              <td>
                {{ optional($l->start_date)->format('Y-m-d') }}
                <span class="text-muted">to</span>
                {{ optional($l->end_date)->format('Y-m-d') }}
              </td>

              <td class="text-end fw-bold">{{ $displayDays }}</td>

              <td><span class="badge text-bg-{{ $badge }}">{{ strtoupper($st) }}</span></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary"
                   href="{{ route('admin.leaves.form6.pdf', $l->id) }}"
                   target="_blank">
                  <i class="bi bi-printer me-1"></i> Form 6 PDF
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-5 text-muted">
                No applications found for this month.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection
