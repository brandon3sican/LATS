@extends('layouts.app')

@section('content')
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0">System Users</h3>
                <div class="text-muted">Manage all users, roles, and assignments across all offices.</div>
            </div>
            <button data-bs-toggle="modal" data-bs-target="#createUserModal" class="btn btn-primary">
                <i class="bi bi-person-plus me-1"></i> Add User
            </button>
        </div>

        {{-- Filters --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('super.users.index') }}" class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search by name or email..."
                            value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="office_id" class="form-select">
                            <option value="">All Offices</option>
                            @foreach ($offices as $office)
                                <option value="{{ $office->id }}"
                                    {{ request('office_id') == $office->id ? 'selected' : '' }}>
                                    {{ $office->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="role_id" class="form-select">
                            <option value="">All Roles</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-secondary w-100">
                            <i class="bi bi-search me-1"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- User Table --}}
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name / Email</th>
                            <th>Position / Office / Roles</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $emp->user->first_name }} {{ $emp->user->last_name }}</div>
                                    <div class="text-muted small">{{ $emp->user->email }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $emp->position_title }}</div>
                                    <div class="text-muted small">
                                        <span class="text-dark fw-bold">{{ $emp->office->name ?? 'No Office' }}</span>
                                        &bull;
                                        {{ $emp->division->name ?? 'No Division' }}
                                    </div>
                                    <div class="mt-1">
                                        @foreach ($emp->user->roles as $role)
                                            @if ($role->key !== 'employee')
                                                <span
                                                    class="badge {{ str_contains($role->key, 'admin') ? 'bg-primary' : 'bg-info text-dark border' }}">
                                                    {{ strtoupper(str_replace('_', ' ', $role->key)) }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    @if ($emp->status === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($emp->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('super.users.edit', $emp->id) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No users found.</td>
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

    {{-- Create User Modal --}}
    @include('super.users.create')

    <script>
        function filterDivisions() {
            const officeId = document.getElementById('office_id').value;
            const divisionSelect = document.getElementById('division_id');
            const options = divisionSelect.querySelectorAll('option');

            divisionSelect.value = "";

            options.forEach(opt => {
                if (opt.value === "") return;
                if (opt.getAttribute('data-office') == officeId) {
                    opt.style.display = 'block';
                } else {
                    opt.style.display = 'none';
                }
            });
        }
    </script>
@endsection
