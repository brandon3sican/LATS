@extends('layouts.app')

@section('content')
<div class="container py-4">
    @if(session('error'))
        <div class="alert alert-danger fw-bold shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        </div>
    @endif
    @if(session('status'))
        <div class="alert alert-success fw-bold shadow-sm">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('status') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger shadow-sm">
            <i class="bi bi-shield-x me-2 fw-bold"></i> <strong>Validation Failed:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0">Review Leave Application</h3>
            <div class="text-muted">Application #{{ $leave->id }} - {{ $leave->employee->user->first_name }} {{ $leave->employee->user->last_name }}</div>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary"
                href="{{ route('approver.leaves.form6.pdf', $leave->id) }}"
                target="_blank">
                <i class="bi bi-printer me-1"></i> Form Preview
            </a>
            <a href="{{ route('approver.inbox') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Inbox
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- LEFT COLUMN: Details & Action --}}
        <div class="col-lg-8">

            {{-- Application Details --}}
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3 fw-bold d-flex justify-content-between">
                    <span><i class="bi bi-file-text me-2 text-primary"></i> Application Details</span>
                    <span class="badge {{ match($leave->status) { 'approved' => 'bg-success', 'pending' => 'bg-warning text-dark', 'returned' => 'bg-info text-dark', 'cancelled' => 'bg-secondary', default => 'bg-danger' } }}">
                        {{ strtoupper($leave->status) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted small">Applicant</div>
                        <div class="col-md-8 fw-semibold">{{ $leave->employee->user->first_name }} {{ $leave->employee->user->last_name }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted small">Position & Division</div>
                        <div class="col-md-8">{{ $leave->employee->position_title }} &bull; {{ $leave->employee->division->name ?? 'N/A' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted small">Leave Type</div>
                        <div class="col-md-8 fw-bold text-primary">{{ $leave->leaveType->name }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted small">Days Requested</div>
                        <div class="col-md-8">
                            <span class="badge bg-secondary fs-6">{{ $leave->working_days_requested }} Day(s)</span>
                        </div>
                    </div>

                    {{-- VISUAL INLINE CALENDAR FOR DATES --}}
                    @if($leave->getDetail('selected_dates') && is_array($leave->getDetail('selected_dates')))
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
                                $details = $leave->details_json ?? [];
                                if (is_string($details)) {
                                    $decoded = json_decode($details, true);
                                    $details = is_array($decoded) ? $decoded : [];
                                }
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
                                    <span class="fw-semibold">Expected Date of Delivery (EDD):</span>
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

            {{-- SETUP ROLES FOR VIEWING/EDITING --}}
            @php
                $isPersonnel = auth()->user()->hasRole('approver_personnel');
                $showCredits = $isPersonnel || auth()->user()->hasRole('approver_chief_personnel') || auth()->user()->hasRole('approver_ard_ms');
            @endphp

            {{-- OPEN MASTER FORM FOR ACTIONS --}}
            @if($canAction)
                <form action="{{ route('approver.leaves.action', $leave->id) }}" method="POST" id="actionForm">
                    @csrf
            @endif

            {{-- 7.A CERTIFICATION OF LEAVE CREDITS --}}
            @if($showCredits)
                <div class="card shadow-sm mb-4 border-primary">
                    <div class="card-header bg-primary text-black fw-bold">
                        <i class="bi bi-file-earmark-check me-2"></i> 7.A CERTIFICATION OF LEAVE CREDITS
                    </div>
                    <div class="card-body">

                        @if($isPersonnel && $canAction)
                            {{-- EDITABLE FORM (For Personnel Only) --}}
                            <p class="text-muted small mb-3">Please fill up the employee's leave credits. This will reflect directly on the final CS Form 6.</p>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">As of (Month/Year)</label>
                                    <input type="text" name="credits_as_of" class="form-control" value="{{ $leave->getDetail('credits_as_of') ?? now()->format('F Y') }}" placeholder="e.g., February 2026">
                                </div>
                            </div>

                            <div class="table-responsive mb-4">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-start">Leave Type</th>
                                            <th>Total Earned</th>
                                            <th>Less this application</th>
                                            <th>Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-start fw-semibold">Vacation Leave</td>
                                            <td><input type="number" step="0.01" name="vl_earned" id="vl_earned" class="form-control text-center" placeholder="0.00" value="{{ $leave->getDetail('vl_earned') }}" oninput="calculateVL()"></td>
                                            <td><input type="number" step="0.01" name="vl_less" id="vl_less" class="form-control text-center" value="{{ $leave->getDetail('vl_less') ?? ($leave->isType('VL') ? $leave->working_days_requested : '0.00') }}"></td>
                                            <td><input type="number" step="0.01" name="vl_balance" id="vl_balance" class="form-control text-center bg-light" placeholder="0.00" value="{{ $leave->getDetail('vl_balance') }}" readonly tabindex="-1"></td>
                                        </tr>
                                        <tr>
                                            <td class="text-start fw-semibold">Sick Leave</td>
                                            <td><input type="number" step="0.01" name="sl_earned" id="sl_earned" class="form-control text-center" placeholder="0.00" value="{{ $leave->getDetail('sl_earned') }}" oninput="calculateSL()"></td>
                                            <td><input type="number" step="0.01" name="sl_less" id="sl_less" class="form-control text-center" value="{{ $leave->getDetail('sl_less') ?? ($leave->isType('SL') ? $leave->working_days_requested : '0.00') }}"></td>
                                            <td><input type="number" step="0.01" name="sl_balance" id="sl_balance" class="form-control text-center bg-light" placeholder="0.00" value="{{ $leave->getDetail('sl_balance') }}" readonly tabindex="-1"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <script>
                                function calculateVL() {
                                    let earned = parseFloat(document.getElementById('vl_earned').value) || 0;
                                    let less = parseFloat(document.getElementById('vl_less').value) || 0;
                                    document.getElementById('vl_balance').value = (earned - less).toFixed(2);
                                }
                                function calculateSL() {
                                    let earned = parseFloat(document.getElementById('sl_earned').value) || 0;
                                    let less = parseFloat(document.getElementById('sl_less').value) || 0;
                                    document.getElementById('sl_balance').value = (earned - less).toFixed(2);
                                }
                                document.addEventListener('DOMContentLoaded', function() {
                                    calculateVL();
                                    calculateSL();

                                    document.getElementById('vl_less').addEventListener('input', calculateVL);
                                    document.getElementById('sl_less').addEventListener('input', calculateSL);
                                });
                            </script>

                        @else
                            {{-- READ-ONLY VIEW (For Chief Personnel & ARD) --}}
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-muted small mb-1">As of (Month/Year)</label>
                                    <div class="fw-bold">{{ $leave->getDetail('credits_as_of') ?? 'Not yet provided' }}</div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered text-center align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-start">Leave Type</th>
                                            <th>Total Earned</th>
                                            <th>Less this application</th>
                                            <th>Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-start fw-semibold">Vacation Leave</td>
                                            <td>{{ number_format((float)($leave->getDetail('vl_earned') ?? 0), 2) }}</td>
                                            <td>{{ number_format((float)($leave->getDetail('vl_less') ?? 0), 2) }}</td>
                                            <td class="bg-light fw-bold text-primary">{{ number_format((float)($leave->getDetail('vl_balance') ?? 0), 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-start fw-semibold">Sick Leave</td>
                                            <td>{{ number_format((float)($leave->getDetail('sl_earned') ?? 0), 2) }}</td>
                                            <td>{{ number_format((float)($leave->getDetail('sl_less') ?? 0), 2) }}</td>
                                            <td class="bg-light fw-bold text-primary">{{ number_format((float)($leave->getDetail('sl_balance') ?? 0), 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- CANCELLATION REQUEST BOX (Only Chief Personnel sees this if pending) --}}
            @if($leave->cancellation_status === 'pending' && auth()->user()->hasRole('approver_personnel'))
                <div class="card shadow-sm border-danger mb-4">
                    <div class="card-header bg-danger text-black fw-bold">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Cancellation Request Pending
                    </div>
                    <div class="card-body">
                        <p class="mb-3"><strong>Reason provided by employee:</strong> <br> "{{ $leave->cancellation_reason }}"</p>

                        <form action="{{ route('approver.leaves.processCancellation', $leave->id) }}" method="POST">
                            @csrf
                            <div class="d-flex gap-2">
                                <button type="submit" name="cancellation_action" value="approved" class="btn btn-danger px-4 fw-bold">
                                    <i class="bi bi-check-circle me-1"></i> Approve Cancellation
                                </button>
                                <button type="submit" name="cancellation_action" value="rejected" class="btn btn-secondary px-4 fw-bold">
                                    <i class="bi bi-x-circle me-1"></i> Reject Cancellation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- ACTION BOX --}}
            @if($canAction)
                <div class="card shadow-sm border-0 bg-light mb-4">
                    <div class="card-body">
                        <h5 class="mb-3 fw-bold"><i class="bi bi-shield-check text-success me-2"></i> Your Action Required</h5>

                        {{-- HIDE Remarks if Personnel (they are just certifying credits) --}}
                        @if(!$isPersonnel)
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Remarks / Comments (Required if Returning/Disapproving)</label>
                                <textarea name="remarks" class="form-control" rows="3" placeholder="Enter your remarks here..."></textarea>
                            </div>
                        @else
                            <input type="hidden" name="remarks" value="Leave credits certified by Personnel">
                        @endif

                        <div class="d-flex gap-2">
                            <button type="submit" name="action" value="approved" class="btn btn-success px-4 fw-bold">
                                <i class="bi bi-check-circle me-1"></i> Approve
                            </button>
                            <button type="submit" name="action" value="returned" class="btn btn-warning px-4 fw-bold">
                                <i class="bi bi-arrow-return-left me-1"></i> Return
                            </button>
                            <button type="submit" name="action" value="disapproved" class="btn btn-danger px-4 fw-bold">
                                <i class="bi bi-x-circle me-1"></i> Disapprove
                            </button>
                        </div>
                    </div>
                </div>
                
                </form> {{-- CLOSE MASTER FORM --}}
            @elseif($leave->status === 'pending')
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i> Waiting for another approver to process this step.
                </div>
            @endif

        </div>

        {{-- RIGHT COLUMN: Employee Details, Attachments & Timeline --}}
        <div class="col-lg-4">

            {{-- Applicant's Leave Details & History --}}
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-person-lines-fill me-2 text-primary"></i> Employee Leave Details
                </div>
                <div class="card-body">
                    {{-- Current Balances --}}
                @if(auth()->user()->hasRole('approver_ard_ms'))
                    <h6 class="fw-semibold mb-3 border-bottom pb-2">Current Leave Balances</h6>
                    @if($credits)
                        <div class="row text-center mb-4">
                            <div class="col-6 border-end">
                                <div class="text-muted small">Vacation Leave</div>
                                <div class="fs-5 fw-bold text-primary">{{ number_format($credits->vacation_leave ?? 0, 2) }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">Sick Leave</div>
                                <div class="fs-5 fw-bold text-primary">{{ number_format($credits->sick_leave ?? 0, 2) }}</div>
                            </div>
                        </div>
                    @else
                        <div class="text-muted small text-center mb-4">No leave credits recorded in system.</div>
                    @endif
                @endif

                    {{-- Recent History --}}
                    <h6 class="fw-semibold mb-3 border-bottom pb-2">Recent Leave History</h6>
                    @if($history->count() > 0)
                        <ul class="list-unstyled mb-0 small">
                            @foreach($history as $hist)
                                <li class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold">{{ $hist->leaveType->name ?? 'Leave' }}</span>
                                        <span class="badge {{ match($hist->status) { 'approved' => 'bg-success', 'pending' => 'bg-warning text-dark', 'returned' => 'bg-info text-dark', 'cancelled' => 'bg-secondary', default => 'bg-danger' } }}" style="font-size: 0.65rem;">
                                            {{ strtoupper($hist->status) }}
                                        </span>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        {{ \Carbon\Carbon::parse($hist->start_date)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($hist->end_date)->format('M d, Y') }}
                                        ({{ $hist->working_days_requested }} days)
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-muted small text-center">No recent leave applications.</div>
                    @endif
                </div>
            </div>

            {{-- Attachments with Preview Button --}}
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-paperclip me-2 text-primary"></i> Attachments
                </div>
                <div class="card-body">
                    @if($leave->attachments->count() > 0)
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
                    @else
                        <div class="text-muted small text-center py-3">No attachments provided.</div>
                    @endif
                </div>
            </div>

            {{-- Approval Timeline --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-clock-history me-2 text-primary"></i> Approval Timeline
                </div>
                <div class="card-body">
                    <div class="position-relative ps-4 ms-2" style="border-left: 2px solid #e9ecef;">
                        @foreach($timeline as $t)
                            <div class="mb-4 position-relative">
                                {{-- Status Dot --}}
                                @php
                                    $dotColor = match($t['state']) {
                                        'approved' => 'bg-success',
                                        'returned' => 'bg-warning',
                                        'disapproved' => 'bg-danger',
                                        'current' => 'bg-primary border border-2 border-white shadow',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="position-absolute top-0 start-0 translate-middle p-2 rounded-circle {{ $dotColor }}" style="left: -17px !important;"></span>

                                <div class="fw-bold">{{ $t['title'] }}</div>

                                @if($t['state'] === 'upcoming')
                                    <div class="text-muted small">Pending...</div>
                                @elseif($t['state'] === 'current')
                                    <div class="text-primary small fw-semibold">Currently Reviewing</div>
                                @else
                                    <div class="small fw-semibold text-{{ $t['state'] === 'approved' ? 'success' : ($t['state'] === 'returned' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($t['state']) }} by {{ $t['actor'] }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        {{ \Carbon\Carbon::parse($t['acted_at'])->format('M d, Y h:i A') }}
                                    </div>
                                    @if($t['remarks'] && $t['remarks'] !== 'Processed by Chief Personnel')
                                        <div class="mt-1 p-2 bg-light rounded small border-start border-3 border-secondary">
                                            "{{ $t['remarks'] }}"
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
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
    @if($leave->getDetail('selected_dates') && is_array($leave->getDetail('selected_dates')))
        const originalDates = {!! json_encode($leave->getDetail('selected_dates')) !!};

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
