@extends('layouts.app')

@section('content')
<div class="container py-4">

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h3 class="mb-0">Employee Dashboard</h3>
      <div class="text-muted small">Calendar view of your leave applications</div>
    </div>

    <div class="d-flex gap-2">
      <a class="btn btn-primary" href="{{ route('employee.leaves.create') }}">
        <i class="bi bi-plus-circle"></i> Apply Leave
      </a>
      <a class="btn btn-outline-secondary" href="{{ route('employee.leaves.index') }}">
        <i class="bi bi-clock-history"></i> Leave History
      </a>
    </div>
  </div>

  {{-- Quick stats --}}
  <div class="row g-3 mb-3">
    {{-- Card 1: Total Applications --}}
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-start border-4 border-primary">
        <div class="card-body">
          <div class="text-muted small text-uppercase fw-bold">Total Applications</div>
          <div class="fs-1 fw-bold text-dark">{{ $stats->total_applications ?? 0 }}</div>
        </div>
      </div>
    </div>

  {{-- Card 2: Pending --}}
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-start border-4 border-warning">
        <div class="card-body">
          <div class="text-muted small text-uppercase fw-bold">Pending Approval</div>
          <div class="fs-1 fw-bold text-dark">{{ $stats->pending ?? 0 }}</div>
        </div>
      </div>
    </div>

    {{-- Card 3: Approved --}}
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-start border-4 border-success">
        <div class="card-body">
          <div class="text-muted small text-uppercase fw-bold">Approved Leaves</div>
          <div class="fs-1 fw-bold text-dark">{{ $stats->approved ?? 0 }}</div>
          <div class="text-muted small">
             Leaves you have already taken.
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Calendar + Filter --}}
  <div class="card mb-3 border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
      <div class="fw-semibold text-primary">
        <i class="bi bi-calendar-event me-2"></i> My Leave Calendar
      </div>

      <div class="d-flex align-items-center gap-2">
        <span class="small text-muted fw-bold">Filter:</span>
        <select id="statusFilter" class="form-select form-select-sm" style="width: 150px;">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="returned">Returned</option>
          <option value="disapproved">Disapproved</option>
        </select>
      </div>
    </div>

    <div class="card-body d-flex justify-content-center bg-light py-4">
      {{-- Flatpickr Calendar Container --}}
      <input type="text" id="flatpickrLeaveCalendar" class="d-none">
    </div>
  </div>

  {{-- Recent applications --}}
  <div class="card shadow-sm border-0">
    <div class="card-header bg-white fw-semibold py-3">Recent Leave Applications</div>
    <div class="card-body">
      @if(empty($leaves) || $leaves->count() === 0)
        <div class="alert alert-info mb-0 border-0">No leave applications yet.</div>
      @else
        <div class="table-responsive">
          <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>Date Filed</th>
                <th>Leave Type</th>
                <th>Dates</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach($leaves as $l)
              @php
                $status = strtolower($l->status ?? 'pending');
                $badge = match($status) {
                  'approved' => 'success',
                  'returned' => 'info',
                  'disapproved' => 'danger',
                  'cancelled' => 'secondary',
                  default => 'warning',
                };
                $text = ($badge === 'warning') ? 'text-dark' : '';
              @endphp
              <tr>
                <td>{{ optional($l->date_filed)->format('Y-m-d') }}</td>
                <td><span class="fw-medium">{{ $l->leaveType->name ?? '-' }}</span></td>
                <td>
                  {{ optional($l->start_date)->format('Y-m-d') }} to
                  {{ optional($l->end_date)->format('Y-m-d') }}
                </td>
                <td>
                  <span class="badge bg-{{ $badge }} {{ $text }} px-2 py-1">{{ strtoupper($status) }}</span>
                </td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('employee.leaves.show', $l->id) }}">
                    View Details
                  </a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

</div>

{{-- MODAL FOR SHOWING LEAVES ON A SPECIFIC DATE --}}
<div class="modal fade" id="dailyLeavesModal" tabindex="-1" aria-labelledby="dailyLeavesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light">
        <h5 class="modal-title fw-bold" id="dailyLeavesModalLabel">
            <i class="bi bi-calendar-check me-2"></i> Details for <span id="modalDateLabel" class="text-primary"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
         <div id="modalLeavesList"></div>
      </div>
    </div>
  </div>
</div>

{{-- Flatpickr CSS & JS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<style>
    /* Make Flatpickr act like a centered dashboard calendar */
    .flatpickr-calendar.inline {
        width: 100% !important;
        max-width: 900px !important;
        margin: 0 auto !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        background: #fff;
    }
    .flatpickr-innerContainer, .flatpickr-rContainer {
        width: 100% !important;
        display: block !important;
    }
    .flatpickr-weekdays, .flatpickr-weekdaycontainer {
        display: flex !important;
        width: 100% !important;
    }
    span.flatpickr-weekday {
        flex: 1 1 0 !important;
        width: auto !important;
    }
    .flatpickr-days {
        width: 100% !important;
        display: block !important;
    }
    .dayContainer {
        width: 100% !important;
        min-width: 100% !important;
        max-width: 100% !important;
        display: flex !important;
        flex-wrap: wrap !important;
    }
    .flatpickr-day {
        width: 14.2857% !important;
        max-width: 14.2857% !important;
        flex-basis: 14.2857% !important;
        margin: 0 !important;
        height: 80px !important;
        display: flex !important;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        padding-top: 8px !important;
        border: 1px solid transparent;
        position: relative;
    }
    .flatpickr-day:hover {
        background: #f8f9fa !important;
        border-color: #dee2e6 !important;
    }
    .leave-dots-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 3px;
        margin-top: auto;
        padding-bottom: 8px;
        width: 100%;
        max-width: 80%;
    }
    .leave-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  let leavesData = {};
  let fpInstance = null;
  const statusFilter = document.getElementById('statusFilter');

  // 1. Fetch data from your existing endpoint and map it for Flatpickr
  function loadEventsAndRender() {
      const params = new URLSearchParams();
      if (statusFilter.value) params.set('status', statusFilter.value);

      // Fetch a 2-year range to cover past and future leaves seamlessly
      params.set('start', '2024-01-01');
      params.set('end', '2026-12-31');

      fetch("{{ route('employee.dashboard.events') }}?" + params.toString(), {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(res => res.json())
      .then(events => {
          // Reset data
          leavesData = {};

          // Convert FullCalendar events to Flatpickr Dictionary
          events.forEach(event => {
              let d = event.start.split('T')[0]; // Extract YYYY-MM-DD
              if (!leavesData[d]) leavesData[d] = [];

              leavesData[d].push({
                  title: event.title,
                  color: event.backgroundColor || '#0d6efd',
                  url: event.url
              });
          });

          // Re-render or Initialize Flatpickr
          if (fpInstance) {
              fpInstance.redraw();
          } else {
              initFlatpickr();
          }
      });
  }

  // 2. Initialize the Flatpickr Calendar
  function initFlatpickr() {
      fpInstance = flatpickr("#flatpickrLeaveCalendar", {
          inline: true,
          onChange: function(selectedDates, dateStr, instance) {
              if (leavesData[dateStr]) {
                  showLeavesForDate(dateStr, leavesData[dateStr]);
              } else {
                  instance.clear();
              }
          },
          onDayCreate: function(dObj, dStr, fp, dayElem) {
              const dateStr = fp.formatDate(dayElem.dateObj, "Y-m-d");

              if (leavesData[dateStr] && leavesData[dateStr].length > 0) {
                  dayElem.style.cursor = 'pointer';

                  let dotsHtml = '<div class="leave-dots-container">';
                  leavesData[dateStr].forEach(function(leave) {
                      dotsHtml += `<span class="leave-dot" style="background-color: ${leave.color};" title="${leave.title}"></span>`;
                  });
                  dotsHtml += '</div>';

                  dayElem.innerHTML += dotsHtml;
              }
          }
      });
  }

  // 3. Render Modal Content
  function showLeavesForDate(dateStr, dailyLeaves) {
      let html = '<div class="list-group list-group-flush">';

      dailyLeaves.forEach(function(leave) {
          html += `<a href="${leave.url || '#'}" class="list-group-item list-group-item-action d-flex align-items-center py-3">`;
          html += `<span class="rounded-circle me-3 flex-shrink-0" style="width: 14px; height: 14px; background-color: ${leave.color};"></span>`;
          html += '<div>';
          html += `<div class="fw-bold mb-1 text-dark">${leave.title}</div>`;
          html += `<div class="small text-muted"><i class="bi bi-box-arrow-up-right me-1"></i>Click to view application</div>`;
          html += '</div></a>';
      });

      html += '</div>';

      // Ensure local timezone parsing to avoid date shifting
      const dateObj = new Date(dateStr + "T00:00:00");
      const options = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' };
      document.getElementById('modalDateLabel').innerText = dateObj.toLocaleDateString('en-US', options);

      document.getElementById('modalLeavesList').innerHTML = html;
      var modal = new bootstrap.Modal(document.getElementById('dailyLeavesModal'));
      modal.show();
  }

  // Initial Load
  loadEventsAndRender();

  // Reload when the user changes the filter
  statusFilter.addEventListener('change', function () {
      loadEventsAndRender();
  });
});
</script>
@endsection
