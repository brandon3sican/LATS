@extends('layouts.app')

@section('content')
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0">Audit Log Details</h3>
                <div class="text-muted">View detailed information about this system action.</div>
            </div>
            <a href="{{ route('super.audit-logs.index', request()->query()) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Logs
            </a>
        </div>

        {{-- Basic Information Card --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Log ID</label>
                            <div class="fw-bold">#{{ $auditLog->id }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Date & Time</label>
                            <div>{{ $auditLog->created_at->format('F d, Y - g:i A') }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Action Type</label>
                            <div>
                                <span class="badge bg-{{ match($auditLog->action_type) {
                                    'approval' => 'success',
                                    'cancellation' => 'danger',
                                    'view' => 'info',
                                    'export' => 'primary',
                                    'dashboard' => 'secondary',
                                    'inbox' => 'warning',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst($auditLog->action_type) }}
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Action</label>
                            <div class="fw-bold">{{ ucfirst($auditLog->action) }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Description</label>
                            <div>{{ $auditLog->description }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted mb-1">IP Address</label>
                            <div class="font-monospace">{{ $auditLog->decrypted_ip_address ?? 'N/A' }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted mb-1">User Agent</label>
                            <div class="small text-break">{{ $auditLog->user_agent ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- User Information Card --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-person me-2"></i>User Information</h5>
            </div>
            <div class="card-body">
                @if($auditLog->user)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="small text-muted mb-1">User ID</label>
                                <div>#{{ $auditLog->user->id }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted mb-1">Name</label>
                                <div class="fw-bold">{{ $auditLog->user->name }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted mb-1">Email</label>
                                <div>{{ $auditLog->user->email }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="small text-muted mb-1">Roles</label>
                                <div>
                                    @foreach($auditLog->user->roles as $role)
                                        <span class="badge bg-secondary me-1">{{ $role->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @if($auditLog->user->employee)
                                <div class="mb-3">
                                    <label class="small text-muted mb-1">Position</label>
                                    <div>{{ $auditLog->user->employee->position_title }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-muted">User information not available</div>
                @endif
            </div>
        </div>

        {{-- Organizational Context Card --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-building me-2"></i>Organizational Context</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Office</label>
                            <div class="fw-bold">{{ $auditLog->office ? $auditLog->office->name : 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Division</label>
                            <div class="fw-bold">{{ $auditLog->division ? $auditLog->division->name : 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Workflow Information Card --}}
        @if($auditLog->step_order || $auditLog->leave_application_id)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Workflow Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @if($auditLog->step_order)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="small text-muted mb-1">Step Order</label>
                                <div>
                                    <span class="badge bg-info">Step {{ $auditLog->step_order }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if($auditLog->leave_application_id)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="small text-muted mb-1">Leave Application</label>
                                <div>
                                    @if($auditLog->leaveApplication)
                                        <a href="{{ route('super.leaves.show', $auditLog->leave_application_id) }}" class="text-decoration-none">
                                            <i class="bi bi-file-earmark-text me-1"></i>
                                            Leave #{{ $auditLog->leave_application_id }}
                                        </a>
                                        @if($auditLog->leaveApplication->employee)
                                            <div class="small text-muted">
                                                {{ $auditLog->leaveApplication->employee->user->name }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">Leave #{{ $auditLog->leave_application_id }} (deleted)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Additional Details Card --}}
        @if($auditLog->details)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-code-slash me-2"></i>Additional Details</h5>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">{{ json_encode($auditLog->details, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
        @endif

        {{-- Timestamp Information Card --}}
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-clock me-2"></i>Timestamp Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Created At</label>
                            <div>{{ $auditLog->created_at->format('F d, Y - g:i:s A') }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Updated At</label>
                            <div>{{ $auditLog->updated_at->format('F d, Y - g:i:s A') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection