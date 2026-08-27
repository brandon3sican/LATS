@extends('layouts.app')
@section('content')
<div class="container py-4">
    <h3 class="mb-4">Approver Dashboard</h3>

{{-- STATS ROW --}}
    @php
        // Determine column sizes based on role
        $isPersonnel = auth()->user()->hasRole('approver_personnel');
        $colClass = $isPersonnel ? 'col-md-3 col-sm-6' : 'col-md-4 col-sm-12';
    @endphp

    <div class="row g-3 mb-4">
        {{-- Normal Pending Requests --}}
        <div class="{{ $colClass }}">
            <div class="card shadow-sm border-start border-4 border-warning h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Pending Requests</div>
                        <div class="fs-1 fw-bold text-dark">{{ $stats['pending'] }}</div>
                    </div>
                    <i class="bi bi-inbox fs-1 text-warning"></i>
                </div>
                <div class="card-footer bg-white">
                    <a href="{{ route('approver.inbox') }}" class="text-decoration-none small text-warning fw-bold">
                        Go to Inbox <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- NEW: Cancellation Requests (Visible ONLY to Personnel) --}}
        @if($isPersonnel)
        <div class="{{ $colClass }}">
            <div class="card shadow-sm border-start border-4 border-danger h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Cancellations</div>
                        <div class="fs-1 fw-bold text-dark">{{ $stats['cancellations'] }}</div>
                    </div>
                    <i class="bi bi-exclamation-octagon fs-1 text-danger"></i>
                </div>
                <div class="card-footer bg-white">
                    <a href="{{ route('approver.inbox') }}" class="text-decoration-none small text-danger fw-bold">
                        Review Requests <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- Processed Total --}}
        <div class="{{ $colClass }}">
            <div class="card shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Processed Total</div>
                        <div class="fs-1 fw-bold text-dark">{{ $stats['processed'] }}</div>
                    </div>
                    <i class="bi bi-check2-all fs-1 text-primary"></i>
                </div>
                <div class="card-footer bg-white">
                    <a href="{{ route('approver.reports.myActions') }}" class="text-decoration-none small text-primary fw-bold">
                        View History <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Demographics for Approver --}}
        <div class="{{ $colClass }}">
            <div class="card shadow-sm border-start border-4 border-info h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold mb-2">Workforce</div>
                    <div class="d-flex justify-content-between align-items-center px-2">
                        <div class="text-center">
                            <div class="fs-3 fw-bold bi bi-gender-male text-primary">{{ $stats['male'] ?? 0 }}</div>
                            <small class="text-muted">Male</small>
                        </div>
                        <div class="vr" style="height: 40px;"></div>
                        <div class="text-center">
                            <div class="fs-3 fw-bold bi bi-gender-female text-danger">{{ $stats['female'] ?? 0 }}</div>
                            <small class="text-muted">Female</small>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white text-muted small">
                    Active Employees
                </div>
            </div>
        </div>
    </div>

    {{-- LEAVE CALENDAR ROW --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 fw-bold d-flex justify-content-between align-items-center flex-wrap">
                    <span><i class="bi bi-calendar-event text-primary me-2"></i> Division Leave Schedule</span>
                    <div class="small mt-2 mt-md-0">
                        <span class="badge bg-success me-1">VL</span>
                        <span class="badge bg-danger me-1">SL</span>
                        <span class="badge bg-info text-dark me-1">SPL</span>
                        <span class="badge" style="background-color: #d63384">ML</span>
                        <span class="badge bg-secondary">Other</span>
                    </div>
                </div>
                <div class="card-body d-flex justify-content-center bg-light py-4">
                    {{-- Flatpickr Calendar Container --}}
                    <input type="text" id="flatpickrLeaveCalendar" class="d-none">
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL FOR SHOWING EMPLOYEES ON LEAVE FOR A SPECIFIC DATE --}}
<div class="modal fade" id="dailyLeavesModal" tabindex="-1" aria-labelledby="dailyLeavesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light">
        <h5 class="modal-title fw-bold" id="dailyLeavesModalLabel">
            <i class="bi bi-people me-2"></i> On Leave: <span id="modalDateLabel" class="text-primary"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
         <div id="modalLeavesList"></div>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
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

    /* Force internal containers to stretch fully */
    .flatpickr-innerContainer,
    .flatpickr-rContainer {
        width: 100% !important;
        display: block !important;
    }

    /* Distribute the weekday headers (Mon, Tue, Wed) evenly */
    .flatpickr-weekdays,
    .flatpickr-weekdaycontainer {
        display: flex !important;
        width: 100% !important;
    }
    span.flatpickr-weekday {
        flex: 1 1 0 !important;
        width: auto !important;
    }

    /* Distribute the day numbers evenly */
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
        width: 14.2857% !important; /* Exactly 100% divided by 7 days */
        max-width: 14.2857% !important;
        flex-basis: 14.2857% !important;
        margin: 0 !important; /* Remove default margins causing gaps */
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

    /* Leave Dots Container */
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
  document.addEventListener('DOMContentLoaded', function() {
    const leavesData = @json($leavesByDate);

    flatpickr("#flatpickrLeaveCalendar", {
        inline: true,
        // When a user clicks a day on the calendar
        onChange: function(selectedDates, dateStr, instance) {
            // Check if there are leaves on this exact date string (YYYY-MM-DD)
            if (leavesData[dateStr]) {
                showLeavesForDate(dateStr, leavesData[dateStr]);
            } else {
                // Optional: clear selection if no leave is clicked so it doesn't stay highlighted
                instance.clear();
            }
        },
        // Inject colored dots into the calendar days
        onDayCreate: function(dObj, dStr, fp, dayElem) {
            const dateStr = fp.formatDate(dayElem.dateObj, "Y-m-d");

            if (leavesData[dateStr]) {
                // Add cursor pointer to indicate it's clickable
                dayElem.style.cursor = 'pointer';

                // Build the dots
                let dotsHtml = '<div class="leave-dots-container">';
                leavesData[dateStr].forEach(function(leave) {
                    dotsHtml += `<span class="leave-dot" style="background-color: ${leave.color};" title="${leave.name} (${leave.leave_type})"></span>`;
                });
                dotsHtml += '</div>';

                // Inject into the day cell
                dayElem.innerHTML += dotsHtml;
            }
        }
    });

    // Render the bootstrap modal with the employee list
    function showLeavesForDate(dateStr, dailyLeaves) {
        let html = '<ul class="list-group list-group-flush">';

        dailyLeaves.forEach(function(leave) {
            html += '<li class="list-group-item d-flex align-items-center py-3">';
            html += `<span class="rounded-circle me-3 flex-shrink-0" style="width: 14px; height: 14px; background-color: ${leave.color};"></span>`;
            html += '<div>';
            html += `<div class="fw-bold mb-1">${leave.name}</div>`;
            html += `<div class="small text-muted"><i class="bi bi-tag me-1"></i>${leave.leave_type}</div>`;
            html += '</div></li>';
        });

        html += '</ul>';

        // Format the date nicely for the modal title
        const dateObj = new Date(dateStr);
        const options = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' };
        document.getElementById('modalDateLabel').innerText = dateObj.toLocaleDateString('en-US', options);

        document.getElementById('modalLeavesList').innerHTML = html;
        var modal = new bootstrap.Modal(document.getElementById('dailyLeavesModal'));
        modal.show();
    }
  });
</script>
@endpush
