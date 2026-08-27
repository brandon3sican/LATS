@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="mb-0 fw-bold">My Action History</h3>
            <div class="text-muted">
                Showing records from <span class="fw-bold">{{ $from->format('M d, Y') }}</span>
                to <span class="fw-bold">{{ $to->format('M d, Y') }}</span>
            </div>
        </div>
        <div>
            <a href="{{ route('approver.reports.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Reports
            </a>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('approver.reports.myActions') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Month</label>
                    <select name="month" class="form-select">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $from->month == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Year</label>
                    <select name="year" class="form-select">
                        @foreach(range(now()->year, 2024) as $y)
                            <option value="{{ $y }}" {{ $from->year == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                </div>

                {{-- Export Buttons (Optional, enable if routes exist) --}}
                <div class="col-md-5 text-md-end">
                    <div class="btn-group">
                        <a href="{{ route('approver.reports.myActions.excel', request()->all()) }}" class="btn btn-success text-white">
                            <i class="bi bi-file-earmark-excel me-1"></i> Excel
                        </a>
                        <a href="{{ route('approver.reports.myActions.pdf', request()->all()) }}" class="btn btn-danger text-white" target="_blank">
                            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date Acted</th>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Action Taken</th>
                        <th class="text-end">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $row->acted_at->format('M d, Y') }}</div>
                                <div class="text-muted small">{{ $row->acted_at->format('h:i A') }}</div>
                            </td>
                            <td>
                                @if($row->leave && $row->leave->employee && $row->leave->employee->user)
                                    <div class="fw-bold">{{ $row->leave->employee->user->name }}</div>
                                    <div class="text-muted small">{{ $row->leave->employee->division->name ?? 'No Division' }}</div>
                                @else
                                    <span class="text-muted fst-italic">User Deleted</span>
                                @endif
                            </td>
                            <td>
                                @if($row->leave && $row->leave->leaveType)
                                    <span class="badge bg-light text-dark border">
                                        {{ $row->leave->leaveType->code }}
                                    </span>
                                    <div class="small text-muted mt-1">
                                        {{ $row->leave->working_days_requested }} days
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @php
                                    $act = strtolower($row->action);
                                    $actionClass = match(true) {
                                        $act === 'approved' => 'success',
                                        $act === 'disapproved' => 'danger',
                                        $act === 'returned' => 'info',
                                        str_contains($act, 'approved cancellation') => 'secondary', // Grey badge
                                        str_contains($act, 'rejected cancellation') => 'warning text-dark', // Yellow badge
                                        default => 'secondary'
                                    };
                                @endphp
                                <span class="badge bg-{{ $actionClass }} text-uppercase">
                                    {{ $row->action }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($row->leave)
                                    <a href="{{ route('approver.leaves.show', $row->leave->id) }}" class="btn btn-sm btn-outline-secondary">
                                        View
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-clipboard-x fs-1 d-block mb-2 opacity-50"></i>
                                No records found for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
