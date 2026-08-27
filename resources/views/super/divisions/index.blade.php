@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Divisions</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDivisionModal">Create
                Division</button>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @elseif (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form class="mb-3" method="GET">
            <div class="input-group">
                <input class="form-control" name="q" value="{{ $q }}"
                    placeholder="Search division name or office">
                <button class="btn btn-outline-secondary">Search</button>
            </div>
        </form>

        @php $offices = \App\Models\Office::orderBy('name')->get(); @endphp

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
                                    <button class="btn btn-sm btn-outline-primary edit-division-btn"
                                        data-id="{{ $division->id }}">Edit</button>
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

        {{-- Edit Division Modal --}}
        <div class="modal fade" id="editDivisionModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Division</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" id="editDivisionForm">
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

                            <div class="mb-3">
                                <label class="form-label">Office</label>
                                <select class="form-select" name="office_id" id="editOfficeSelect" required>
                                    <option value="">Select Office</option>
                                    @foreach($offices as $office)
                                        <option value="{{ $office->id }}">{{ $office->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Division Name</label>
                                <input class="form-control" name="name" id="editDivisionName" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Store division data for editing
        const divisionsData = @json($divisions->items());
        
        document.addEventListener('DOMContentLoaded', function() {
            // Handle edit button clicks
            document.querySelectorAll('.edit-division-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const divisionId = parseInt(this.getAttribute('data-id'));
                    const division = divisionsData.find(d => d.id === divisionId);
                    
                    if (division) {
                        const form = document.getElementById('editDivisionForm');
                        // Set the form action dynamically
                        form.action = `/super/divisions/${divisionId}`;
                        document.getElementById('editDivisionName').value = division.name;
                        document.getElementById('editOfficeSelect').value = division.office_id;
                        
                        const modal = new bootstrap.Modal(document.getElementById('editDivisionModal'));
                        modal.show();
                    }
                });
            });
        });
    </script>
@endsection
