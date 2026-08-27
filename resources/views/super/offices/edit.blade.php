@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h3 class="mb-3">Edit Office</h3>

  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('super.offices.update', $office->id) }}">
        @csrf
        @method('PUT')

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

        <button class="btn btn-primary">Update</button>
        <a class="btn btn-outline-secondary" href="{{ route('super.offices.index') }}">Cancel</a>
      </form>
    </div>
  </div>
</div>
@endsection
