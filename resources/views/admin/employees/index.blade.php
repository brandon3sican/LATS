@extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0">Employee Management</h3>
            <div class="text-muted">Manage employees for <strong>{{ $adminOffice->name }}</strong>.</div>
        </div>
        <a href="{{ route('admin.employees.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Add Employee
        </a>
    </div>

    {{-- Filters --}}
    {{-- Filters --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.employees.index') }}" class="row g-2">
                {{-- Search bar column adjusted --}}
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search') }}">
                </div>

                {{-- Division Filter --}}
                <div class="col-md-4">
                    <select name="division_id" class="form-select">
                        <option value="">All Divisions</option>
                        @foreach($divisions as $division)
                            <option value="{{ $division->id }}" {{ request('division_id') == $division->id ? 'selected' : '' }}>
                                {{ $division->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-secondary w-100">
                        <i class="bi bi-search"></i> Search / Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Employee Table --}}
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name / Email</th>
                        <th>Position / Division</th>
                        <th>Roles (Approver Level)</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $emp->user->name }}</div>
                                <div class="text-muted small">{{ $emp->user->email }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $emp->position_title }}</div>
                                <div class="text-muted small">{{ $emp->division->name ?? 'No Division' }}</div>
                            </td>
                            <td>
                                @foreach($emp->user->roles as $role)
                                    @if($role->key !== 'employee')
                                        <span class="badge bg-info text-dark border">{{ $role->name }}</span>
                                    @endif
                                @endforeach
                            </td>
                            <td>
                                @if($emp->status === 'active')
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($emp->status) }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.employees.edit', $emp->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No employees found in your office.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $employees->links() }}
        </div>
    </div>
</div>
@endsection
