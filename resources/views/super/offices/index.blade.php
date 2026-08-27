@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Offices</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createOfficeModal">Create Office</button>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @elseif (session('status'))
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
                                    <button class="btn btn-sm btn-outline-primary edit-office-btn"
                                        data-id="{{ $o->id }}">Edit</button>
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

        {{-- Edit Office Modal --}}
        <div class="modal fade" id="editOfficeModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Office</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" id="editOfficeForm">
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
                                <label class="form-label">Office Code</label>
                                <input class="form-control" name="office_code" id="editOfficeCode" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Office Name</label>
                                <input class="form-control" name="name" id="editOfficeName" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <input class="form-control" name="address" id="editOfficeAddress">
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
        // Store office data for editing
        const officesData = @json($offices->items());
        
        document.addEventListener('DOMContentLoaded', function() {
            // Handle edit button clicks
            document.querySelectorAll('.edit-office-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const officeId = parseInt(this.getAttribute('data-id'));
                    const office = officesData.find(o => o.id === officeId);
                    
                    if (office) {
                        const form = document.getElementById('editOfficeForm');
                        // Set the form action dynamically
                        form.action = `/super/offices/${officeId}`;
                        document.getElementById('editOfficeCode').value = office.office_code;
                        document.getElementById('editOfficeName').value = office.name;
                        document.getElementById('editOfficeAddress').value = office.address || '';
                        
                        const modal = new bootstrap.Modal(document.getElementById('editOfficeModal'));
                        modal.show();
                    }
                });
            });
        });
    </script>
@endsection
