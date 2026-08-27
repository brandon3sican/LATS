@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">My Leaves</h3>
    <a href="{{ route('employee.leaves.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Apply Leave
    </a>
  </div>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Date Filed</th>
            <th>Type</th>
            <th>Inclusive Dates</th>
            <th class="text-center">Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($leaves as $l)
          <tr>
            <td>{{ $l->date_filed->format('M d, Y') }}</td>
            <td>
                <span class="fw-semibold">{{ $l->leaveType->name }}</span>
            </td>
            <td>
                {{ $l->start_date->format('M d') }} - {{ $l->end_date->format('M d, Y') }}
                <div class="small text-muted">({{ $l->working_days_requested }} day/s)</div>
            </td>
            <td class="text-center">
                @php
                    $status = strtolower($l->status);
                    $badgeClass = match($status) {
                        'approved' => 'bg-success text-white',     // Green
                        'pending'  => 'bg-warning text-dark',      // Yellow
                        'returned' => 'bg-info text-white',        // Cyan (forced white text)
                        'disapproved' => 'bg-danger text-white',   // Red
                        'cancelled' => 'bg-secondary text-white',  // Grey
                        default    => 'bg-secondary text-white',
                    };
                @endphp
                <span class="badge {{ $badgeClass }} rounded-3 px-3 py-2 text-uppercase fw-bold"
                      style="font-size: 0.75rem; min-width: 90px;">
                    {{ $l->status }}
                </span>
            </td>
            <td class="text-end">
              <div class="d-flex gap-1 justify-content-end">
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('employee.leaves.show', $l->id) }}">
                    <i class="bi bi-eye"></i> View
                  </a>
                  @if(in_array($l->status, ['approved','disapproved','returned'], true))
                    <a class="btn btn-sm btn-outline-secondary"
                        href="{{ route('employee.leaves.form6.pdf', $l->id) }}"
                        target="_blank" title="Print Form 6">
                            <i class="bi bi-printer"></i>
                        </a>
                  @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
              <td colspan="5" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                  No leave applications found.
              </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">{{ $leaves->links() }}</div>
</div>
@endsection
