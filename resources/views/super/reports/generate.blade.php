@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">Generate Reports</h3>
            <a href="{{ route('super.dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Report Configuration</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="report_type" class="form-label fw-bold">Report Type</label>
                                <select name="report_type" id="report_type" class="form-select" required>
                                    <option value="efficiency" {{ $reportType == 'efficiency' ? 'selected' : '' }}>
                                        Efficiency Metrics Report
                                    </option>
                                    <option value="audit" {{ $reportType == 'audit' ? 'selected' : '' }}>
                                        Audit Trail Report
                                    </option>
                                    <option value="combined" {{ $reportType == 'combined' ? 'selected' : '' }}>
                                        Combined Analysis Report
                                    </option>
                                </select>
                                <div class="form-text">
                                    <small>
                                        <strong>Efficiency Metrics:</strong> Focus on timing analysis, approval rates, and workflow efficiency<br>
                                        <strong>Audit Trail:</strong> Focus on audit logs, approver actions, and workflow patterns<br>
                                        <strong>Combined Analysis:</strong> Comprehensive analysis including both efficiency metrics and audit trail data
                                    </small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="division_id" class="form-label fw-bold">Division</label>
                                <select name="division_id" id="division_id" class="form-select">
                                    <option value="">All Divisions</option>
                                    @foreach ($divisions as $division)
                                        <option value="{{ $division->id }}" {{ $selectedDivision == $division->id ? 'selected' : '' }}>
                                            {{ $division->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">
                                    <small>Select a specific division or leave blank for all divisions</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label for="from_date" class="form-label fw-bold">From Date</label>
                                        <input type="date" name="from_date" id="from_date" class="form-control" 
                                               value="{{ $fromDate }}" required>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label for="to_date" class="form-label fw-bold">To Date</label>
                                        <input type="date" name="to_date" id="to_date" class="form-control" 
                                               value="{{ $toDate }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <h6 class="alert-heading"><i class="bi bi-info-circle"></i> Report Information</h6>
                                <p class="mb-0 small">
                                    Reports are generated in PDF format and include comprehensive analysis based on the selected parameters.
                                    All report generation activities are logged in the audit trail for security and accountability.
                                </p>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" formaction="{{ route('super.reports.efficiency') }}" 
                                        class="btn btn-success flex-grow-1" onclick="return validateForm('efficiency')">
                                    <i class="bi bi-file-earmark-bar-graph"></i> Generate Efficiency Report
                                </button>
                                <button type="submit" formaction="{{ route('super.reports.audit') }}" 
                                        class="btn btn-primary flex-grow-1" onclick="return validateForm('audit')">
                                    <i class="bi bi-file-earmark-text"></i> Generate Audit Report
                                </button>
                                <button type="submit" formaction="{{ route('super.reports.combined') }}" 
                                        class="btn btn-warning flex-grow-1" onclick="return validateForm('combined')">
                                    <i class="bi bi-file-earmark-zip"></i> Generate Combined Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0">Report Types</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="fw-bold"><i class="bi bi-speedometer2 text-success"></i> Efficiency Metrics</h6>
                            <p class="small text-muted mb-0">
                                Analyzes approval timing, response rates, and workflow efficiency. Includes bottleneck identification and per-step analysis.
                            </p>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <h6 class="fw-bold"><i class="bi bi-list-check text-primary"></i> Audit Trail</h6>
                            <p class="small text-muted mb-0">
                                Shows comprehensive audit logs, approver actions, workflow patterns, and performance metrics by user and step.
                            </p>
                        </div>
                        <hr>
                        <div class="mb-0">
                            <h6 class="fw-bold"><i class="bi bi-graph-up text-warning"></i> Combined Analysis</h6>
                            <p class="small text-muted mb-0">
                                Comprehensive report combining efficiency metrics and audit trail data with actionable recommendations.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">Quick Tips</h5>
                    </div>
                    <div class="card-body">
                        <ul class="small mb-0">
                            <li>Select "All Divisions" for organization-wide analysis</li>
                            <li>Use specific divisions for targeted insights</li>
                            <li>Choose appropriate date ranges for meaningful analysis</li>
                            <li>Combined reports provide the most comprehensive view</li>
                            <li>All reports are logged for audit purposes</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function validateForm(reportType) {
            const selectedType = document.getElementById('report_type').value;
            const from = document.getElementById('from_date').value;
            const to = document.getElementById('to_date').value;

            if (!from || !to) {
                alert('Please select both from and to dates.');
                return false;
            }

            if (new Date(from) > new Date(to)) {
                alert('From date must be before to date.');
                return false;
            }

            // Auto-select the matching report type
            document.getElementById('report_type').value = reportType;

            return true;
        }

        // Update report type when buttons are clicked
        document.querySelectorAll('button[formaction]').forEach(button => {
            button.addEventListener('click', function() {
                const action = this.getAttribute('formaction');
                if (action.includes('efficiency')) {
                    document.getElementById('report_type').value = 'efficiency';
                } else if (action.includes('audit')) {
                    document.getElementById('report_type').value = 'audit';
                } else if (action.includes('combined')) {
                    document.getElementById('report_type').value = 'combined';
                }
            });
        });
    </script>
@endpush