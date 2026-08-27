@extends('layouts.app')
@section('content')
    <div class="container py-4">
        <h3 class="mb-4">Super Admin Dashboard</h3>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card text-bg-primary h-100">
                    <div class="card-body">
                        <h5 class="card-title">Registered Offices</h5>
                        <p class="display-4 fw-bold">{{ $stats['offices'] }}</p>
                        <a href="{{ route('super.offices.index') }}" class="btn btn-light btn-sm text-primary">Manage
                            Offices</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-bg-success h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <p class="display-4 fw-bold">{{ $stats['users'] }}</p>
                        <a href="{{ route('super.users.index') }}" class="btn btn-light btn-sm text-success">Manage
                            Users</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-bg-info text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Office Admins</h5>
                        <p class="display-4 fw-bold">{{ $stats['admins'] }}</p>
                    </div>
                </div>
            </div>
        </div>
        <h4 class="mb-3 mt-4">Leave Request Statistics</h4>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="card text-bg-secondary h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Filed</h5>
                        <p class="display-4 fw-bold">{{ $stats['leaves']['total'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-success h-100">
                    <div class="card-body">
                        <h5 class="card-title">Approved</h5>
                        <p class="display-4 fw-bold">{{ $stats['leaves']['approved'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-warning h-100">
                    <div class="card-body">
                        <h5 class="card-title">Returned</h5>
                        <p class="display-4 fw-bold">{{ $stats['leaves']['returned'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-danger h-100">
                    <div class="card-body">
                        <h5 class="card-title">Disapproved</h5>
                        <p class="display-4 fw-bold">{{ $stats['leaves']['disapproved'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
