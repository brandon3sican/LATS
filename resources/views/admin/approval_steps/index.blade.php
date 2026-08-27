@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h3 class="mb-3">Approval Steps (This Office)</h3>

  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('admin.approvalSteps.update') }}">
        @csrf
        @method('PUT')

        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th style="width: 80px;">Order</th>
                <th>Step Name</th>
                <th>Approver Role</th>
              </tr>
            </thead>
            <tbody>
              @foreach($steps as $i => $s)
                <tr>
                  <td class="fw-semibold">{{ $s->step_order }}</td>

                  <td>
                    <input type="hidden" name="steps[{{ $i }}][id]" value="{{ $s->id }}">
                    <input class="form-control" name="steps[{{ $i }}][name]" value="{{ old("steps.$i.name", $s->name) }}" required>
                  </td>

                  <td>
                    <select class="form-select" name="steps[{{ $i }}][role_key]" required>
                      @foreach($allowedRoleKeys as $rk)
                        <option value="{{ $rk }}" {{ old("steps.$i.role_key", $s->role_key) === $rk ? 'selected' : '' }}>
                          {{ $rk }}
                        </option>
                      @endforeach
                    </select>
                    <div class="small text-muted mt-1">
                      Step 1 is usually division-based (Division Chief).
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <button class="btn btn-primary">Save Steps</button>
      </form>
    </div>
  </div>
</div>
@endsection
