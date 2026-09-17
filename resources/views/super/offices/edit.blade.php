<div class="modal fade" id="editOfficeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Office</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('super.offices.update', $office->id) }}">
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
                        <input class="form-control" name="office_code" value="{{ old('office_code', $office->office_code) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Office Name</label>
                        <input class="form-control" name="name" value="{{ old('name', $office->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input class="form-control" name="address" value="{{ old('address', $office->address) }}">
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
