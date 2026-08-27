@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Offices</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createOfficeModal">Create Office</button>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form class="mb-3" method="GET">
            <div class="input-group">
                <input class="form-control" name="q" value="{{ $q }}"
                    placeholder="Search office name or code">
                <button class="btn btn-outline-secondary">Search</button>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Office Code</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($offices as $o)
                            <tr>
                                <td>{{ $o->office_code }}</td>
                                <td>{{ $o->name }}</td>
                                <td>{{ $o->address ?? '—' }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('super.offices.edit', $o->id) }}">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No offices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $offices->links() }}</div>

        {{-- Create Office Modal --}}
        @include('super.offices.create')
    </div>
@endsection
