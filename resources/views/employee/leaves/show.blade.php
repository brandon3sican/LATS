@extends('layouts.app')

@section('content')
<div class="container py-4">
    @php
        $roleMap = \App\Models\Role::pluck('name', 'key')->toArray();

        $details = $leave->details_json ?? [];
        if (is_string($details)) {
            $decoded = json_decode($details, true);
            $details = is_array($decoded) ? $decoded : [];
        }
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0">Leave Application Details</h3>
            <div class="text-muted">Reference ID: #{{ $leave->id }}</div>
        </div>

        <div class="d-flex gap-2">
            @if(in_array($leave->status, ['pending', 'approved']) && $leave->cancellation_status !== 'pending')
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="bi bi-x-circle me-1"></i> Request Cancellation
                </button>
            @elseif($leave->cancellation_status === 'pending')
                <span class="badge bg-warning text-dark d-flex align-items-center"><i class="bi bi-hourglass-split me-1"></i> Cancellation Pending</span>
            @endif
            @if(in_array($leave->status, ['pending','approved','disapproved','returned'], true))
                <a class="btn btn-outline-primary"
                    href="{{ route('employee.leaves.form6.pdf', $leave->id) }}"
                    target="_blank">
                    <i class="bi bi-printer me-1"></i> Print Form 6
                </a>
            @endif
            <a href="{{ route('employee.leaves.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Leaves
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- LEFT COLUMN: Details --}}
        <div class="col-lg-8">

            {{-- Application Details --}}
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3 fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-file-text me-2 text-primary"></i> Application Details</span>
                    @php
                        $statusBadge = match($leave->status) {
                            'approved' => 'bg-success',
                            'pending' => 'bg-warning text-dark',
                            'returned' => 'bg-info text-dark',
                            'disapproved' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                    @endphp
                    <span class="badge {{ $statusBadge }}">
                        {{ strtoupper($leave->status) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted small">Applicant</div>
                        <div class="col-md-8 fw-semibold">{{ $leave->employee->user->name ?? $leave->employee->full_name ?? '—' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted small">Position & Office</div>
                        <div class="col-md-8">{{ $leave->employee->position_title ?? '—' }} &bull; {{ $leave->office->name ?? '—' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted small">Leave Type</div>
                        <div class="col-md-8 fw-bold text-primary">{{ $leave->leaveType->name ?? '—' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted small">Date Filed</div>
                        <div class="col-md-8">{{ optional($leave->date_filed)->format('M d, Y') ?? optional($leave->created_at)->format('M d, Y') }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted small">Days Requested</div>
                        <div class="col-md-8">
                            <span class="badge bg-secondary fs-6">{{ $leave->working_days_requested ?? '—' }} Day(s)</span>
                        </div>
                    </div>

                    {{-- VISUAL INLINE CALENDAR FOR DATES --}}
                    @if(!empty($details['selected_dates']) && is_array($details['selected_dates']))
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted small">Selected Dates</div>
                        <div class="col-md-8">
                            <input type="text" id="selectedDatesVisual" class="d-none">
                        </div>
                    </div>
                    @endif

                    {{-- DYNAMIC DETAILS DEPENDING ON LEAVE TYPE --}}
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted small">Details of Leave (6.B)</div>
                        <div class="col-md-8">
                            @php
                                $leaveCode = $leave->leaveType->code ?? '';
                            @endphp

                            @if($leaveCode === 'VL')
                                <div class="mb-1">
                                    <span class="fw-semibold">Travel:</span>
                                    @if(!empty($details['abroad']) || ($details['vl_travel'] ?? '') === 'abroad')
                                        Abroad
                                    @elseif(($details['vl_travel'] ?? '') === 'within_ph')
                                        Within the Philippines
                                    @else
                                        N/A
                                    @endif
                                </div>
                                @if(!empty($details['location']))
                                <div><span class="fw-semibold">Location/Destination:</span> {{ $details['location'] }}</div>
                                @endif

                            @elseif($leaveCode === 'SL')
                                <div class="mb-1">
                                    <span class="fw-semibold">Patient Type:</span>
                                    @if(!empty($details['no_consultation']))
                                        Out Patient (No Consultation)
                                    @else
                                        {{ ucwords(str_replace('_', ' ', $details['sl_patient_type'] ?? 'N/A')) }}
                                    @endif
                                </div>
                                @if(!empty($details['illness']))
                                <div><span class="fw-semibold">Illness:</span> {{ $details['illness'] }}</div>
                                @endif

                            @elseif($leaveCode === 'PL')
                                <div class="mb-1">
                                    <span class="fw-semibold">Child's Date of Delivery:</span>
                                    {{ !empty($details['pl_delivery_date']) ? \Carbon\Carbon::parse($details['pl_delivery_date'])->format('M d, Y') : 'N/A' }}
                                </div>
                                <div>
                                    <span class="fw-semibold">Marriage Contract Available:</span>
                                    {{ ucwords($details['pl_marriage_contract'] ?? 'N/A') }}
                                </div>

                            @elseif($leaveCode === 'ML')
                                <div class="mb-1">
                                    <span class="fw-semibold">Expected Date of Delivery:</span>
                                    {{ !empty($details['ml_edd']) ? \Carbon\Carbon::parse($details['ml_edd'])->format('M d, Y') : 'N/A' }}
                                </div>
                                <div>
                                    <span class="fw-semibold">CS Form 6a (Allocation) Needed:</span>
                                    {{ ucwords($details['ml_need_cs6a'] ?? 'N/A') }}
                                </div>

                            @elseif($leaveCode === 'SPL')
                                <div class="mb-1">
                                    <span class="fw-semibold">Travel:</span>
                                    {{ ($details['spl_travel'] ?? '') === 'abroad' ? 'Abroad' : 'Within the Philippines' }}
                                </div>
                                @if(!empty($details['spl_location']))
                                <div><span class="fw-semibold">Destination:</span> {{ $details['spl_location'] }}</div>
                                @endif

                            @elseif($leaveCode === 'SOLO')
                                <div class="mb-1"><span class="fw-semibold">Solo Parent ID No:</span> {{ $details['solo_id_no'] ?? 'N/A' }}</div>
                                <div>
                                    <span class="fw-semibold">ID Valid Until:</span>
                                    {{ !empty($details['solo_id_valid_until']) ? \Carbon\Carbon::parse($details['solo_id_valid_until'])->format('M d, Y') : 'N/A' }}
                                </div>

                            @elseif($leaveCode === 'STUDY')
                                <div class="mb-1">
                                    <span class="fw-semibold">Purpose:</span>
                                    @if(($details['study_purpose'] ?? '') === 'masters') Completion of Master's Degree
                                    @elseif(($details['study_purpose'] ?? '') === 'bar_board_review') BAR/Board Examination Review
                                    @else Other
                                    @endif
                                </div>
                                @if(!empty($details['study_other']))
                                <div><span class="fw-semibold">Specific Purpose:</span> {{ $details['study_other'] }}</div>
                                @endif

                            @elseif($leaveCode === 'VAWC')
                                <div><span class="fw-semibold">Supporting Document:</span> {{ strtoupper(str_replace('_', ' ', $details['vawc_support'] ?? 'N/A')) }}</div>

                            @elseif($leaveCode === 'REHAB')
                                <div class="mb-1">
                                    <span class="fw-semibold">Accident Date:</span>
                                    {{ !empty($details['rehab_accident_date']) ? \Carbon\Carbon::parse($details['rehab_accident_date'])->format('M d, Y') : 'N/A' }}
                                </div>
                                <div><span class="fw-semibold">Physician:</span> {{ ucwords($details['rehab_physician'] ?? 'N/A') }}</div>

                            @elseif($leaveCode === 'WOMEN')
                                <div>
                                    <span class="fw-semibold">Surgery Date:</span>
                                    {{ !empty($details['women_surgery_date']) ? \Carbon\Carbon::parse($details['women_surgery_date'])->format('M d, Y') : 'N/A' }}
                                </div>

                            @elseif($leaveCode === 'CALAMITY')
                                <div class="mb-1"><span class="fw-semibold">Calamity/Disaster:</span> {{ $details['calamity_name'] ?? 'N/A' }}</div>
                                <div><span class="fw-semibold">Affected Area:</span> {{ $details['calamity_area'] ?? 'N/A' }}</div>

                            @elseif($leaveCode === 'MON')
                                <div><span class="fw-semibold">Reason for Monetization:</span> {{ $details['mon_reason'] ?? 'N/A' }}</div>

                            @elseif($leaveCode === 'TL')
                                <div class="mb-1">
                                    <span class="fw-semibold">Separation Date:</span>
                                    {{ !empty($details['tl_separation_date']) ? \Carbon\Carbon::parse($details['tl_separation_date'])->format('M d, Y') : 'N/A' }}
                                </div>
                                <div><span class="fw-semibold">Separation Type:</span> {{ ucwords($details['tl_type'] ?? 'N/A') }}</div>

                            @elseif($leaveCode === 'ADOPT')
                                <div><span class="fw-semibold">PAPA Ref No:</span> {{ $details['adopt_papa_ref'] ?? 'N/A' }}</div>
                            @endif

                            {{-- General Reason & Notes (Always print if available) --}}
                            @if(!empty($details['reason']))
                                <div class="mt-2 text-muted small">General Reason:</div>
                                <div>{{ $details['reason'] }}</div>
                            @endif

                            @if(!empty($details['notes']))
                                <div class="mt-2 text-muted small">Additional Notes:</div>
                                <div>{{ $details['notes'] }}</div>
                            @endif

                            @if(empty($details))
                                <span class="text-muted">No additional details provided.</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4 text-muted small">Commutation (6.D)</div>
                        <div class="col-md-8 fw-semibold">
                            {{ $leave->commutation === 'requested' ? 'Requested' : 'Not Requested' }}
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- RIGHT COLUMN: Attachments & Timeline --}}
        <div class="col-lg-4">

            {{-- Attachments --}}
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-paperclip me-2 text-primary"></i> Attachments
                </div>
                <div class="card-body">
                    @if(!isset($leave->attachments) || $leave->attachments->count() === 0)
                        <div class="text-muted small text-center py-3">No attachments uploaded.</div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($leave->attachments as $att)
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div class="text-truncate me-2">
                                        <i class="bi bi-file-earmark me-2 text-muted"></i>
                                        <span title="{{ $att->original_name }}" class="small">{{ $att->original_name }}</span>
                                    </div>
                                    <div class="btn-group">
                                        <a href="{{ route('attachments.preview', $att->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Preview">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('attachments.download', $att->id) }}" class="btn btn-sm btn-outline-primary" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            {{-- Approval Timeline --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-clock-history me-2 text-primary"></i> Approval Progress
                </div>
                <div class="card-body">
                    @if(empty($timeline) || $timeline->count() === 0)
                        <div class="text-muted small text-center py-3">No approval steps configured for this office.</div>
                    @else
                        <div class="position-relative ps-4 ms-2" style="border-left: 2px solid #e9ecef;">
                            @foreach($timeline as $t)
                                <div class="mb-4 position-relative">
                                    {{-- Status Dot --}}
                                    @php
                                        $displayRole = $roleMap[$t['role_key']] ?? $t['title'] ?? $t['role_key'];
                                        $dotColor = match($t['state']) {
                                            'approved' => 'bg-success',
                                            'returned' => 'bg-info',
                                            'disapproved' => 'bg-danger',
                                            'current' => 'bg-primary border border-2 border-white shadow',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="position-absolute top-0 start-0 translate-middle p-2 rounded-circle {{ $dotColor }}" style="left: -17px !important;"></span>

                                    <div class="fw-bold">(Step {{ $t['step_order'] }}) {{ $displayRole }}</div>

                                    @if($t['state'] === 'upcoming')
                                        <div class="text-muted small">Pending...</div>
                                    @elseif($t['state'] === 'current')
                                        <div class="text-primary small fw-semibold">Currently Reviewing</div>
                                    @else
                                        <div class="small fw-semibold text-{{ $t['state'] === 'approved' ? 'success' : ($t['state'] === 'returned' ? 'info' : 'danger') }}">
                                            {{ ucfirst($t['state']) }} {{ !empty($t['actor']) ? 'by ' . $t['actor'] : '' }}
                                        </div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            {{ \Carbon\Carbon::parse($t['acted_at'])->format('M d, Y h:i A') }}
                                        </div>
                                        @if(!empty($t['remarks']) && $t['remarks'] !== 'Processed by Chief Personnel')
                                            <div class="mt-1 p-2 bg-light rounded small border-start border-3 border-secondary">
                                                "{{ $t['remarks'] }}"
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Request Cancelation --}}
            <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form action="{{ route('employee.leaves.cancel', $leave->id) }}" method="POST">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">Request Leave Cancellation</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to request cancellation for this leave? This request will be sent to the Personnel for approval.</p>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Reason for Cancellation</label>
                                    <textarea name="cancellation_reason" class="form-control" rows="3" required placeholder="Please provide a valid reason..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-danger">Submit Request</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
{{-- Flatpickr CSS & JS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>
  /* Optional CSS to make the inline calendar look cleaner */
  .flatpickr-calendar.inline {
      box-shadow: none !important;
      border: 1px solid #dee2e6;
      margin-top: 0.25rem;
  }
</style>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    @if(!empty($details['selected_dates']) && is_array($details['selected_dates']))
        const originalDates = {!! json_encode($details['selected_dates']) !!};

        flatpickr("#selectedDatesVisual", {
            inline: true,
            mode: "multiple",
            defaultDate: originalDates,
            // Automatically locks the dates so they can't be added or removed visually
            onChange: function(selectedDates, dateStr, instance) {
                instance.setDate(originalDates);
            }
        });
    @endif
  });
</script>
@endpush
@endsection
