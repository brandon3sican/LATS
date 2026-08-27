@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Divisions</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDivisionModal">Create
                Division</button>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form class="mb-3" method="GET">
            <div class="input-group">
                <input class="form-control" name="q" value="{{ $q }}"
                    placeholder="Search division name or office">
                <button class="btn btn-outline-secondary">Search</button>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Office</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($divisions as $division)
                            <tr>
                                <td>{{ $division->name }}</td>
                                <td>{{ $division->office->name ?? '—' }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('super.divisions.edit', $division->id) }}">Edit</a>
                                    <form method="POST" action="{{ route('super.divisions.destroy', $division->id) }}"
                                        class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to delete this division?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No divisions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $divisions->links() }}</div>

        {{-- Create Division Modal --}}
        @include('super.divisions.create')
    </div>
@endsection
