@extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="mb-0">My Employee Profile</h3>
            <div class="text-muted">View your employment information.</div>
        </div>
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Card 1: Account Information --}}
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-person-circle me-2"></i> Account Details
                </div>
                <div class="card-body text-center py-4">
                    <div class="mb-3">
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fs-2" style="width: 80px; height: 80px;">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                    </div>
                    <h5 class="card-title">{{ $user->name }}</h5>
                    <p class="card-text text-muted">{{ $user->email }}</p>

                    <div class="mt-3">
                        <span class="badge bg-secondary">
                            User ID: {{ $user->id }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Employment Details --}}
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-briefcase me-2"></i> Employment Information
                </div>
                <div class="card-body">
                    @if($employee)
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small text-muted text-uppercase fw-bold">Position Title</label>
                                <div class="fs-5 border-bottom pb-1">{{ $employee->position_title ?? 'Not Assigned' }}</div>
                            </div>

                            <div class="col-md-3">
                                <label class="small text-muted text-uppercase fw-bold">Salary Grade</label>
                                <div class="fs-5 border-bottom pb-1">
                                    {{ $employee->salary_grade ? 'SG ' . $employee->salary_grade : 'N/A' }}
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="small text-muted text-uppercase fw-bold">Sex</label>
                                <div class="fs-5 border-bottom pb-1">
                                    {{ $employee->sex ? $employee->sex : '—' }}
                                </div>
                            </div>

                            <div class="col-md-5 pt-3">
                                <label class="small text-muted text-uppercase fw-bold">Office / Department</label>
                                <div class="fs-5 border-bottom pb-1">{{ $employee->office->name ?? 'Pending Assignment' }}</div>
                            </div>

                            <div class="col-md-7 pt-3">
                                <label class="small text-muted text-uppercase fw-bold">Division / Unit</label>
                                <div class="fs-5 border-bottom pb-1">{{ $employee->division->name ?? 'Pending Assignment' }}</div>
                            </div>

                            <div class="col-md-12 pt-3">
                                <label class="small text-muted text-uppercase fw-bold">Employment Status</label>
                                <div>
                                    @php
                                        $status = $employee->status ?? 'unknown';
                                        $badgeColor = match($status) {
                                            'active' => 'success',
                                            'inactive' => 'secondary',
                                            'suspended' => 'danger',
                                            default => 'warning'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badgeColor }} fs-6 px-3 py-2 text-capitalize">
                                        {{ $status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Your employee profile has not been set up yet. Please contact the administrator.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    {{-- Signatures Row --}}
    <div class="row g-4 mt-1">

        {{-- E-Signature Upload Card (Image) --}}
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-pen me-2"></i> Standard E-Signature
                </div>
                <div class="card-body text-center">

                    {{-- Current Signature Preview --}}
                    @if($user->signature_path)
                        <div class="mb-3">
                            <span class="d-block text-muted small mb-1">Current Signature:</span>
                            @php
                                $fullPath = storage_path('app/public/' . $user->signature_path);
                                $imgSrc = '';
                                if (file_exists($fullPath)) {
                                    $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
                                    $data = base64_encode(file_get_contents($fullPath));
                                    $imgSrc = "data:image/$ext;base64,$data";
                                }
                            @endphp

                            @if($imgSrc)
                                <img src="{{ $imgSrc }}" alt="Signature" class="img-fluid border rounded p-2" style="max-height: 160px; width: auto;">
                            @else
                                <div class="alert alert-danger py-1 small">Image missing.</div>
                            @endif
                        </div>
                    @else
                        <div class="alert alert-secondary small py-2">No image uploaded yet.</div>
                    @endif

                    {{-- Upload Form --}}
                    <form action="{{ route('employee.profile.signature') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2 text-start">
                            <input type="file" name="signature" class="form-control form-control-sm" accept="image/png, image/jpeg, image/jpg" required>
                        </div>
                        <button type="submit" class="btn btn-secondary btn-sm w-100">
                            Upload Image
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
