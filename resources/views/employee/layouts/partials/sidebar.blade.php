@php
  $user = Auth::user();
  $user->loadMissing('roles');

  $hasRole = function(string $key) use ($user) {
      if (method_exists($user, 'hasRole')) return $user->hasRole($key);
      return $user->roles->contains('key', $key);
  };

  $isEmployee = $hasRole('employee');
  $isApprover = $hasRole('approver_division_chief') || $hasRole('approver_personnel') || $hasRole('approver_chief_personnel') || $hasRole('approver_chief_admin') || $hasRole('approver_ard_ms');
  $isOfficeAdmin = $hasRole('office_admin');
  $isSuper = $hasRole('super_admin');

  $active = fn($name) => request()->routeIs($name) ? 'active' : '';
@endphp

<div class="lais-sidebar-inner">

    {{-- User Info Section --}}
    <a class="lais-side-user text-decoration-none text-reset"
       href="{{ route('employee.profile.show') }}"
       title="View My Profile & Signature">
        <div class="lais-avatar">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div class="flex-grow-1">
            <div class="fw-semibold">{{ $user->name }}</div>
            <div class="text-muted small">
                @foreach($user->roles as $r)
                    <span class="badge text-bg-light border me-1">{{ str_replace('_', ' ', strtoupper($r->key)) }}</span>
                @endforeach
            </div>
        </div>
    </a>

    <div class="lais-nav-section">
        <div class="lais-nav-title">General</div>
        <a class="lais-nav-link {{ request()->routeIs('dashboard') || request()->routeIs('*.dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </div>

    @if($isEmployee)
        <div class="lais-nav-section">
            <div class="lais-nav-title">Employee</div>

            <a class="lais-nav-link {{ $active('employee.leaves.create') }}" href="{{ route('employee.leaves.create') }}">
                <i class="bi bi-plus-circle"></i> Apply Leave
            </a>

            <a class="lais-nav-link {{ $active('employee.leaves.index') }}" href="{{ route('employee.leaves.index') }}">
                <i class="bi bi-clock-history"></i> My Leaves
            </a>
        </div>
    @endif

    @if($isApprover)
        <div class="lais-nav-section">
            <div class="lais-nav-title">Approver</div>
            <a class="lais-nav-link {{ $active('approver.inbox') }}" href="{{ route('approver.inbox') }}">
                <i class="bi bi-inbox"></i> Inbox (Pending)
            </a>

            <a class="lais-nav-link {{ $active('approver.reports.myActions') }}" href="{{ route('approver.reports.myActions') }}">
                <i class="bi bi-clock-history"></i> My Action History
            </a>
        </div>
    @endif

    @if($isOfficeAdmin)
        <div class="lais-nav-section">
            <div class="lais-nav-title">Office Admin</div>

            <a class="lais-nav-link {{ $active('admin.employees.index') }}" href="{{ route('admin.employees.index') }}">
                <i class="bi bi-people me-2"></i>Manage Employees
            </a>
            <a class="lais-nav-link {{ $active('admin.reports.index') }}"href="{{ route('admin.reports.index') }}">
            <i class="bi bi-graph-up me-2"></i>Reports
            </a>
            <a class="lais-nav-link {{ $active('admin.approvalSteps.index') }}" href="{{ route('admin.approvalSteps.index') }}">
                <i class="bi bi-diagram-3"></i>Approval Steps
            </a>
        </div>
    @endif

    @if($isSuper)
        <div class="lais-nav-section">
            <div class="lais-nav-title">Super Admin</div>
            <a class="lais-nav-link {{ $active('super.offices.index') }}" href="{{ route('super.offices.index') }}">
                <i class="bi bi-building"></i> Offices
            </a>
            <a class="lais-nav-link {{ $active('super.users.index') }}" href="{{ route('super.users.index') }}">
                <i class="bi bi-people"></i> Users & Roles
            </a>
        </div>
    @endif
</div>
