@php
  $user = Auth::user();
@endphp

<nav class="navbar lais-topbar navbar-expand-lg sticky-top bg-white shadow-sm">
    <div class="container-fluid">

        <button class="btn btn-sm lais-icon-btn d-lg-none me-2"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#laisSidebar">
            <i class="bi bi-list"></i>
        </button>

        <a class="navbar-brand d-flex align-items-center gap-3" href="{{ route('dashboard') }}">
            <img src="{{ asset('images/denr_logo.png') }}"
                 alt="DENR Logo"
                 class="denr-logo"
                 style="height: 45px; width: auto;">

            <div class="d-flex flex-column justify-content-center">
                <span class="fw-bold text-primary" style="font-size: 1.25rem; letter-spacing: 0.5px;">DENR</span>
                <span class="fw-semibold text-secondary" style="font-size: 0.7rem; letter-spacing: 1px;">
                    {{-- Shows only on Medium screens and larger (Laptops/Desktops) --}}
                    <span class="d-none d-md-inline">Leave Application Tracking System</span>

                    {{-- Shows only on Small screens (Mobile Phones) --}}
                    <span class="d-inline d-md-none">LATS</span>
                </span>
            </div>
        </a>

        <div class="ms-auto d-flex align-items-center gap-3">

            {{-- NOTIFICATION BELL --}}
            <div class="dropdown">
                <button class="btn btn-light position-relative border-0 bg-transparent" data-bs-toggle="dropdown" style="font-size: 1.2rem;">
                    <i class="bi bi-bell-fill text-secondary"></i>
                    @if($user->unreadNotifications->count() > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; margin-top: 8px; margin-left: -10px;">
                            {{ $user->unreadNotifications->count() }}
                        </span>
                    @endif
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="width: 320px; max-height: 400px; overflow-y: auto;">
                    <li class="px-3 py-2 border-bottom bg-light d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Notifications</span>
                        @if($user->unreadNotifications->count() > 0)
                            <form action="{{ route('notifications.markAllRead') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-link btn-sm text-decoration-none text-primary p-0" style="font-size: 0.75rem;" title="Mark all as read">
                                    <i class="bi bi-check2-all"></i> Mark all read
                                </button>
                            </form>
                        @endif
                    </li>
                    @forelse($user->notifications()->latest()->get() as $notification)
                        <li>
                            @php
                                $employeeId = $notification->data['employee_id'] ?? null;
                                // For backward compatibility with old notifications
                                if (!$employeeId && isset($notification->data['user_id'])) {
                                    $employee = \App\Models\Employee::where('user_id', $notification->data['user_id'])->first();
                                    $employeeId = $employee ? $employee->id : null;
                                }
                                $isUnread = is_null($notification->read_at);
                            @endphp
                            @if($employeeId && isset($notification->data['url']) && str_contains($notification->data['url'], 'super/users'))
                                <a class="dropdown-item py-2 text-wrap border-bottom notification-link {{ $isUnread ? 'bg-light' : '' }}"
                                   href="#"
                                   data-notification-id="{{ $notification->id }}"
                                   data-employee-id="{{ $employeeId }}"
                                   data-is-user-notification="true">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="small fw-bold text-primary">{{ $notification->data['user_name'] }}</div>
                                        <div class="text-muted" style="font-size: 0.65rem;">{{ $notification->created_at->diffForHumans(null, true, true) }}</div>
                                    </div>
                                    <div class="small text-muted mt-1" style="font-size: 0.8rem;">{{ $notification->data['message'] }}</div>
                                    @if($isUnread)
                                        <div class="small text-primary mt-1" style="font-size: 0.7rem;">
                                            <i class="bi bi-circle-fill"></i> New
                                        </div>
                                    @endif
                                </a>
                            @else
                                <a class="dropdown-item py-2 text-wrap border-bottom {{ $isUnread ? 'bg-light' : '' }}" href="{{ route('notifications.read', $notification->id) }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        @if(isset($notification->data['applicant_name']))
                                            <div class="small fw-bold text-primary">{{ $notification->data['applicant_name'] }} ({{ $notification->data['leave_type'] }})</div>
                                        @elseif(isset($notification->data['user_name']))
                                            <div class="small fw-bold text-primary">{{ $notification->data['user_name'] }}</div>
                                        @else
                                            <div class="small fw-bold text-primary">System Notification</div>
                                        @endif
                                        <div class="text-muted" style="font-size: 0.65rem;">{{ $notification->created_at->diffForHumans(null, true, true) }}</div>
                                    </div>
                                    <div class="small text-muted mt-1" style="font-size: 0.8rem;">{{ $notification->data['message'] }}</div>
                                    @if($isUnread)
                                        <div class="small text-primary mt-1" style="font-size: 0.7rem;">
                                            <i class="bi bi-circle-fill"></i> New
                                        </div>
                                    @endif
                                </a>
                            @endif
                        </li>
                    @empty
                        <li class="px-4 py-4 text-center text-muted small">No notifications.</li>
                    @endforelse
                </ul>
            </div>

            <div class="dropdown">
                <button class="btn btn-sm lais-user-btn dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i> {{ $user->first_name }}
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li class="px-3 py-2">
                        <div class="fw-semibold">{{ $user->name }}</div>
                        <div class="text-muted small">{{ $user->email }}</div>
                    </li>
                    <li><hr class="dropdown-divider"></li>

                    <li>
                        <a class="dropdown-item" href="{{ route('employee.profile.show') }}">
                            <i class="bi bi-person-badge me-2"></i> My Profile
                        </a>
                    </li>

                    @if($user->hasAnyRole(['approver_chief_personnel', 'approver_ard_ms']))
                    <li>
                        <a class="dropdown-item" href="{{ route('approver.google2fa.setup') }}">
                            <i class="bi bi-shield-lock me-2"></i> Google Authenticator
                        </a>
                    </li>
                    @endif

                    <li><hr class="dropdown-divider"></li>

                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="offcanvas offcanvas-start lais-offcanvas" tabindex="-1" id="laisSidebar">
  <div class="offcanvas-header">
    <div class="d-flex align-items-center gap-2">
      <img src="{{ asset('images/denr_logo.png') }}"
           alt="DENR Logo"
           style="height: 40px; width: auto;">
      <div>
        <div class="fw-bold">LATS</div>
      </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    @include('layouts.partials.sidebar')
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle user notification clicks to open modal instead of redirecting
    document.querySelectorAll('.notification-link[data-is-user-notification="true"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            const notificationId = this.getAttribute('data-notification-id');
            const employeeId = this.getAttribute('data-employee-id');

            // Mark notification as read
            fetch(`/notifications/${notificationId}/mark-read`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                // Notification marked as read successfully
                // Keep the notification visible but mark it as read (no removal)
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });

            // Check if we're on the users index page and open the modal
            if (typeof openViewUserModal === 'function' && employeeId) {
                openViewUserModal(employeeId);
            } else {
                // If not on users index page or no employee ID, redirect to the user show page
                window.location.href = `/super/users/${employeeId}`;
            }
        });
    });
});
</script>
