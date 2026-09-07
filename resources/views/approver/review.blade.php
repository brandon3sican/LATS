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
                $requiresOtp = auth()->user()->hasAnyRole(['approver_chief_personnel', 'approver_ard_ms']);
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
                            <button type="button" class="btn btn-success px-4 fw-bold" id="approveBtn" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="bi bi-check-circle me-1"></i> Approve
                            </button>
                            <button type="button" class="btn btn-warning px-4 fw-bold" id="returnBtn" data-bs-toggle="modal" data-bs-target="#returnModal">
                                <i class="bi bi-arrow-return-left me-1"></i> Return
                            </button>
                            <button type="button" class="btn btn-danger px-4 fw-bold" id="disapproveBtn" data-bs-toggle="modal" data-bs-target="#disapproveModal">
                                <i class="bi bi-x-circle me-1"></i> Disapprove
                            </button>
                        </div>
                    </div>
                </div>
                
                {{-- APPROVE MODAL --}}
                <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title" id="approveModalLabel">
                                    <i class="bi bi-check-circle me-2"></i> Confirm Approval
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                {{-- Step 1: Initial Confirmation --}}
                                <div id="approvalStep1">
                                <div class="alert alert-success border-0 bg-success bg-opacity-10">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>You are about to APPROVE</strong> this leave application.
                                </div>
                                
                                <p class="mb-3">By approving this application, you confirm that:</p>
                                <ul class="mb-3">
                                    <li>The leave request complies with office policies</li>
                                    <li>The employee has sufficient leave credits</li>
                                    <li>All required documentation has been reviewed</li>
                                    <li>The dates and leave type are appropriate</li>
                                </ul>
                                
                                <div class="alert alert-warning small">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    <strong>Important:</strong> This action will be recorded in the approval timeline and cannot be undone.
                                </div>
                                
                                <button type="button" class="btn btn-primary w-100 mb-3" id="proceedToSignature">
                                    <i class="bi bi-arrow-right me-1"></i> Proceed to Signature
                                </button>
                                </div>
                                
                                <div id="signatureSection" class="d-none">
                                    <div class="alert alert-info border-0 bg-info bg-opacity-10">
                                        <i class="bi bi-pencil-square me-2"></i>
                                        <strong>Step 2:</strong> Provide your signature to confirm approval
                                    </div>
                                    
                                    {{-- Signature Options --}}
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Signature Method</label>
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" name="signatureMethod" id="useExisting" value="existing" autocomplete="off" checked>
                                            <label class="btn btn-outline-primary" for="useExisting">
                                                <i class="bi bi-file-earmark-check me-1"></i> Use Saved Signature
                                            </label>
                                            
                                            <input type="radio" class="btn-check" name="signatureMethod" id="drawNew" value="draw" autocomplete="off">
                                            <label class="btn btn-outline-primary" for="drawNew">
                                                <i class="bi bi-pencil me-1"></i> Draw New
                                            </label>
                                            
                                            <input type="radio" class="btn-check" name="signatureMethod" id="uploadNew" value="upload" autocomplete="off">
                                            <label class="btn btn-outline-primary" for="uploadNew">
                                                <i class="bi bi-upload me-1"></i> Upload New
                                            </label>
                                        </div>
                                    </div>

                                {{-- Existing Signature Display --}}
                                <div id="existingSignatureSection" class="mb-3">
                                    @if(auth()->user()->signature_path)
                                        <div class="card border-primary">
                                            <div class="card-body text-center">
                                                <img src="{{ route('signatures.show', ['path' => auth()->user()->signature_path]) }}" alt="Your saved signature" class="img-fluid" style="max-height: 100px;">
                                                <p class="text-muted small mt-2 mb-0">Your saved signature</p>
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            No saved signature found. Please draw or upload a new signature.
                                        </div>
                                    @endif
                                </div>

                                {{-- Draw Signature Section --}}
                                <div id="drawSignatureSection" class="mb-3 d-none">
                                    <p class="text-muted mb-2">Draw your signature below:</p>
                                    <div class="card border" style="background: white;">
                                        <canvas id="signaturePad" width="600" height="200" style="width: 100%; height: 200px; touch-action: none; cursor: crosshair;"></canvas>
                                    </div>
                                    <div class="mt-2 d-flex gap-2">
                                        <button type="button" id="clearSignature" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eraser me-1"></i> Clear Signature
                                        </button>
                                        <small class="text-muted align-self-center ms-auto">This signature will be saved for future use</small>
                                    </div>
                                </div>

                                {{-- Upload Signature Section --}}
                                <div id="uploadSignatureSection" class="mb-3 d-none">
                                    <p class="text-muted mb-2">Upload your signature image:</p>
                                    <div class="mb-2">
                                        <input type="file" id="signatureUpload" class="form-control" accept="image/*">
                                        <small class="text-muted">Supported formats: PNG, JPG, JPEG (Max 2MB)</small>
                                    </div>
                                    <div id="uploadPreview" class="card border d-none">
                                        <div class="card-body text-center">
                                            <img id="uploadedSignature" src="" alt="Uploaded signature" class="img-fluid" style="max-height: 100px;">
                                        </div>
                                    </div>
                                    <small class="text-muted">This signature will be saved for future use</small>
                                </div>
                                
                                <input type="hidden" name="signature" id="signatureData">
                                
                                <div class="d-flex gap-2 mt-3">
                                    <button type="button" class="btn btn-outline-secondary" id="backToStep1">
                                        <i class="bi bi-arrow-left me-1"></i> Back
                                    </button>
                                    <button type="button" class="btn btn-primary flex-grow-1" id="proceedToFinal">
                                        <i class="bi bi-arrow-right me-1"></i> Review & Confirm
                                    </button>
                                </div>
                                </div>
                                
                                {{-- Step 3: OTP Verification (for Chief Personnel and ARD only) --}}
                                @if($requiresOtp)
                                <div id="otpSection" class="d-none">
                                    <div class="alert alert-info border-0 bg-info bg-opacity-10">
                                        <i class="bi bi-shield-lock me-2"></i>
                                        <strong>Step 3:</strong> OTP Verification Required
                                    </div>

                                    <p class="mb-3">For enhanced security, please verify your approval using a one-time password (OTP) sent to your email.</p>

                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Email Address</label>
                                                <div class="form-control bg-light">{{ auth()->user()->email }}</div>
                                                <small class="text-muted">OTP will be sent to this email address</small>
                                            </div>

                                            <div id="otpSendSection">
                                                <button type="button" class="btn btn-primary w-100" id="sendOtpBtn">
                                                    <i class="bi bi-envelope me-1"></i> Send OTP Code
                                                </button>
                                            </div>

                                            <div id="otpInputSection" class="d-none">
                                                <label class="form-label fw-semibold">Enter OTP Code</label>
                                                <input type="text" id="otpCode" class="form-control text-center fs-4 fw-bold" maxlength="6" placeholder="000000" style="letter-spacing: 8px;">
                                                <small class="text-muted d-block mt-2">Enter the 6-digit code sent to your email</small>

                                                <div class="mt-3">
                                                    <button type="button" class="btn btn-link btn-sm text-decoration-none" id="resendOtpBtn" disabled>
                                                        <i class="bi bi-arrow-clockwise me-1"></i> Resend OTP <span id="resendCountdown">(30s)</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="alert alert-warning small">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        <strong>Important:</strong> The OTP code will expire in 5 minutes and can only be used once.
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary" id="backToSignatureFromOtp">
                                            <i class="bi bi-arrow-left me-1"></i> Back
                                        </button>
                                        <button type="button" class="btn btn-primary flex-grow-1" id="verifyOtpBtn" disabled>
                                            <i class="bi bi-shield-check me-1"></i> Verify & Continue
                                        </button>
                                    </div>
                                </div>
                                @endif

                                {{-- Step 4: Final Confirmation --}}
                                <div id="finalConfirmSection" class="d-none">
                                    <div class="alert alert-danger border-0 bg-danger bg-opacity-10">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <strong>FINAL CONFIRMATION REQUIRED</strong>
                                    </div>

                                    <p class="mb-3">Please review the following before final approval:</p>

                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="fw-bold mb-2">Approval Effects:</h6>
                                            <ul class="mb-0 small">
                                                <li>This application will move to the next approval step</li>
                                                <li>Your digital signature will be permanently recorded</li>
                                                <li>The employee will be notified of your approval</li>
                                                <li>This action will be logged in the system audit trail</li>
                                                <li>If this is the final approval step, the leave will be officially approved</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="alert alert-warning small">
                                        <i class="bi bi-shield-exclamation me-1"></i>
                                        <strong>Warning:</strong> Once confirmed, this action cannot be undone. Make sure you have thoroughly reviewed the application.
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary" id="backToSignature">
                                            <i class="bi bi-arrow-left me-1"></i> Back
                                        </button>
                                        <button type="button" class="btn btn-success flex-grow-1 fw-bold" id="finalConfirmBtn">
                                            <i class="bi bi-check-circle me-1"></i> I Understand - Confirm Approval
                                        </button>
                                    </div>
                                </div>
                                @error('signature')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle me-1"></i> Cancel
                                </button>
                                <button type="button" class="btn btn-success px-4 fw-bold d-none" id="confirmApproveBtn">
                                    <i class="bi bi-check-circle me-1"></i> Confirm & Approve
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RETURN MODAL --}}
                <div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-warning text-dark">
                                <h5 class="modal-title" id="returnModalLabel">
                                    <i class="bi bi-arrow-return-left me-2"></i> Confirm Return
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-warning border-0 bg-warning bg-opacity-10">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>You are about to RETURN</strong> this leave application.
                                </div>
                                
                                <p class="mb-3">By returning this application, you are sending it back to the employee for corrections or additional information.</p>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Reason for Return <span class="text-danger">*</span></label>
                                    <textarea id="returnReason" class="form-control" rows="3" placeholder="Please explain why you are returning this application..."></textarea>
                                    <small class="text-muted">This reason will be visible to the employee.</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle me-1"></i> Cancel
                                </button>
                                <button type="button" class="btn btn-warning px-4 fw-bold" id="confirmReturnBtn">
                                    <i class="bi bi-arrow-return-left me-1"></i> Confirm & Return
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DISAPPROVE MODAL --}}
                <div class="modal fade" id="disapproveModal" tabindex="-1" aria-labelledby="disapproveModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="disapproveModalLabel">
                                    <i class="bi bi-x-circle me-2"></i> Confirm Disapproval
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-danger border-0 bg-danger bg-opacity-10">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>You are about to DISAPPROVE</strong> this leave application.
                                </div>
                                
                                <p class="mb-3">By disapproving this application, you are rejecting the leave request entirely. This action cannot be undone.</p>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Reason for Disapproval <span class="text-danger">*</span></label>
                                    <textarea id="disapproveReason" class="form-control" rows="3" placeholder="Please explain why you are disapproving this application..."></textarea>
                                    <small class="text-muted">This reason will be visible to the employee.</small>
                                </div>
                                
                                <div class="alert alert-warning small">
                                    <i class="bi bi-exclamation-circle me-1"></i>
                                    <strong>Warning:</strong> This will permanently reject the leave application.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle me-1"></i> Cancel
                                </button>
                                <button type="button" class="btn btn-danger px-4 fw-bold" id="confirmDisapproveBtn">
                                    <i class="bi bi-x-circle me-1"></i> Confirm & Disapprove
                                </button>
                            </div>
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
{{-- Flatpickr CSS and JS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>
  /* Optional CSS to make the inline calendar look cleaner */
  .flatpickr-calendar.inline {
      box-shadow: none !important;
      border: 1px solid #dee2e6;
      margin-top: 0.25rem;
  }
  /* Signature pad styling */
  #signaturePad {
      border: 1px solid #dee2e6;
      border-radius: 4px;
      background-color: #fff;
      cursor: crosshair;
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

    // Modal Functionality
    @if($canAction)
        // APPROVE MODAL with Signature Options (Required for all users)
        // Custom canvas drawing implementation
        let signatureCtx = null;
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;
        const approveModal = document.getElementById('approveModal');
        let currentSignatureMethod = 'existing';
        
        function initSignatureCanvas() {
            const canvas = document.getElementById('signaturePad');
            if (!canvas) return;
            signatureCtx = canvas.getContext('2d');
            // Use the explicit width/height attributes from HTML
            signatureCtx.strokeStyle = '#000';
            signatureCtx.lineWidth = 2;
            signatureCtx.lineCap = 'round';
            signatureCtx.lineJoin = 'round';
        }
        
        function getSignaturePos(e) {
            const canvas = document.getElementById('signaturePad');
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return {
                x: (clientX - rect.left) * scaleX,
                y: (clientY - rect.top) * scaleY
            };
        }
        
        function startSignatureDrawing(e) {
            e.preventDefault();
            isDrawing = true;
            const pos = getSignaturePos(e);
            lastX = pos.x;
            lastY = pos.y;
        }
        
        function drawSignature(e) {
            if (!isDrawing) return;
            e.preventDefault();
            const pos = getSignaturePos(e);
            
            signatureCtx.beginPath();
            signatureCtx.moveTo(lastX, lastY);
            signatureCtx.lineTo(pos.x, pos.y);
            signatureCtx.stroke();
            
            lastX = pos.x;
            lastY = pos.y;
        }
        
        function stopSignatureDrawing(e) {
            if (isDrawing) {
                isDrawing = false;
                const canvas = document.getElementById('signaturePad');
                document.getElementById('signatureData').value = canvas.toDataURL();
            }
        }
        
        // Signature method switching
        const signatureMethodRadios = document.querySelectorAll('input[name="signatureMethod"]');
        const existingSection = document.getElementById('existingSignatureSection');
        const drawSection = document.getElementById('drawSignatureSection');
        const uploadSection = document.getElementById('uploadSignatureSection');
        
        signatureMethodRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                currentSignatureMethod = this.value;
                
                // Hide all sections first
                existingSection.classList.add('d-none');
                drawSection.classList.add('d-none');
                uploadSection.classList.add('d-none');
                
                // Show selected section
                if (currentSignatureMethod === 'existing') {
                    existingSection.classList.remove('d-none');
                } else if (currentSignatureMethod === 'draw') {
                    drawSection.classList.remove('d-none');
                    // Initialize canvas when draw section is shown
                    setTimeout(initSignatureCanvas, 100);
                } else if (currentSignatureMethod === 'upload') {
                    uploadSection.classList.remove('d-none');
                }
            });
        });
        
        // Initialize signature canvas when approve modal is shown
        if (approveModal) {
            approveModal.addEventListener('shown.bs.modal', function() {
                // Reset modal to step 1
                document.getElementById('approvalStep1').classList.remove('d-none');
                document.getElementById('signatureSection').classList.add('d-none');
                document.getElementById('finalConfirmSection').classList.add('d-none');

                // Reset OTP section if exists
                const otpSection = document.getElementById('otpSection');
                if (otpSection) {
                    otpSection.classList.add('d-none');
                    const otpSendSection = document.getElementById('otpSendSection');
                    const otpInputSection = document.getElementById('otpInputSection');
                    if (otpSendSection) otpSendSection.classList.remove('d-none');
                    if (otpInputSection) otpInputSection.classList.add('d-none');
                    const otpCode = document.getElementById('otpCode');
                    if (otpCode) otpCode.value = '';
                    const verifyOtpBtn = document.getElementById('verifyOtpBtn');
                    if (verifyOtpBtn) verifyOtpBtn.disabled = true;
                    if (typeof resendTimer !== 'undefined' && resendTimer) {
                        clearInterval(resendTimer);
                        resendTimer = null;
                    }
                }

                // Reset to existing signature by default
                document.getElementById('useExisting').checked = true;
                currentSignatureMethod = 'existing';

                // Show existing section
                existingSection.classList.remove('d-none');
                drawSection.classList.add('d-none');
                uploadSection.classList.add('d-none');

                // Initialize signature canvas
                initSignatureCanvas();

                // Check if user has existing signature
                const hasExistingSignature = {{ auth()->user()->signature_path ? 'true' : 'false' }};
                if (!hasExistingSignature) {
                    // If no existing signature, switch to draw by default
                    document.getElementById('drawNew').checked = true;
                    document.getElementById('drawNew').dispatchEvent(new Event('change'));
                }
            });

            // Clear signature when modal is hidden
            approveModal.addEventListener('hidden.bs.modal', function() {
                document.getElementById('signatureData').value = '';
                document.getElementById('signatureUpload').value = '';
                document.getElementById('uploadPreview').classList.add('d-none');

                // Reset modal to step 1
                document.getElementById('approvalStep1').classList.remove('d-none');
                document.getElementById('signatureSection').classList.add('d-none');
                document.getElementById('finalConfirmSection').classList.add('d-none');

                // Reset OTP section if exists
                const otpSection = document.getElementById('otpSection');
                if (otpSection) {
                    otpSection.classList.add('d-none');
                    const otpCode = document.getElementById('otpCode');
                    if (otpCode) otpCode.value = '';
                    if (resendTimer) {
                        clearInterval(resendTimer);
                        resendTimer = null;
                    }
                }
            });
        }

        // Clear signature button
        document.getElementById('clearSignature')?.addEventListener('click', function() {
            const canvas = document.getElementById('signaturePad');
            if (canvas && signatureCtx) {
                signatureCtx.clearRect(0, 0, canvas.width, canvas.height);
                document.getElementById('signatureData').value = '';
            }
        });
        
        // Attach drawing events to signature canvas
        const signatureCanvas = document.getElementById('signaturePad');
        if (signatureCanvas) {
            signatureCanvas.addEventListener('mousedown', startSignatureDrawing);
            signatureCanvas.addEventListener('mousemove', drawSignature);
            signatureCanvas.addEventListener('mouseup', stopSignatureDrawing);
            signatureCanvas.addEventListener('mouseout', stopSignatureDrawing);
            signatureCanvas.addEventListener('touchstart', startSignatureDrawing);
            signatureCanvas.addEventListener('touchmove', drawSignature);
            signatureCanvas.addEventListener('touchend', stopSignatureDrawing);
        }

        // Handle signature upload preview
        document.getElementById('signatureUpload')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    this.value = '';
                    return;
                }
                
                // Validate file type
                if (!file.type.match('image.*')) {
                    alert('Please select an image file');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('uploadedSignature').src = e.target.result;
                    document.getElementById('uploadPreview').classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            }
        });

        // Handle approve button click
        document.getElementById('proceedToSignature')?.addEventListener('click', function() {
            // Hide step 1, show signature section
            document.getElementById('approvalStep1').classList.add('d-none');
            document.getElementById('signatureSection').classList.remove('d-none');
        });

        // Handle back to step 1
        document.getElementById('backToStep1')?.addEventListener('click', function() {
            // Show step 1, hide signature section
            document.getElementById('approvalStep1').classList.remove('d-none');
            document.getElementById('signatureSection').classList.add('d-none');
        });

        // Handle proceed to final confirmation (or OTP for Chief Personnel/ARD)
        document.getElementById('proceedToFinal')?.addEventListener('click', function() {
            let signatureData = null;

            if (currentSignatureMethod === 'existing') {
                // Check if user has existing signature
                const hasExistingSignature = {{ auth()->user()->signature_path ? 'true' : 'false' }};
                if (!hasExistingSignature) {
                    alert('No saved signature found. Please draw or upload a signature.');
                    return false;
                }
                // Use existing signature path (controller will handle it)
                signatureData = 'existing';
            } else if (currentSignatureMethod === 'draw') {
                const canvas = document.getElementById('signaturePad');
                const signatureDataValue = document.getElementById('signatureData').value;
                if (!signatureDataValue) {
                    alert('Please draw your signature before proceeding.');
                    return false;
                }
                signatureData = signatureDataValue;
            } else if (currentSignatureMethod === 'upload') {
                const uploadInput = document.getElementById('signatureUpload');
                if (!uploadInput.files || uploadInput.files.length === 0) {
                    alert('Please upload your signature before proceeding.');
                    return false;
                }
                // Get the base64 data from the preview
                const uploadedSignature = document.getElementById('uploadedSignature');
                if (uploadedSignature.src) {
                    signatureData = uploadedSignature.src;
                } else {
                    alert('Please upload a valid signature image.');
                    return false;
                }
            }

            // Set signature data
            document.getElementById('signatureData').value = signatureData;

            // Check if user requires OTP verification
            const requiresOtp = {{ $requiresOtp ? 'true' : 'false' }};

            if (requiresOtp) {
                // Show OTP section instead of final confirmation
                document.getElementById('signatureSection').classList.add('d-none');
                document.getElementById('otpSection').classList.remove('d-none');
            } else {
                // Show final confirmation directly
                document.getElementById('signatureSection').classList.add('d-none');
                document.getElementById('finalConfirmSection').classList.remove('d-none');
            }
        });

        // Handle back to signature
        document.getElementById('backToSignature')?.addEventListener('click', function() {
            // Show signature section, hide final confirmation
            document.getElementById('signatureSection').classList.remove('d-none');
            document.getElementById('finalConfirmSection').classList.add('d-none');
        });

        // OTP Functionality (for Chief Personnel and ARD)
        const requiresOtp = {{ $requiresOtp ? 'true' : 'false' }};
        const leaveId = {{ $leave->id }};
        let resendCountdown = 30;
        let resendTimer = null;

        // Countdown timer for resend button
        window.startResendCountdown = function() {
            resendCountdown = 30;
            const resendBtn = document.getElementById('resendOtpBtn');
            if (resendBtn) {
                resendBtn.disabled = true;
                resendBtn.innerHTML = `<i class="bi bi-arrow-clockwise me-1"></i> Resend OTP (${resendCountdown}s)`;
            }

            if (resendTimer) {
                clearInterval(resendTimer);
            }

            resendTimer = setInterval(() => {
                resendCountdown--;
                if (resendCountdown <= 0) {
                    clearInterval(resendTimer);
                    resendTimer = null;
                    if (resendBtn) {
                        resendBtn.disabled = false;
                        resendBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Resend OTP';
                    }
                } else {
                    if (resendBtn) {
                        resendBtn.innerHTML = `<i class="bi bi-arrow-clockwise me-1"></i> Resend OTP (${resendCountdown}s)`;
                    }
                }
            }, 1000);
        };

        if (requiresOtp) {

        // Send OTP
        const sendOtpBtn = document.getElementById('sendOtpBtn');
        if (sendOtpBtn) {
            sendOtpBtn.addEventListener('click', function() {
                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Sending...';

                // Get signature data
                const signatureData = document.getElementById('signatureData').value;

                fetch(`{{ route('approver.otp.send', $leave->id) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        signature: signatureData
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const otpSendSection = document.getElementById('otpSendSection');
                        const otpInputSection = document.getElementById('otpInputSection');
                        const verifyOtpBtn = document.getElementById('verifyOtpBtn');
                        if (otpSendSection) otpSendSection.classList.add('d-none');
                        if (otpInputSection) otpInputSection.classList.remove('d-none');
                        if (verifyOtpBtn) verifyOtpBtn.disabled = false;
                        startResendCountdown();
                    } else {
                        alert(data.message || 'Failed to send OTP. Please try again.');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-envelope me-1"></i> Send OTP Code';
                    }
                })
                .catch(error => {
                    console.error('Error sending OTP:', error);
                    alert('An error occurred. Please try again.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-envelope me-1"></i> Send OTP Code';
                });
            });
        }

        // Verify OTP
        const verifyOtpBtn = document.getElementById('verifyOtpBtn');
        if (verifyOtpBtn) {
            verifyOtpBtn.addEventListener('click', function() {
                const otpCodeInput = document.getElementById('otpCode');
                const otpCode = otpCodeInput ? otpCodeInput.value.trim() : '';

                if (otpCode.length !== 6) {
                    alert('Please enter the 6-digit OTP code.');
                    return;
                }

                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Verifying...';

                fetch(`{{ route('approver.otp.verify', $leave->id) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        otp: otpCode,
                        leave_id: leaveId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Store the temporary signature for final approval
                        if (data.temporary_signature) {
                            document.getElementById('signatureData').value = data.temporary_signature;
                        }

                        // Hide OTP section, show final confirmation
                        const otpSection = document.getElementById('otpSection');
                        const finalConfirmSection = document.getElementById('finalConfirmSection');
                        if (otpSection) otpSection.classList.add('d-none');
                        if (finalConfirmSection) finalConfirmSection.classList.remove('d-none');
                    } else {
                        alert(data.message || 'Invalid OTP. Please try again.');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-shield-check me-1"></i> Verify & Continue';
                    }
                })
                .catch(error => {
                    console.error('Error verifying OTP:', error);
                    alert('An error occurred. Please try again.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-shield-check me-1"></i> Verify & Continue';
                });
            });
        }

        // Resend OTP
        const resendOtpBtn = document.getElementById('resendOtpBtn');
        if (resendOtpBtn) {
            resendOtpBtn.addEventListener('click', function() {
                if (this.disabled) return;

                this.disabled = true;
                this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Sending...';

                fetch(`{{ route('approver.otp.resend', $leave->id) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('New OTP sent successfully!');
                        startResendCountdown();
                    } else {
                        alert(data.message || 'Failed to resend OTP. Please try again.');
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Resend OTP';
                    }
                })
                .catch(error => {
                    console.error('Error resending OTP:', error);
                    alert('An error occurred. Please try again.');
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Resend OTP';
                });
            });
        }

        // Back to signature from OTP
        const backToSignatureFromOtp = document.getElementById('backToSignatureFromOtp');
        if (backToSignatureFromOtp) {
            backToSignatureFromOtp.addEventListener('click', function() {
                document.getElementById('otpSection').classList.add('d-none');
                document.getElementById('signatureSection').classList.remove('d-none');
                // Reset OTP input
                const otpCode = document.getElementById('otpCode');
                if (otpCode) otpCode.value = '';
                const otpSendSection = document.getElementById('otpSendSection');
                const otpInputSection = document.getElementById('otpInputSection');
                if (otpSendSection) otpSendSection.classList.remove('d-none');
                if (otpInputSection) otpInputSection.classList.add('d-none');
                const verifyOtpBtn = document.getElementById('verifyOtpBtn');
                if (verifyOtpBtn) verifyOtpBtn.disabled = true;
                if (resendTimer) {
                    clearInterval(resendTimer);
                    resendTimer = null;
                }
            });
        }

        // OTP input formatting (auto-format to 6 digits)
        const otpInput = document.getElementById('otpCode');
        if (otpInput) {
            otpInput.addEventListener('input', function(e) {
                // Remove non-digit characters
                this.value = this.value.replace(/\D/g, '').substring(0, 6);
            });
        }
        }

        // Handle final confirmation button
        const finalConfirmBtn = document.getElementById('finalConfirmBtn');
        if (finalConfirmBtn) {
            finalConfirmBtn.addEventListener('click', function(e) {
                e.preventDefault();

                if (requiresOtp) {
                    // For OTP users, submit to the complete-with-otp endpoint
                    const form = document.getElementById('actionForm');
                    form.action = `{{ route('approver.leaves.completeWithOtp', $leave->id) }}`;
                    form.method = 'POST';

                    // Add action field to form
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'approved';
                    form.appendChild(actionInput);

                    // Add OTP code to form
                    const otpCodeInput = document.getElementById('otpCode');
                    if (otpCodeInput && otpCodeInput.value) {
                        const otpInput = document.createElement('input');
                        otpInput.type = 'hidden';
                        otpInput.name = 'otp';
                        otpInput.value = otpCodeInput.value;
                        form.appendChild(otpInput);
                    } else {
                        alert('Please enter the OTP code before confirming.');
                        return;
                    }

                    // Submit the form
                    form.submit();
                } else {
                    // For non-OTP users, submit normally
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'approved';
                    document.getElementById('actionForm').appendChild(actionInput);

                    // Submit the form
                    document.getElementById('actionForm').submit();
                }
            });
        }

        // RETURN MODAL
        const returnModal = document.getElementById('returnModal');
        if (returnModal) {
            document.getElementById('confirmReturnBtn')?.addEventListener('click', function() {
                const reason = document.getElementById('returnReason').value.trim();
                if (!reason) {
                    alert('Please provide a reason for returning this application.');
                    return;
                }
                
                // Update the remarks field in the main form
                const remarksField = document.querySelector('textarea[name="remarks"]');
                if (remarksField) {
                    remarksField.value = reason;
                }
                
                // Create hidden input for action and submit
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'returned';
                document.getElementById('actionForm').appendChild(actionInput);
                document.getElementById('actionForm').submit();
            });
            
            // Clear reason when modal is hidden
            returnModal.addEventListener('hidden.bs.modal', function() {
                document.getElementById('returnReason').value = '';
            });
        }

        // DISAPPROVE MODAL
        const disapproveModal = document.getElementById('disapproveModal');
        if (disapproveModal) {
            document.getElementById('confirmDisapproveBtn')?.addEventListener('click', function() {
                const reason = document.getElementById('disapproveReason').value.trim();
                if (!reason) {
                    alert('Please provide a reason for disapproving this application.');
                    return;
                }
                
                // Update the remarks field in the main form
                const remarksField = document.querySelector('textarea[name="remarks"]');
                if (remarksField) {
                    remarksField.value = reason;
                }
                
                // Create hidden input for action and submit
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'disapproved';
                document.getElementById('actionForm').appendChild(actionInput);
                document.getElementById('actionForm').submit();
            });
            
            // Clear reason when modal is hidden
            disapproveModal.addEventListener('hidden.bs.modal', function() {
                document.getElementById('disapproveReason').value = '';
            });
        }
    @endif
  });
</script>
@endpush

@endsection
