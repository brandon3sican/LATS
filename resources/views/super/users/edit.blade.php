@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Edit User: {{ $employee->user->first_name }} {{ $employee->user->last_name }}</h3>
        <a href="{{ route('super.users.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('super.users.update', $employee->id) }}" method="POST">
                @csrf
                @method('PUT')

                <h5 class="mb-3 text-primary">Account Details</h5>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control"
                               value="{{ old('first_name', $employee->user->first_name) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control"
                               value="{{ old('middle_name', $employee->user->middle_name) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control"
                               value="{{ old('last_name', $employee->user->last_name) }}" required>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                     <div class="col-md-6">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ $employee->user->email }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Account Status</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ $employee->status == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $employee->status == 'inactive' ? 'selected' : '' }}>Inactive / Retired</option>
                            <option value="suspended" {{ $employee->status == 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password <small class="text-muted">(Leave blank to keep current)</small></label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>
                </div>

                <h5 class="mb-3 text-primary">Employment Details</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Office Assignment <span class="text-danger">*</span></label>
                        <select name="office_id" id="office_id" class="form-select" required onchange="filterDivisions(true)">
                            @foreach($offices as $office)
                                <option value="{{ $office->id }}" {{ $employee->office_id == $office->id ? 'selected' : '' }}>
                                    {{ $office->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Division</label>
                        <select name="division_id" id="division_id" class="form-select">
                            <option value="">-- Select Division --</option>
                            @foreach($divisions as $div)
                                <option value="{{ $div->id }}"
                                        data-office="{{ $div->office_id }}"
                                        {{ $employee->division_id == $div->id ? 'selected' : '' }}
                                        style="{{ $div->office_id == $employee->office_id ? '' : 'display:none' }}">
                                    {{ $div->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Position Title <span class="text-danger">*</span></label>
                        <input type="text" name="position_title" class="form-control" value="{{ $employee->position_title }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Salary Grade</label>
                        <input type="number" name="salary_grade" class="form-control" value="{{ $employee->salary_grade }}" min="1" max="33">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sex</label>
                        <select name="sex" class="form-select">
                            <option value="">--</option>
                            <option value="M" {{ $employee->sex == 'M' ? 'selected' : '' }}>Male</option>
                            <option value="F" {{ $employee->sex == 'F' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                </div>

                <h5 class="mb-3 text-primary">System Roles & Approver Access</h5>
                <div class="mb-4">
                    <div class="row">
                        @foreach($roles as $role)
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="role_{{ $role->id }}"
                                    {{ $employee->user->hasRole($role->key) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="role_{{ $role->id }}">
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

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary px-4">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function filterDivisions(resetSelection = false) {
    const officeId = document.getElementById('office_id').value;
    const divisionSelect = document.getElementById('division_id');
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

// Run on page load so divisions list is correct
document.addEventListener("DOMContentLoaded", function() {
    filterDivisions(false);
});
</script>
@endsection
