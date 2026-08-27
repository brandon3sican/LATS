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

        @if (session('updated'))
            <div class="alert alert-success">{{ session('updated') }}</div>
        @elseif (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @elseif (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

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
                                    <button class="btn btn-sm btn-outline-primary edit-user-btn"
                                        data-id="{{ $emp->id }}">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
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

    {{-- Edit User Modal --}}
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST" id="editUserForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <h6 class="mb-3 text-primary">Account Details</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" id="editFirstName" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" id="editMiddleName" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" id="editLastName" class="form-control" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                             <div class="col-md-6">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="editEmail" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Account Status</label>
                                <select name="status" id="editStatus" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive / Retired</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password <small class="text-muted">(Leave blank to keep current)</small></label>
                                <input type="password" name="password" id="editPassword" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" id="editPasswordConfirmation" class="form-control">
                            </div>
                        </div>

                        <h6 class="mb-3 text-primary">Employment Details</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Office Assignment <span class="text-danger">*</span></label>
                                <select name="office_id" id="editOfficeId" class="form-select" required onchange="filterEditDivisions(true)">
                                    @foreach ($offices as $office)
                                        <option value="{{ $office->id }}">{{ $office->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Division</label>
                                <select name="division_id" id="editDivisionId" class="form-select">
                                    <option value="">-- Select Division --</option>
                                    @foreach($divisions as $div)
                                        <option value="{{ $div->id }}" data-office="{{ $div->office_id }}" style="display:none">
                                            {{ $div->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Position Title <span class="text-danger">*</span></label>
                                <input type="text" name="position_title" id="editPositionTitle" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Salary Grade</label>
                                <input type="number" name="salary_grade" id="editSalaryGrade" class="form-control" min="1" max="33">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Sex</label>
                                <select name="sex" id="editSex" class="form-select">
                                    <option value="">--</option>
                                    <option value="M">Male</option>
                                    <option value="F">Female</option>
                                </select>
                            </div>
                        </div>

                        <h6 class="mb-3 text-primary">System Roles & Approver Access</h6>
                        <div class="mb-4">
                            <div class="row">
                                @foreach($roles as $role)
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="edit_role_{{ $role->id }}">
                                            <label class="form-check-label" for="edit_role_{{ $role->id }}">
                                                {{ $role->name }}
                                                @if($role->key === 'super_admin')
                                                    <span class="badge bg-danger">SUPER ADMIN</span>
                                                @elseif($role->key === 'office_admin' || $role->key === 'admin')
                                                    <span class="badge bg-primary">ADMIN</span>
                                                @elseif(str_contains($role->key, 'approver'))
                                                    <span class="badge bg-warning text-dark">APPROVER</span>
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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

        function filterEditDivisions(resetSelection = false) {
            const officeId = document.getElementById('editOfficeId').value;
            const divisionSelect = document.getElementById('editDivisionId');
            const options = divisionSelect.querySelectorAll('option');

            if(resetSelection) {
                divisionSelect.value = "";
            }

            options.forEach(opt => {
                if (opt.value === "") return;
                if (opt.getAttribute('data-office') == officeId) {
                    opt.style.display = 'block';
                } else {
                    opt.style.display = 'none';
                }
            });
        }

        // Store employee data for editing
        const employeesData = @json($employees->items());
        
        document.addEventListener('DOMContentLoaded', function() {
            // Handle edit button clicks
            document.querySelectorAll('.edit-user-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const employeeId = parseInt(this.getAttribute('data-id'));
                    const employee = employeesData.find(e => e.id === employeeId);
                    
                    if (employee && employee.user) {
                        const form = document.getElementById('editUserForm');
                        // Set the form action dynamically
                        form.action = `/super/users/${employeeId}`;
                        
                        // Account details
                        document.getElementById('editFirstName').value = employee.user.first_name;
                        document.getElementById('editMiddleName').value = employee.user.middle_name || '';
                        document.getElementById('editLastName').value = employee.user.last_name;
                        document.getElementById('editEmail').value = employee.user.email;
                        document.getElementById('editStatus').value = employee.status;
                        
                        // Employment details
                        document.getElementById('editOfficeId').value = employee.office_id;
                        document.getElementById('editDivisionId').value = employee.division_id || '';
                        document.getElementById('editPositionTitle').value = employee.position_title;
                        document.getElementById('editSalaryGrade').value = employee.salary_grade || '';
                        document.getElementById('editSex').value = employee.sex || '';
                        
                        // Clear password fields
                        document.getElementById('editPassword').value = '';
                        document.getElementById('editPasswordConfirmation').value = '';
                        
                        // Uncheck all roles first
                        document.querySelectorAll('[id^="edit_role_"]').forEach(checkbox => {
                            checkbox.checked = false;
                        });
                        
                        // Check user's roles
                        if (employee.user.roles) {
                            employee.user.roles.forEach(role => {
                                const checkbox = document.getElementById('edit_role_' + role.id);
                                if (checkbox) {
                                    checkbox.checked = true;
                                }
                            });
                        }
                        
                        // Filter divisions based on selected office
                        filterEditDivisions(false);
                        
                        const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                        modal.show();
                    }
                });
            });
        });
    </script>
@endsection
