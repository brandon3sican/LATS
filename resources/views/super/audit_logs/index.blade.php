@extends('layouts.app')

@section('content')
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0">Audit Logs</h3>
                <div class="text-muted">Track all system actions and approval workflow performance.</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('super.audit-logs.export', request()->query()) }}" class="btn btn-outline-primary">
                    <i class="bi bi-download me-1"></i> Export PDF
                </a>
            </div>
        </div>

        {{-- Bottleneck Analysis Section --}}
        @if($bottleneckAnalysis['slowest_step'])
        <div class="card shadow-sm mb-4 border-start border-4 border-warning">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                    Bottleneck Analysis
                </h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-warning mb-0">
                            <strong>Slowest Approval Step:</strong> Step {{ $bottleneckAnalysis['slowest_step'] }}
                            <br>
                            <small>Average Response Time: {{ $bottleneckAnalysis['slowest_step_data']['formatted'] }}</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="small text-muted mb-2">Step Performance Overview</h6>
                        @foreach($bottleneckAnalysis['step_analysis'] as $step => $data)
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small">Step {{ $step }}:</span>
                                <span class="small {{ $step == $bottleneckAnalysis['slowest_step'] ? 'text-danger fw-bold' : '' }}">
                                    {{ $data['formatted'] }} ({{ $data['count'] }} actions)
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Filters --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('super.audit-logs.index') }}" class="row g-2">
                    <div class="col-md-2">
                        <select name="user_id" class="form-select">
                            <option value="">All Users</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="action_type" class="form-select">
                            <option value="">All Action Types</option>
                            <option value="approval" {{ request('action_type') == 'approval' ? 'selected' : '' }}>Approval</option>
                            <option value="cancellation" {{ request('action_type') == 'cancellation' ? 'selected' : '' }}>Cancellation</option>
                            <option value="view" {{ request('action_type') == 'view' ? 'selected' : '' }}>View</option>
                            <option value="export" {{ request('action_type') == 'export' ? 'selected' : '' }}>Export</option>
                            <option value="dashboard" {{ request('action_type') == 'dashboard' ? 'selected' : '' }}>Dashboard</option>
                            <option value="inbox" {{ request('action_type') == 'inbox' ? 'selected' : '' }}>Inbox</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="action" class="form-select">
                            <option value="">All Actions</option>
                            <option value="approved" {{ request('action') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="disapproved" {{ request('action') == 'disapproved' ? 'selected' : '' }}>Disapproved</option>
                            <option value="returned" {{ request('action') == 'returned' ? 'selected' : '' }}>Returned</option>
                            <option value="cancelled" {{ request('action') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="viewed" {{ request('action') == 'viewed' ? 'selected' : '' }}>Viewed</option>
                            <option value="exported" {{ request('action') == 'exported' ? 'selected' : '' }}>Exported</option>
                            <option value="accessed" {{ request('action') == 'accessed' ? 'selected' : '' }}>Accessed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="office_id" class="form-select">
                            <option value="">All Offices</option>
                            @foreach ($offices as $office)
                                <option value="{{ $office->id }}" {{ request('office_id') == $office->id ? 'selected' : '' }}>
                                    {{ $office->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="division_id" class="form-select">
                            <option value="">All Divisions</option>
                            @foreach ($divisions as $division)
                                <option value="{{ $division->id }}" {{ request('division_id') == $division->id ? 'selected' : '' }}>
                                    {{ $division->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <select name="step_order" class="form-select">
                            <option value="">All Steps</option>
                            <option value="1" {{ request('step_order') == '1' ? 'selected' : '' }}>Step 1</option>
                            <option value="2" {{ request('step_order') == '2' ? 'selected' : '' }}>Step 2</option>
                            <option value="3" {{ request('step_order') == '3' ? 'selected' : '' }}>Step 3</option>
                            <option value="4" {{ request('step_order') == '4' ? 'selected' : '' }}>Step 4</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="date_filter_type" class="form-select" id="dateFilterType">
                            <option value="">All Time</option>
                            <option value="specific" {{ request('date_filter_type') == 'specific' ? 'selected' : '' }}>Specific Dates</option>
                            <option value="this_week" {{ request('date_filter_type') == 'this_week' ? 'selected' : '' }}>This Week</option>
                            <option value="this_month" {{ request('date_filter_type') == 'this_month' ? 'selected' : '' }}>This Month</option>
                            <option value="last_week" {{ request('date_filter_type') == 'last_week' ? 'selected' : '' }}>Last Week</option>
                            <option value="last_month" {{ request('date_filter_type') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                            <option value="custom_month" {{ request('date_filter_type') == 'custom_month' ? 'selected' : '' }}>Custom Month</option>
                        </select>
                    </div>
                    <div class="col-md-2" id="specificDates" style="display: {{ request('date_filter_type') == 'specific' ? 'block' : 'none' }};">
                        <input type="date" name="date_from" class="form-control" placeholder="From"
                               value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2" id="specificDatesTo" style="display: {{ request('date_filter_type') == 'specific' ? 'block' : 'none' }};">
                        <input type="date" name="date_to" class="form-control" placeholder="To"
                               value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2" id="customMonth" style="display: {{ request('date_filter_type') == 'custom_month' ? 'block' : 'none' }};">
                        <input type="month" name="custom_month" class="form-control"
                               value="{{ request('custom_month') }}">
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-secondary flex-grow-1">
                                <i class="bi bi-search me-1"></i> Filter
                            </button>
                            @if(request()->hasAny(['user_id', 'action_type', 'action', 'office_id', 'division_id', 'step_order', 'date_filter_type', 'date_from', 'date_to', 'custom_month']))
                                <a href="{{ route('super.audit-logs.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Audit Log Table --}}
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date/Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Step</th>
                            <th>Office/Division</th>
                            <th>IP Address</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($auditLogs as $log)
                            <tr>
                                <td>
                                    <div class="small">{{ $log->created_at->format('M d, Y') }}</div>
                                    <div class="small text-muted">{{ $log->created_at->format('H:i:s') }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $log->user ? $log->user->name : 'N/A' }}</div>
                                    <div class="small text-muted">{{ $log->user ? $log->user->email : '' }}</div>
                                </td>
                                <td>
                                    @php
                                        $badgeColor = match($log->action_type) {
                                            'approval' => 'success',
                                            'cancellation' => 'danger',
                                            'view' => 'info',
                                            'export' => 'primary',
                                            'dashboard' => 'secondary',
                                            'inbox' => 'warning',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badgeColor }}">
                                        {{ ucfirst($log->action_type) }}
                                    </span>
                                    <div class="small mt-1">{{ ucfirst($log->action) }}</div>
                                </td>
                                <td>
                                    <div class="small">{{ $log->description }}</div>
                                    @if($log->leave_application_id)
                                        <div class="small text-muted">
                                            <a href="{{ route('super.leaves.show', $log->leave_application_id) }}" class="text-decoration-none">
                                                Leave #{{ $log->leave_application_id }}
                                            </a>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($log->step_order)
                                        <span class="badge bg-info">Step {{ $log->step_order }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small">{{ $log->office ? $log->office->name : 'N/A' }}</div>
                                    <div class="small text-muted">{{ $log->division ? $log->division->name : '' }}</div>
                                </td>
                                <td>
                                    <div class="small font-monospace">{{ $log->decrypted_ip_address ?? 'N/A' }}</div>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('super.audit-logs.show', $log->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No audit logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">
                {{ $auditLogs->links() }}
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateFilterType = document.getElementById('dateFilterType');
            const specificDates = document.getElementById('specificDates');
            const specificDatesTo = document.getElementById('specificDatesTo');
            const customMonth = document.getElementById('customMonth');

            function toggleDateFields() {
                const filterType = dateFilterType.value;

                if (filterType === 'specific') {
                    specificDates.style.display = 'block';
                    specificDatesTo.style.display = 'block';
                    customMonth.style.display = 'none';
                } else if (filterType === 'custom_month') {
                    specificDates.style.display = 'none';
                    specificDatesTo.style.display = 'none';
                    customMonth.style.display = 'block';
                } else {
                    specificDates.style.display = 'none';
                    specificDatesTo.style.display = 'none';
                    customMonth.style.display = 'none';
                }
            }

            dateFilterType.addEventListener('change', toggleDateFields);
            toggleDateFields(); // Initialize on page load
        });
    </script>
@endsection