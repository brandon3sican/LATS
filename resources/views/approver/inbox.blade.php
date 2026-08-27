@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Approver Inbox</h3>
        <span class="badge bg-primary">{{ $leaves->total() }} Pending</span>
    </div>

    {{-- FILTERS (Req #2) --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('approver.inbox') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Division</label>
                    <select name="division_id" class="form-select form-select-sm">
                        <option value="">All Divisions</option>
                        @foreach($divisions as $div)
                            <option value="{{ $div->id }}" {{ request('division_id') == $div->id ? 'selected' : '' }}>
                                {{ $div->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">From Date</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">To Date</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    @if ($leaves->count() === 0)
        <div class="alert alert-info">No pending leave applications found.</div>
    @else
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date Filed</th>
                            <th>Employee</th>
                            <th>Division</th>
                            <th>Leave Type</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leaves as $leave)
                            <tr>
                                <td>{{ $leave->date_filed->format('M d, Y') }}</td>
                                <td>
                                    <div class="fw-bold">{{ $leave->employee->user->name }}</div>
                                    <div class="small text-muted">{{ $leave->employee->position_title }}</div>
                                </td>
                                <td>{{ optional($leave->employee->division)->name ?? '-' }}</td>
                                <td>{{ $leave->leaveType->name }}</td>
                                <td>
                                    @if($leave->cancellation_status === 'pending')
                                        <span class="badge bg-danger shadow-sm"><i class="bi bi-exclamation-octagon-fill me-1"></i> CANCELLATION REQUEST</span>
                                    @else
                                        <span class="badge bg-warning text-dark">PENDING</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-primary" href="{{ route('approver.leaves.show', $leave->id) }}">
                                        Review
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $leaves->links() }}</div>
    @endif
</div>
@endsection
