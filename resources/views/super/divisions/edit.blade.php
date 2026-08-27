@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h3 class="mb-3">Edit Division</h3>

  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('super.divisions.update', $division->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
          <label class="form-label">Office</label>
          <select class="form-select" name="office_id" required>
            <option value="">Select Office</option>
            @foreach($offices as $office)
              <option value="{{ $office->id }}" {{ old('office_id', $division->office_id) == $office->id ? 'selected' : '' }}>
                {{ $office->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Division Name</label>
          <input class="form-control" name="name" value="{{ old('name', $division->name) }}" required>
        </div>

        <button class="btn btn-primary">Update</button>
        <a class="btn btn-outline-secondary" href="{{ route('super.divisions.index') }}">Cancel</a>
      </form>
    </div>
  </div>
</div>
@endsection
