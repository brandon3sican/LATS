@php
    $user = Auth::user();
    $user->loadMissing('roles');

    $hasRole = function (string $key) use ($user) {
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($key);
        }
        return $user->roles->contains('key', $key);
    };

    $isEmployee = $hasRole('employee');
    $isApprover =
        $hasRole('approver_division_chief') ||
        $hasRole('approver_personnel') ||
        $hasRole('approver_chief_personnel') ||
        $hasRole('approver_chief_admin') ||
        $hasRole('approver_ard_ms');
    $isChiefPersonnel = $hasRole('approver_chief_personnel');
    $isOfficeAdmin = $hasRole('office_admin');
    $isSuper = $hasRole('super_admin');
@endphp

<div class="p-3">
    <div class="fw-bold mb-2">LATS</div>
    <div class="text-muted small mb-3">
        {{ $user->name }}
        <div class="mt-1">
            @foreach ($user->roles as $r)
                <span class="badge text-bg-secondary">{{ $r->key }}</span>
            @endforeach
        </div>
    </div>

    <div class="list-group list-group-flush">

        <a class="list-group-item list-group-item-action" href="{{ route('dashboard') }}">
            Dashboard
        </a>

        <a class="list-group-item list-group-item-action" href="{{ route('employee.profile.show') }}">
            My Profile
        </a>

        @if ($isEmployee)
            <div class="mt-3 small text-uppercase text-muted">Employee</div>

            <a class="list-group-item list-group-item-action" href="{{ route('employee.leaves.create') }}">
                Apply Leave
            </a>

            <a class="list-group-item list-group-item-action" href="{{ route('employee.leaves.index') }}">
                My Leaves
            </a>

            <a class="list-group-item list-group-item-action" href="{{ route('employee.reports.myForms') }}">
                My Reports
            </a>
        @endif

        @if ($isApprover)
            <div class="mt-3 small text-uppercase text-muted">Approver</div>

            <a class="list-group-item list-group-item-action" href="{{ route('approver.dashboard') }}">
                Approver Dashboard
            </a>

            <a class="list-group-item list-group-item-action" href="{{ route('approver.inbox') }}">
                Inbox (Pending)
            </a>

            <a class="list-group-item list-group-item-action" href="{{ route('approver.reports.myActions') }}">
                My Actions
            </a>

            <a class="list-group-item list-group-item-action" href="{{ route('approver.reports.index') }}">
                Reports
            </a>
        @endif

        @if ($isOfficeAdmin)
            <div class="mt-3 small text-uppercase text-muted">Office Admin</div>

            <a class="list-group-item list-group-item-action" href="{{ route('admin.approvalSteps.index') }}">
                Approval Steps
            </a>

            <a class="list-group-item list-group-item-action" href="{{ route('admin.reports.index') }}">
                Reports
            </a>
        @endif

        @if ($isChiefPersonnel)
            <div class="mt-3 small text-uppercase text-muted">Chief Personnel</div>

            <a class="list-group-item list-group-item-action" href="{{ route('super.reports.generate') }}">
                Generate Reports
            </a>

            <a class="list-group-item list-group-item-action" href="{{ route('super.audit-logs.index') }}">
                Audit Logs
            </a>
        @endif

        @if ($isSuper)
            <div class="mt-3 small text-uppercase text-muted">Super Admin</div>

            <a class="list-group-item list-group-item-action" href="{{ route('super.offices.index') }}">Offices</a>
            <a class="list-group-item list-group-item-action" href="{{ route('super.divisions.index') }}">Divisions</a>
            <a class="list-group-item list-group-item-action" href="{{ route('super.users.index') }}">Users</a>

            <a class="list-group-item list-group-item-action" href="{{ route('super.reports.generate') }}">
                Generate Reports
            </a>

            <a class="list-group-item list-group-item-action" href="{{ route('super.audit-logs.index') }}">
                Audit Logs
            </a>
        @endif

        <div class="mt-3 small text-uppercase text-muted">Account</div>

        <a class="list-group-item list-group-item-action" href="{{ route('profile.edit') }}">
            Settings
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="list-group-item list-group-item-action w-100 text-start border-0 bg-transparent text-danger">
                Logout
            </button>
        </form>

    </div>
</div>
