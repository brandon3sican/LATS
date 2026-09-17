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

        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
            <img src="{{ asset('images/denr_logo.png') }}"
                 alt="DENR Logo"
                 style="height: 40px; width: auto;">

            <div class="d-flex flex-column" style="line-height: 1;">
                <span class="fw-bold" style="font-size: 1.1rem;">DENR</span>
            </div>
            <span class="fw-bold badge text-bg-light border" style="font-size: 1rem;">
                {{-- Shows only on Medium screens and larger (Laptops/Desktops) --}}
                <span class="d-none d-md-inline">Leave Application Tracking System</span>

                {{-- Shows only on Small screens (Mobile Phones) --}}
                <span class="d-inline d-md-none">LATS</span>
            </span>
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
                        @php
                            $isUnread = is_null($notification->read_at);
                        @endphp
                        <li>
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
