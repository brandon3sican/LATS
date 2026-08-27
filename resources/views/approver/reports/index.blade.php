@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-4 fw-bold text-dark">Approver Reports</h3>

    <div class="row g-4">
        {{-- Card: My Action History --}}
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary me-3">
                            <i class="bi bi-clock-history fs-4"></i>
                        </div>
                        <h5 class="card-title mb-0 fw-bold">My Action History</h5>
                    </div>
                    <p class="card-text text-muted small">
                        View a detailed log of all leave applications you have processed (Approved, Disapproved, or Returned).
                    </p>
                    {{-- This button links to the separate My Actions page --}}
                    <a href="{{ route('approver.reports.myActions') }}" class="btn btn-outline-primary stretched-link w-100">
                        View Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
