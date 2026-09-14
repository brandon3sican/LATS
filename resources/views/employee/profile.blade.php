@extends('layouts.app')

@section('content')
@php
    $user->loadMissing('roles');
@endphp

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

                    <div class="mt-3">
                        <label class="small text-muted text-uppercase fw-bold">Roles</label>
                        <div class="d-flex flex-wrap gap-2 justify-content-center mt-2">
                            @foreach ($user->roles as $role)
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                    <i class="bi bi-shield-check me-1"></i>
                                    {{ str_replace('_', ' ', ucfirst($role->key)) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Employment Details --}}
        <div class="col-md-8">
            <div class="card shadow-sm h-100 modern-card">
                <div class="card-header bg-white fw-bold modern-card-header">
                    <div class="d-flex align-items-center">
                        <div class="modern-card-icon bg-primary bg-opacity-10">
                            <i class="bi bi-briefcase text-primary"></i>
                        </div>
                        <span class="ms-2">Employment Information</span>
                    </div>
                </div>
                <div class="card-body">
                    @if($employee)
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="modern-info-item">
                                    <div class="modern-info-label">
                                        <i class="bi bi-person-workspace me-2"></i>Position Title
                                    </div>
                                    <div class="modern-info-value">{{ $employee->position_title ?? 'Not Assigned' }}</div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="modern-info-item">
                                    <div class="modern-info-label">
                                        <i class="bi bi-currency-dollar me-2"></i>Salary Grade
                                    </div>
                                    <div class="modern-info-value">
                                        {{ $employee->salary_grade ? 'SG ' . $employee->salary_grade : 'N/A' }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="modern-info-item">
                                    <div class="modern-info-label">
                                        <i class="bi bi-gender-ambiguous me-2"></i>Sex
                                    </div>
                                    <div class="modern-info-value">
                                        {{ $employee->sex ? $employee->sex : '—' }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="modern-info-item">
                                    <div class="modern-info-label">
                                        <i class="bi bi-building me-2"></i>Office / Department
                                    </div>
                                    <div class="modern-info-value">{{ $employee->office->name ?? 'Pending Assignment' }}</div>
                                </div>
                            </div>

                            <div class="col-md-7">
                                <div class="modern-info-item">
                                    <div class="modern-info-label">
                                        <i class="bi bi-diagram-3 me-2"></i>Division / Unit
                                    </div>
                                    <div class="modern-info-value">{{ $employee->division->name ?? 'Pending Assignment' }}</div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="modern-info-item">
                                    <div class="modern-info-label">
                                        <i class="bi bi-activity me-2"></i>Employment Status
                                    </div>
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
                                        <span class="modern-status-badge modern-status-{{ $badgeColor }}">
                                            <i class="bi bi-circle-fill me-1"></i>
                                            {{ ucfirst($status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="modern-alert modern-alert-warning">
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
        <div class="col-12">
            <div class="card shadow-sm modern-card">
                <div class="card-header bg-white fw-bold modern-card-header">
                    <div class="d-flex align-items-center">
                        <div class="modern-card-icon bg-success bg-opacity-10">
                            <i class="bi bi-pen text-success"></i>
                        </div>
                        <span class="ms-2">Standard E-Signature</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        {{-- Left: Current Signature Preview --}}
                        <div class="col-md-5">
                            @if($user->signature_path)
                                <div class="modern-signature-preview h-100">
                                    <div class="modern-signature-label">
                                        <i class="bi bi-image me-2"></i>Current Signature
                                    </div>
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
                                        <div class="modern-signature-image">
                                            <img src="{{ $imgSrc }}" alt="Signature" class="img-fluid">
                                        </div>
                                    @else
                                        <div class="modern-alert modern-alert-danger">
                                            <i class="bi bi-exclamation-circle me-2"></i>Image missing.
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="modern-alert modern-alert-info h-100 d-flex align-items-center justify-content-center">
                                    <div class="text-center">
                                        <i class="bi bi-info-circle d-block mb-2" style="font-size: 2rem;"></i>
                                        No signature uploaded yet.
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Right: Signature Input Tabs --}}
                        <div class="col-md-7">
                            <div class="modern-tabs-container h-100">
                                <ul class="nav nav-pills modern-tabs mb-3" id="signatureTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link modern-tab-link active" id="upload-tab" data-bs-toggle="pill" data-bs-target="#upload-pane" type="button" role="tab">
                                            <i class="bi bi-upload me-2"></i> Upload
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link modern-tab-link" id="draw-tab" data-bs-toggle="pill" data-bs-target="#draw-pane" type="button" role="tab">
                                            <i class="bi bi-pencil me-2"></i> Draw
                                        </button>
                                    </li>
                                </ul>

                                <div class="tab-content modern-tab-content" id="signatureTabsContent">
                                    {{-- Upload Option --}}
                                    <div class="tab-pane fade show active" id="upload-pane" role="tabpanel">
                                        <form action="{{ route('employee.profile.signature') }}" method="POST" enctype="multipart/form-data" class="modern-form">
                                            @csrf
                                            <div class="modern-file-upload">
                                                <input type="file" name="signature" id="signatureFile" class="modern-file-input" accept="image/png, image/jpeg, image/jpg" required>
                                                <label for="signatureFile" class="modern-file-label">
                                                    <i class="bi bi-cloud-upload"></i>
                                                    <span>Choose image file</span>
                                                    <small>PNG, JPG up to 2MB</small>
                                                </label>
                                            </div>
                                            <button type="submit" class="btn btn-primary modern-btn w-100">
                                                <i class="bi bi-upload me-2"></i> Upload Signature
                                            </button>
                                        </form>
                                    </div>

                                    {{-- Draw Option --}}
                                    <div class="tab-pane fade" id="draw-pane" role="tabpanel">
                                        <form action="{{ route('employee.profile.signature') }}" method="POST" id="drawSignatureForm" class="modern-form">
                                            @csrf
                                            <input type="hidden" name="signature_data" id="signatureData">
                                            <div class="modern-canvas-container">
                                                <canvas id="signatureCanvas" class="modern-canvas" style="width: 100%; height: 180px;"></canvas>
                                                <div class="modern-canvas-hint">
                                                    <i class="bi bi-pencil-square me-1"></i> Draw your signature here
                                                </div>
                                            </div>
                                            <div class="modern-canvas-actions">
                                                <button type="button" class="btn btn-outline-secondary modern-btn flex-1" id="clearCanvas">
                                                    <i class="bi bi-eraser me-2"></i> Clear
                                                </button>
                                                <button type="submit" class="btn btn-success modern-btn flex-1" id="saveSignature">
                                                    <i class="bi bi-check-circle me-2"></i> Save
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('signatureCanvas');
    const ctx = canvas.getContext('2d');
    const clearBtn = document.getElementById('clearCanvas');
    const saveBtn = document.getElementById('saveSignature');
    const signatureDataInput = document.getElementById('signatureData');
    const drawForm = document.getElementById('drawSignatureForm');
    const drawTab = document.getElementById('draw-tab');

    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;

    // Set canvas size
    function resizeCanvas() {
        if (!canvas) return;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = 180; // Fixed height for consistency
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
    }

    // Initialize canvas when draw tab is shown
    drawTab.addEventListener('shown.bs.tab', function() {
        setTimeout(resizeCanvas, 100);
    });

    // Initial resize
    setTimeout(resizeCanvas, 100);
    window.addEventListener('resize', resizeCanvas);

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        const clientX = e.clientX || (e.touches && e.touches[0] ? e.touches[0].clientX : 0);
        const clientY = e.clientY || (e.touches && e.touches[0] ? e.touches[0].clientY : 0);
        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
    }

    function startDrawing(e) {
        isDrawing = true;
        const pos = getPos(e);
        lastX = pos.x;
        lastY = pos.y;
        e.preventDefault();
    }

    function draw(e) {
        if (!isDrawing) return;
        const pos = getPos(e);
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        lastX = pos.x;
        lastY = pos.y;
        e.preventDefault();
    }

    function stopDrawing() {
        isDrawing = false;
    }

    // Mouse events
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);

    // Touch events
    canvas.addEventListener('touchstart', startDrawing, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', stopDrawing);

    // Clear canvas
    clearBtn.addEventListener('click', function() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    });

    // Save signature
    saveBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const dataURL = canvas.toDataURL('image/png');
        signatureDataInput.value = dataURL;
        drawForm.submit();
    });

    // File upload label update
    const fileInput = document.getElementById('signatureFile');
    const fileLabel = document.querySelector('.modern-file-label');

    if (fileInput && fileLabel) {
        fileInput.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                fileLabel.querySelector('span').textContent = fileName;
                fileLabel.querySelector('small').textContent = 'Ready to upload';
                fileLabel.style.borderColor = 'var(--lais-primary)';
                fileLabel.style.background = 'linear-gradient(135deg, rgba(37,99,235,0.1), rgba(59,130,246,0.1))';
                fileLabel.style.color = 'var(--lais-primary)';
            }
        });
    }
});
</script>
@endsection
