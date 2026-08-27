@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Edit Employee: {{ $employee->user->name }}</h3>
        <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.employees.update', $employee->id) }}" method="POST">
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
                        <label class="form-label">Office</label>
                        <input type="text" class="form-control bg-light" value="{{ $adminOffice->name }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Division</label>
                        <select name="division_id" class="form-select">
                            <option value="">-- Select Division --</option>
                            @foreach($divisions as $div)
                                <option value="{{ $div->id }}" {{ $employee->division_id == $div->id ? 'selected' : '' }}>
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
                                    {{ $employee->user->hasRole($role->key) ? 'checked' : '' }}
                                    {{ $role->key === 'employee' ? 'onclick="return false;"' : '' }}>
                                    <label class="form-check-label" for="role_{{ $role->id }}">
                                        {{ $role->name }}
                                        @if(str_contains($role->key, 'approver'))
                                            <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">APPROVER</span>
                                        @endif
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary px-4">Update Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
