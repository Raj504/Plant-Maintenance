<header class="pm-header">
    <button class="btn btn-light d-lg-none" id="sidebarToggle" type="button" aria-label="Menu"><i class="bi bi-list"></i></button>

    <form action="{{ route('assets.index') }}" method="GET" class="header-search d-none d-md-flex">
        <i class="bi bi-search"></i>
        <input type="text" name="q" class="form-control" placeholder="Search asset code or name…" value="{{ request('q') }}">
    </form>

    <div class="ms-auto d-flex align-items-center gap-2">
        @can('pm.requests.create')
        <a href="{{ route('requests.create') }}" class="btn btn-primary btn-sm d-none d-sm-inline-flex"><i class="bi bi-plus-lg me-1"></i> Raise Request</a>
        @endcan

        {{-- Notifications --}}
        <div class="dropdown">
            <button class="btn btn-light position-relative" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                <i class="bi bi-bell"></i>
                @if($unreadCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                @endif
            </button>
            <div class="dropdown-menu dropdown-menu-end notification-menu shadow">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <strong>Notifications</strong>
                    @if($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-link btn-sm p-0">Mark all read</button></form>
                    @endif
                </div>
                <div class="notification-list">
                    @forelse($headerNotifications as $n)
                    <a href="{{ route('notifications.read', $n) }}" onclick="event.preventDefault(); document.getElementById('notif-{{ $n->id }}').submit();" class="dropdown-item notification-item">
                        <span class="dot bg-{{ $n->level }}"></span>
                        <div>
                            <div class="fw-semibold small">{{ $n->title }}</div>
                            <div class="text-muted small text-truncate">{{ $n->message }}</div>
                            <div class="text-muted" style="font-size:.7rem">{{ $n->created_at->diffForHumans() }}</div>
                        </div>
                    </a>
                    <form id="notif-{{ $n->id }}" method="POST" action="{{ route('notifications.read', $n) }}" class="d-none">@csrf</form>
                    @empty
                    <div class="text-center text-muted py-4 small">No new notifications</div>
                    @endforelse
                </div>
                <a href="{{ route('notifications.index') }}" class="dropdown-item text-center small border-top py-2">View all</a>
            </div>
        </div>

        {{-- User --}}
        <div class="dropdown">
            <button class="btn btn-light d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                @if(auth()->user()->avatar)
                <img src="{{ asset('storage/' . auth()->user()->avatar) }}" class="avatar" alt="">
                @else
                <span class="avatar avatar-text">{{ auth()->user()->initials }}</span>
                @endif
                <span class="d-none d-md-inline text-start lh-1">
                    <span class="d-block small fw-semibold">{{ auth()->user()->name }}</span>
                    <span class="d-block text-muted" style="font-size:.7rem">{{ auth()->user()->roles->first()?->name ?? 'User' }}</span>
                </span>
                <i class="bi bi-chevron-down small"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>My Profile</a></li>
                <li><a class="dropdown-item" href="{{ route('notifications.index') }}"><i class="bi bi-bell me-2"></i>Notifications</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
