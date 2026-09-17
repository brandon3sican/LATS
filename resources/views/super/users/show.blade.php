@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div id="resetPasswordAlert" class="alert alert-success d-none mb-4">
        <i class="bi bi-check-circle me-2"></i>
        <span id="resetPasswordMessage"></span>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0">User Details</h3>
            <div class="text-muted">View complete user information and roles</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('super.users.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Users
            </a>
            <button class="btn btn-warning" onclick="confirmResetPassword({{ $employee->id }}, '{{ $employee->user->first_name }} {{ $employee->user->last_name }}')">
                <i class="bi bi-key me-1"></i> Reset Password
            </button>
            <button class="btn btn-danger" onclick="confirmDelete({{ $employee->id }}, '{{ $employee->user->first_name }} {{ $employee->user->last_name }}')">
                <i class="bi bi-trash me-1"></i> Delete User
            </button>
            <a href="{{ route('super.users.edit', $employee->id) }}" class="btn btn-primary">
                <i class="bi bi-pencil-square me-1"></i> Edit User
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i>Account Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="fw-bold" style="width: 40%">First Name:</td>
                            <td>{{ $employee->user->first_name }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Middle Name:</td>
                            <td>{{ $employee->user->middle_name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Last Name:</td>
                            <td>{{ $employee->user->last_name }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Full Name:</td>
                            <td>{{ $employee->user->name }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Email:</td>
                            <td>{{ $employee->user->email }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Account Status:</td>
                            <td>
                                @if ($employee->status === 'active')
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($employee->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-briefcase me-2"></i>Employment Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="fw-bold" style="width: 40%">Office:</td>
                            <td>{{ $employee->office->name ?? 'No Office' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Division:</td>
                            <td>{{ $employee->division->name ?? 'No Division' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Position:</td>
                            <td>{{ $employee->position_title }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Salary Grade:</td>
                            <td>{{ $employee->salary_grade ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Sex:</td>
                            <td>{{ $employee->sex ? ($employee->sex === 'M' ? 'Male' : 'Female') : '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>System Roles</h5>
        </div>
        <div class="card-body">
            <div class="row">
                @forelse ($employee->user->roles as $role)
                    <div class="col-md-4 mb-3">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body py-3 px-3">
                                <div class="fw-bold mb-1">{{ $role->name }}</div>
                                <div class="small text-muted mb-2">{{ $role->key }}</div>
                                @if($role->key === 'super_admin')
                                    <span class="badge bg-danger">SUPER ADMIN</span>
                                @elseif($role->key === 'office_admin' || $role->key === 'admin')
                                    <span class="badge bg-primary">ADMIN</span>
                                @elseif(str_contains($role->key, 'approver'))
                                    <span class="badge bg-warning text-dark">APPROVER</span>
                                @else
                                    <span class="badge bg-info text-dark">EMPLOYEE</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-warning mb-0">No roles assigned to this user.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Account Activity</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="text-center p-3 bg-light rounded">
                        <div class="text-muted small">Account Created</div>
                        <div class="fw-bold">{{ $employee->user->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3 bg-light rounded">
                        <div class="text-muted small">Last Updated</div>
                        <div class="fw-bold">{{ $employee->user->updated_at->format('M d, Y') }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3 bg-light rounded">
                        <div class="text-muted small">Employee ID</div>
                        <div class="fw-bold">#{{ $employee->id }}</div>
                    </div>
                </div>
            </div>
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

<script>
function confirmResetPassword(userId, userName) {
    document.getElementById('resetUserName').textContent = userName;
    const form = document.getElementById('resetPasswordForm');
    form.action = `/super/users/${userId}/reset-password`;

    const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    modal.show();
}

function confirmDelete(userId, userName) {
    document.getElementById('deleteUserName').textContent = userName;
    const form = document.getElementById('deleteUserForm');
    form.action = `/super/users/${userId}`;

    const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    modal.show();
}

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
</script>
@endsection