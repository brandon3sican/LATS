@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-10">

      <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 class="mb-0">Application for Leave</h3>
        <a href="#" onclick="history.back(); return false;" class="btn btn-outline-secondary btn-sm">Back</a>
      </div>

      @if ($errors->any())
        <div class="alert alert-danger">
          <div class="fw-semibold mb-1">Please fix the following:</div>
          <ul class="mb-0">
            @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('employee.leaves.store') }}" enctype="multipart/form-data" id="leaveForm">
        @csrf

        {{-- BASIC INFO --}}
        <div class="card shadow-sm mb-3">
          <div class="card-body">
            <div class="row g-3">

              <div class="col-md-3">
                <label class="form-label">Type of Leave <span class="text-danger">*</span></label>
                <select class="form-select @error('leave_type_id') is-invalid @enderror" name="leave_type_id" id="leave_type_id" required>
                  <option value="">-- Select --</option>
                  @foreach ($types as $t)
                    <option value="{{ $t->id }}" data-code="{{ $t->code }}" {{ old('leave_type_id') == $t->id ? 'selected' : '' }}>
                      {{ $t->name }} ({{ $t->code }})
                    </option>
                  @endforeach
                </select>
                @error('leave_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              {{-- Flatpickr Dates Input --}}
              <div class="col-md-3">
                <label class="form-label">Leave Dates <span class="text-danger">*</span></label>
                <input type="text" id="leave_dates" name="dates" class="form-control bg-white @error('dates') is-invalid @enderror" value="{{ old('dates') }}" placeholder="Click to select dates..." required readonly>
                @error('dates') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-1 d-none">
                <label class="form-label">No. of Days</label>
                <input type="number" step="0.5" min="0.5" max="365"
                       class="form-control @error('working_days_requested') is-invalid @enderror"
                       name="working_days_requested" id="working_days_requested"
                       value="{{ old('working_days_requested', '') }}" required>
                @error('working_days_requested') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-3">
                <label class="form-label">Monthly Salary <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text">₱</span>
                  <input type="number" step="0.01" min="0" class="form-control @error('details.salary') is-invalid @enderror" name="details[salary]" id="salary" value="{{ old('details.salary') }}" placeholder="0.00" required>
                </div>
                @error('details.salary') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-3">
                <label class="form-label">Commutation</label>
                <select class="form-select @error('commutation') is-invalid @enderror" name="commutation" id="commutation">
                  <option value="">-- Select --</option>
                  <option value="requested" {{ old('commutation') === 'requested' ? 'selected' : '' }}>Requested</option>
                  <option value="not_requested" {{ old('commutation') === 'not_requested' ? 'selected' : '' }}>Not Requested</option>
                </select>
                @error('commutation') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              {{-- NEW: 30 Calendar Days / Terminal Leave Clearance Alert --}}
              <div class="col-12 d-none" id="clearanceAlert">
                <div class="alert alert-warning d-flex align-items-start mb-0 shadow-sm border-warning">
                  <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-warning"></i>
                  <div>
                    <h6 class="fw-bold mb-1">Clearance Required!</h6>
                    <p class="mb-0 small">
                      For leave of absence for <strong>thirty (30) calendar days or more</strong> and <strong>terminal leave</strong>, application shall be accompanied by a clearance from money, property and work-related accountabilities (pursuant to CSC Memorandum Circular No. 2, s. 1985).
                    </p>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="d-flex flex-column gap-3 h-100">

                  {{-- Reason / Purpose --}}
                  <div>
                    <label class="form-label">Reason / Purpose <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('reason') is-invalid @enderror" name="reason" rows="2" required
                              placeholder="Write the reason/purpose for this leave...">{{ old('reason') }}</textarea>
                    @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                  </div>

                  {{-- Required Docs --}}
                  <div class="border rounded p-3 bg-light flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between">
                      <div class="fw-semibold">Required Documents</div>
                      <span class="badge text-bg-secondary" id="reqDocsStatus">Waiting…</span>
                    </div>
                    <div class="small text-muted mt-1">
                      This list updates based on leave type + days + your answers.
                    </div>
                    <ul class="mt-2 mb-0" id="requiredDocsList">
                      <li class="text-muted">Select leave type to see required documents.</li>
                    </ul>
                  </div>

                </div>
              </div>

              {{-- Attachments (Right Column) --}}
              <div class="col-md-6">
                <div class="card shadow-sm border-0 p-0 h-100">
                  <div class="card-header bg-white border rounded-top">
                      <div class="fw-semibold">Attachments</div>
                      <div class="small text-muted">Upload supporting documents (multiple allowed).</div>
                  </div>
                  <div class="card-body border border-top-0 rounded-bottom">
                      <input type="file" class="form-control @error('attachments') is-invalid @enderror"
                          name="attachments[]" id="attachments" multiple>
                      @error('attachments') <div class="invalid-feedback">{{ $message }}</div> @enderror

                      <div class="form-text">Max 5MB per file.</div>

                      <div class="mt-3">
                      <div class="fw-semibold small mb-1">Selected files</div>
                      <ul class="mb-0 small" id="fileList"><li class="text-muted">No files selected.</li></ul>
                      </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        {{-- DETAILS --}}
        <div class="card shadow-sm mb-3">
          <div class="card-header bg-white">
            <div class="fw-semibold">Leave Details</div>
            <div class="small text-muted">Fields change depending on leave type (CS Form 6 based).</div>
          </div>
          <div class="card-body">

            {{-- VL --}}
            <div id="sectionVL" class="d-none">
              <div class="fw-semibold mb-2">Vacation Leave</div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Travel</label>
                  <select class="form-select" name="details[vl_travel]">
                    <option value="">-- Select --</option>
                    <option value="within_ph" {{ old('details.vl_travel') === 'within_ph' ? 'selected' : '' }}>Within the Philippines</option>
                    <option value="abroad" {{ old('details.vl_travel') === 'abroad' ? 'selected' : '' }}>Abroad</option>
                  </select>
                  <div class="form-text">Abroad may require travel authority/clearance.</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Destination / Location</label>
                  <input type="text" class="form-control" name="details[location]" value="{{ old('details.location') }}"
                         placeholder="e.g., Baguio City / Manila / Japan">
                </div>

                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="details_abroad" name="details[abroad]"
                           {{ old('details.abroad') ? 'checked' : '' }}>
                    <label class="form-check-label" for="details_abroad">Mark as abroad (enables travel clearance doc rule)</label>
                  </div>
                </div>
              </div>
            </div>

            {{-- SL --}}
            <div id="sectionSL" class="d-none">
              <div class="fw-semibold mb-2">Sick Leave</div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Patient Type</label>
                  <select class="form-select" name="details[sl_patient_type]">
                    <option value="">-- Select --</option>
                    <option value="in_hospital" {{ old('details.sl_patient_type') === 'in_hospital' ? 'selected' : '' }}>In Hospital</option>
                    <option value="out_patient" {{ old('details.sl_patient_type') === 'out_patient' ? 'selected' : '' }}>Out Patient</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Specify illness (optional)</label>
                  <input type="text" class="form-control" name="details[illness]" value="{{ old('details.illness') }}"
                         placeholder="e.g., fever, cough, etc.">
                </div>

                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="details_no_consultation" name="details[no_consultation]"
                           {{ old('details.no_consultation') ? 'checked' : '' }}>
                    <label class="form-check-label" for="details_no_consultation">No medical consultation (may require affidavit)</label>
                  </div>
                </div>
              </div>
            </div>

            {{-- ML --}}
            <div id="sectionML" class="d-none">
              <div class="fw-semibold mb-2">Maternity Leave</div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Expected Date of Delivery (EDD)</label>
                  <input type="date" class="form-control" name="details[ml_edd]" value="{{ old('details.ml_edd') }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Need allocation (CS Form 6a)?</label>
                  <select class="form-select" name="details[ml_need_cs6a]">
                    <option value="">-- Select --</option>
                    <option value="yes" {{ old('details.ml_need_cs6a') === 'yes' ? 'selected' : '' }}>Yes</option>
                    <option value="no" {{ old('details.ml_need_cs6a') === 'no' ? 'selected' : '' }}>No</option>
                  </select>
                </div>
              </div>
            </div>

            {{-- PL --}}
            <div id="sectionPL" class="d-none">
              <div class="fw-semibold mb-2">Paternity Leave</div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Child’s Date of Delivery</label>
                  <input type="date" class="form-control" name="details[pl_delivery_date]" value="{{ old('details.pl_delivery_date') }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Marriage contract available?</label>
                  <select class="form-select" name="details[pl_marriage_contract]">
                    <option value="">-- Select --</option>
                    <option value="yes" {{ old('details.pl_marriage_contract') === 'yes' ? 'selected' : '' }}>Yes</option>
                    <option value="no" {{ old('details.pl_marriage_contract') === 'no' ? 'selected' : '' }}>No</option>
                  </select>
                </div>
              </div>
            </div>

            {{-- SPL --}}
            <div id="sectionSPL" class="d-none">
              <div class="fw-semibold mb-2">Special Privilege Leave</div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Within PH or Abroad</label>
                  <select class="form-select" name="details[spl_travel]">
                    <option value="">-- Select --</option>
                    <option value="within_ph" {{ old('details.spl_travel') === 'within_ph' ? 'selected' : '' }}>Within the Philippines</option>
                    <option value="abroad" {{ old('details.spl_travel') === 'abroad' ? 'selected' : '' }}>Abroad</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Destination (optional)</label>
                  <input type="text" class="form-control" name="details[spl_location]" value="{{ old('details.spl_location') }}">
                </div>
              </div>
            </div>

            {{-- SOLO --}}
            <div id="sectionSOLO" class="d-none">
              <div class="fw-semibold mb-2">Solo Parent Leave</div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Solo Parent ID No. (optional)</label>
                  <input type="text" class="form-control" name="details[solo_id_no]" value="{{ old('details.solo_id_no') }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label">ID Valid Until (optional)</label>
                  <input type="date" class="form-control" name="details[solo_id_valid_until]" value="{{ old('details.solo_id_valid_until') }}">
                </div>
              </div>
            </div>

            {{-- STUDY --}}
            <div id="sectionSTUDY" class="d-none">
              <div class="fw-semibold mb-2">Study Leave</div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Purpose</label>
                  <select class="form-select" name="details[study_purpose]">
                    <option value="">-- Select --</option>
                    <option value="masters" {{ old('details.study_purpose') === 'masters' ? 'selected' : '' }}>Completion of Master’s Degree</option>
                    <option value="bar_board_review" {{ old('details.study_purpose') === 'bar_board_review' ? 'selected' : '' }}>BAR/Board Examination Review</option>
                    <option value="other" {{ old('details.study_purpose') === 'other' ? 'selected' : '' }}>Other</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">If other, specify</label>
                  <input type="text" class="form-control" name="details[study_other]" value="{{ old('details.study_other') }}">
                </div>
              </div>
            </div>

            {{-- VAWC --}}
            <div id="sectionVAWC" class="d-none">
              <div class="fw-semibold mb-2">10-Day VAWC Leave</div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Supporting document type (at least one)</label>
                  <select class="form-select" name="details[vawc_support]">
                    <option value="">-- Select --</option>
                    <option value="bpo" {{ old('details.vawc_support') === 'bpo' ? 'selected' : '' }}>Barangay Protection Order (BPO)</option>
                    <option value="tpo_ppo" {{ old('details.vawc_support') === 'tpo_ppo' ? 'selected' : '' }}>Temporary/Permanent Protection Order (TPO/PPO)</option>
                    <option value="cert_filed" {{ old('details.vawc_support') === 'cert_filed' ? 'selected' : '' }}>Certification that application was filed</option>
                    <option value="police_med" {{ old('details.vawc_support') === 'police_med' ? 'selected' : '' }}>Police report + medical certificate</option>
                  </select>
                </div>
              </div>
            </div>

            {{-- REHAB --}}
            <div id="sectionREHAB" class="d-none">
              <div class="fw-semibold mb-2">Rehabilitation Leave</div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Accident date</label>
                  <input type="date" class="form-control" name="details[rehab_accident_date]" value="{{ old('details.rehab_accident_date') }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Attending physician</label>
                  <select class="form-select" name="details[rehab_physician]">
                    <option value="">-- Select --</option>
                    <option value="govt" {{ old('details.rehab_physician') === 'govt' ? 'selected' : '' }}>Government physician</option>
                    <option value="private" {{ old('details.rehab_physician') === 'private' ? 'selected' : '' }}>Private practitioner (may require govt concurrence)</option>
                  </select>
                </div>
              </div>
            </div>

            {{-- WOMEN SPECIAL --}}
            <div id="sectionWOMEN" class="d-none">
              <div class="fw-semibold mb-2">Special Leave Benefits for Women</div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Surgery date (optional)</label>
                  <input type="date" class="form-control" name="details[women_surgery_date]" value="{{ old('details.women_surgery_date') }}">
                </div>
              </div>
            </div>

            {{-- CALAMITY --}}
            <div id="sectionCALAMITY" class="d-none">
              <div class="fw-semibold mb-2">Special Emergency (Calamity) Leave</div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Calamity/Disaster</label>
                  <input type="text" class="form-control" name="details[calamity_name]" value="{{ old('details.calamity_name') }}"
                         placeholder="e.g., Flood, Typhoon, Earthquake">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Affected area (City/Barangay)</label>
                  <input type="text" class="form-control" name="details[calamity_area]" value="{{ old('details.calamity_area') }}">
                </div>
              </div>
            </div>

            {{-- MONETIZE --}}
            <div id="sectionMON" class="d-none">
              <div class="fw-semibold mb-2">Monetization of Leave Credits</div>
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">Valid/justifiable reason (optional)</label>
                  <input type="text" class="form-control" name="details[mon_reason]" value="{{ old('details.mon_reason') }}">
                </div>
              </div>
            </div>

            {{-- TERMINAL --}}
            <div id="sectionTL" class="d-none">
              <div class="fw-semibold mb-2">Terminal Leave</div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Separation date (optional)</label>
                  <input type="date" class="form-control" name="details[tl_separation_date]" value="{{ old('details.tl_separation_date') }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Type</label>
                  <select class="form-select" name="details[tl_type]">
                    <option value="">-- Select --</option>
                    <option value="resignation" {{ old('details.tl_type') === 'resignation' ? 'selected' : '' }}>Resignation</option>
                    <option value="retirement" {{ old('details.tl_type') === 'retirement' ? 'selected' : '' }}>Retirement</option>
                    <option value="separation" {{ old('details.tl_type') === 'separation' ? 'selected' : '' }}>Separation</option>
                  </select>
                </div>
              </div>
            </div>

            {{-- ADOPTION --}}
            <div id="sectionADOPT" class="d-none">
              <div class="fw-semibold mb-2">Adoption Leave</div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">PAPA reference no. (optional)</label>
                  <input type="text" class="form-control" name="details[adopt_papa_ref]" value="{{ old('details.adopt_papa_ref') }}">
                </div>
              </div>
            </div>

            <hr class="my-4">

            <div class="mb-0">
              <label class="form-label">Additional Notes (Optional)</label>
              <textarea class="form-control" rows="3" name="details[notes]" placeholder="Other details you want the approver to see...">{{ old('details.notes') }}</textarea>
            </div>

          </div>
        </div>

        {{-- ATTACHMENTS --}}

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">Submit Leave Application</button>
          <button type="reset" class="btn btn-outline-secondary" id="btnReset">Reset</button>
        </div>

      </form>

    </div>
  </div>
</div>
@endsection

@push('scripts')
{{-- Flatpickr CSS and JS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
(function() {
  const $leaveType = $('#leave_type_id');
  const $days = $('#working_days_requested');
  const $reqList = $('#requiredDocsList');
  const $reqStatus = $('#reqDocsStatus');
  const $clearanceAlert = $('#clearanceAlert');

  // NEW: Function to check if the 30 calendar days or Terminal Leave rule applies
  function checkClearanceRule(selectedDates) {
      const code = selectedLeaveCode();
      let is30CalendarDays = false;

      // Check if dates span 30 or more calendar days
      if (selectedDates && selectedDates.length > 0) {
          const sortedDates = [...selectedDates].sort((a, b) => a - b);
          const firstDate = sortedDates[0];
          const lastDate = sortedDates[sortedDates.length - 1];

          // Calculate calendar day difference
          const diffTime = Math.abs(lastDate - firstDate);
          const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

          if (diffDays >= 30) {
              is30CalendarDays = true;
          }
      }

      // Show alert if Terminal Leave OR >= 30 calendar days
      if (code === 'TL' || is30CalendarDays) {
          $clearanceAlert.removeClass('d-none');
      } else {
          $clearanceAlert.addClass('d-none');
      }
  }

  // Init Flatpickr exactly like the screenshot design
  const fp = flatpickr("#leave_dates", {
      mode: "multiple",
      dateFormat: "Y-m-d",
      onChange: function(selectedDates, dateStr, instance) {
          let count = 0;

          selectedDates.forEach(function(d) {
              const day = d.getDay();
              // Count valid weekdays (skip Sunday=0, Saturday=6)
              if (day !== 0 && day !== 6) count++;
          });

          if (count > 0) {
              $days.val(count);
              $('#autoDaysHint').text('Auto-calculated based on selected valid weekdays. Edit for half-days.');
          } else {
              $days.val('');
              $('#autoDaysHint').text('Please select valid weekday dates first.');
          }

          // Trigger clearance check and required docs update immediately
          checkClearanceRule(selectedDates);
          refreshRequiredDocs();
      }
  });

  const sections = {
    VL: $('#sectionVL'), SL: $('#sectionSL'), ML: $('#sectionML'), PL: $('#sectionPL'),
    SPL: $('#sectionSPL'), SOLO: $('#sectionSOLO'), STUDY: $('#sectionSTUDY'), VAWC: $('#sectionVAWC'),
    REHAB: $('#sectionREHAB'), WOMEN: $('#sectionWOMEN'), CALAMITY: $('#sectionCALAMITY'),
    MON: $('#sectionMON'), TL: $('#sectionTL'), ADOPT: $('#sectionADOPT'),
  };

  function selectedLeaveCode() {
    return $leaveType.find('option:selected').data('code') || '';
  }

  function hideAllSections() {
    Object.values(sections).forEach($el => $el.addClass('d-none'));
  }

  function showHideSections() {
    hideAllSections();
    const code = selectedLeaveCode();
    if (sections[code]) sections[code].removeClass('d-none');
  }

  function buildDetailsPayload() {
    return {
      abroad: $('#details_abroad').is(':checked') ? 1 : 0,
      no_consultation: $('#details_no_consultation').is(':checked') ? 1 : 0,
      vawc_support: $('select[name="details[vawc_support]"]').val() || null,
      rehab_physician: $('select[name="details[rehab_physician]"]').val() || null
    };
  }

  async function refreshRequiredDocs() {
    const leaveTypeId = $leaveType.val();
    if (!leaveTypeId) {
      $reqStatus.removeClass().addClass('badge text-bg-secondary').text('Waiting…');
      $reqList.html('<li class="text-muted">Select leave type to see required documents.</li>');
      return;
    }

    $reqStatus.removeClass().addClass('badge text-bg-warning').text('Checking…');
    $reqList.html('<li class="text-muted">Loading…</li>');

    try {
      const res = await $.ajax({
        url: "{{ route('employee.leaves.requiredDocs') }}",
        method: "POST",
        data: {
          _token: "{{ csrf_token() }}",
          leave_type_id: leaveTypeId,
          dates: $('#leave_dates').val(), // Flatpickr returns string: "2026-03-17, 2026-03-18"
          working_days_requested: $days.val(),
          details: buildDetailsPayload(),
        }
      });

      const docs = res.required_docs || [];
      if (docs.length === 0) {
        $reqStatus.removeClass().addClass('badge text-bg-success').text('None');
        $reqList.html('<li>No required documents detected for current inputs.</li>');
        return;
      }

      $reqStatus.removeClass().addClass('badge text-bg-danger').text(docs.length + ' Required');
      $reqList.empty();
      docs.forEach(d => $reqList.append(`<li>${escapeHtml(d.name)}</li>`));

    } catch (e) {
      $reqStatus.removeClass().addClass('badge text-bg-danger').text('Error');
      $reqList.html('<li class="text-danger">Could not check required documents. Please try again.</li>');
    }
  }

  function escapeHtml(str) {
    return String(str).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
  }

  // Allow “append files” instead of replace
  const fileInput = document.getElementById('attachments');
  const fileList = document.getElementById('fileList');
  const dt = new DataTransfer();

  function renderFiles() {
    if (dt.files.length === 0) {
      fileList.innerHTML = '<li class="text-muted">No files selected.</li>';
      return;
    }
    fileList.innerHTML = '';
    Array.from(dt.files).forEach((f, idx) => {
      const li = document.createElement('li');
      li.className = 'd-flex align-items-center justify-content-between gap-2';
      li.innerHTML = `<span>${escapeHtml(f.name)} <span class="text-muted">(${Math.round(f.size/1024)} KB)</span></span>
                      <button type="button" class="btn btn-sm btn-outline-danger">Remove</button>`;
      li.querySelector('button').addEventListener('click', () => {
        const ndt = new DataTransfer();
        Array.from(dt.files).forEach((file, i) => { if (i !== idx) ndt.items.add(file); });
        dt.items.clear();
        Array.from(ndt.files).forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
        renderFiles();
      });
      fileList.appendChild(li);
    });
  }

  fileInput.addEventListener('change', function() {
    Array.from(fileInput.files).forEach(f => dt.items.add(f));
    fileInput.files = dt.files;
    renderFiles();
  });

  $('#btnReset').on('click', function() {
    setTimeout(function() {
      hideAllSections();
      fp.clear(); // Clear the calendar
      checkClearanceRule([]); // Reset alert
      refreshRequiredDocs();
      dt.items.clear();
      fileInput.value = '';
      renderFiles();
    }, 0);
  });

  // Events
  $leaveType.on('change', function() {
    showHideSections();
    checkClearanceRule(fp.selectedDates); // Check clearance on type change
    refreshRequiredDocs();
  });

  $days.on('input', refreshRequiredDocs);

  // initial
  showHideSections();
  refreshRequiredDocs();
  renderFiles();
})();
</script>
@endpush
