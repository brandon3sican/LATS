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

        <div id="resetPasswordAlert" class="alert alert-success d-none">
            <i class="bi bi-check-circle me-2"></i>
            <span id="resetPasswordMessage"></span>
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
                                            @if($role->key === 'super_admin')
                                                <span class="badge bg-danger">SUPER ADMIN</span>
                                            @elseif($role->key === 'office_admin' || $role->key === 'admin')
                                                <span class="badge bg-primary">ADMIN</span>
                                            @elseif(str_contains($role->key, 'approver'))
                                                <span class="badge bg-warning text-dark">APPROVER</span>
                                            @elseif($role->key !== 'employee')
                                                <span class="badge bg-info text-dark">{{ strtoupper(str_replace('_', ' ', $role->key)) }}</span>
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
                                    <button class="btn btn-sm btn-outline-info view-user-btn"
                                        data-id="{{ $emp->id }}">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning reset-password-btn"
                                        data-id="{{ $emp->id }}"
                                        data-name="{{ $emp->user->first_name }} {{ $emp->user->last_name }}">
                                        <i class="bi bi-key"></i> Reset
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary edit-user-btn"
                                        data-id="{{ $emp->id }}">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-user-btn"
                                        data-id="{{ $emp->id }}"
                                        data-name="{{ $emp->user->first_name }} {{ $emp->user->last_name }}">
                                        <i class="bi bi-trash"></i> Delete
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
                                                @else
                                                    <span class="badge bg-info text-dark">EMPLOYEE</span>
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

    {{-- Password Reset Modal --}}
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset User Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST" id="resetPasswordForm">
                    @csrf
                    <div class="modal-body">
                        <p>Reset password for <strong id="resetUserName"></strong>?</p>
                        <p class="text-info small"><i class="bi bi-info-circle me-1"></i>The password will be set to "password".</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm User Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
                    <p class="text-danger small">This action cannot be undone. The user account and all associated data will be permanently deleted.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteUserForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- View User Modal --}}
    <div class="modal fade" id="viewUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="viewUserContent">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a id="viewUserFullDetails" href="#" class="btn btn-primary">
                        <i class="bi bi-eye me-1"></i> View Full Details
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- User details template for modal --}}
    <template id="userDetailsTemplate">
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bi bi-person-circle me-2"></i>Account Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td class="fw-bold" style="width: 40%">First Name:</td>
                                <td class="user-first-name"></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Middle Name:</td>
                                <td class="user-middle-name"></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Last Name:</td>
                                <td class="user-last-name"></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Full Name:</td>
                                <td class="user-full-name"></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Email:</td>
                                <td class="user-email"></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Account Status:</td>
                                <td class="user-status"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-briefcase me-2"></i>Employment Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td class="fw-bold" style="width: 40%">Office:</td>
                                <td class="user-office"></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Division:</td>
                                <td class="user-division"></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Position:</td>
                                <td class="user-position"></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Salary Grade:</td>
                                <td class="user-salary-grade"></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Sex:</td>
                                <td class="user-sex"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="bi bi-shield-check me-2"></i>System Roles</h6>
            </div>
            <div class="card-body">
                <div class="row user-roles-container">
                </div>
            </div>
        </div>
    </template>

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
            // Handle view button clicks
            document.querySelectorAll('.view-user-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const employeeId = parseInt(this.getAttribute('data-id'));
                    openViewUserModal(employeeId);
                });
            });

            // Handle reset password button clicks
            document.querySelectorAll('.reset-password-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const employeeId = parseInt(this.getAttribute('data-id'));
                    const userName = this.getAttribute('data-name');

                    document.getElementById('resetUserName').textContent = userName;
                    const form = document.getElementById('resetPasswordForm');
                    form.action = `/super/users/${employeeId}/reset-password`;

                    const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
                    modal.show();
                });
            });

            // Handle password reset form submission
            document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const form = this;
                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('resetPasswordModal')).hide();
                        // Show success alert
                        const alert = document.getElementById('resetPasswordAlert');
                        const message = document.getElementById('resetPasswordMessage');
                        message.textContent = data.message;
                        alert.classList.remove('d-none');

                        // Hide alert after 5 seconds
                        setTimeout(() => {
                            alert.classList.add('d-none');
                        }, 5000);
                    } else {
                        alert(data.message || 'Error resetting password');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error resetting password');
                });
            });

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

            // Handle delete button clicks
            document.querySelectorAll('.delete-user-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const employeeId = parseInt(this.getAttribute('data-id'));
                    const userName = this.getAttribute('data-name');

                    document.getElementById('deleteUserName').textContent = userName;
                    const form = document.getElementById('deleteUserForm');
                    form.action = `/super/users/${employeeId}`;

                    const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
                    modal.show();
                });
            });
        });
    </script>
@endsection
