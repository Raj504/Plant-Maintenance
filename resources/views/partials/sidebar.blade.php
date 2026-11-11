{{-- Left navigation. Links are shown only when the user holds the permission;
     the real check is on the route (permission middleware). --}}
<aside class="pm-sidebar" id="pmSidebar">
    <a href="{{ route('dashboard') }}" class="sidebar-brand">
        <span class="brand-icon"><i class="bi bi-gear-wide-connected"></i></span>
        <span class="brand-text">
            <strong>PlantCare</strong>
            <small>Maintenance &amp; Ops</small>
        </span>
    </a>

    <nav class="sidebar-nav">
        @can('pm.dashboard.view')
        <a href="{{ route('dashboard') }}" class="nav-link {{ is_active_route('dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
        @endcan

        @can('pm.assets.view')
        <div class="nav-section">Assets</div>
        <a href="{{ route('assets.index') }}" class="nav-link {{ is_active_route(['assets.index', 'assets.show', 'assets.create', 'assets.edit']) }}"><i class="bi bi-cpu"></i> Asset Registry</a>
        <a href="{{ route('assets.tree') }}" class="nav-link {{ is_active_route('assets.tree') }}"><i class="bi bi-diagram-3"></i> Asset Hierarchy</a>
        @endcan

        @canany(['pm.requests.view', 'pm.workorders.view'])
        <div class="nav-section">Maintenance</div>
        @endcanany
        @can('pm.requests.view')
        <a href="{{ route('requests.index') }}" class="nav-link {{ is_active_route('requests.*') }}"><i class="bi bi-exclamation-octagon"></i> Requests / Complaints</a>
        @endcan
        @can('pm.workorders.view')
        <a href="{{ route('work-orders.index') }}" class="nav-link {{ is_active_route('work-orders.*') }}"><i class="bi bi-clipboard-check"></i> Work Orders</a>
        @endcan
        @can('pm.pm.view')
        <a href="{{ route('pm.plans.index') }}" class="nav-link {{ is_active_route('pm.plans.*') }}"><i class="bi bi-calendar-check"></i> PM Plans</a>
        <a href="{{ route('pm.schedules.index') }}" class="nav-link {{ is_active_route(['pm.schedules.*', 'pm.logs.*']) }}"><i class="bi bi-calendar-event"></i> PM Schedules &amp; Logs</a>
        <a href="{{ route('pm.checklists.index') }}" class="nav-link {{ is_active_route('pm.checklists.*') }}"><i class="bi bi-list-check"></i> Checklists</a>
        @endcan
        @can('pm.safety.view')
        <a href="{{ route('permits.index') }}" class="nav-link {{ is_active_route('permits.*') }}"><i class="bi bi-shield-exclamation"></i> Safety – PTW &amp; LOTO</a>
        @endcan

        @can('pm.stock.view')
        <div class="nav-section">Inventory</div>
        <a href="{{ route('spares.index') }}" class="nav-link {{ is_active_route('spares.*') }}"><i class="bi bi-box-seam"></i> Spare Parts</a>
        <a href="{{ route('stock.ledger') }}" class="nav-link {{ is_active_route('stock.ledger') }}"><i class="bi bi-journal-text"></i> Stock Ledger</a>
        @can('pm.stock.issue')
        <a href="{{ route('stock.issue') }}" class="nav-link {{ is_active_route('stock.issue') }}"><i class="bi bi-arrow-left-right"></i> Issue / Return</a>
        @endcan
        @endcan
        @can('pm.purchase.view')
        <a href="{{ route('purchase-requests.index') }}" class="nav-link {{ is_active_route('purchase-requests.*') }}"><i class="bi bi-cart-plus"></i> Purchase Requisitions</a>
        @endcan

        @canany(['pm.logs.view', 'pm.handover.view'])
        <div class="nav-section">Operations</div>
        @endcanany
        @can('pm.logs.view')
        <a href="{{ route('daily-logs.index') }}" class="nav-link {{ is_active_route('daily-logs.*') }}"><i class="bi bi-journal-bookmark"></i> Daily Plant Logs</a>
        @endcan
        @can('pm.handover.view')
        <a href="{{ route('handovers.index') }}" class="nav-link {{ is_active_route('handovers.*') }}"><i class="bi bi-arrow-repeat"></i> Shift Handover</a>
        @endcan

        @canany(['pm.amc.view', 'pm.compliance.view'])
        <div class="nav-section">Contracts &amp; Compliance</div>
        @endcanany
        @can('pm.amc.view')
        <a href="{{ route('amc.index') }}" class="nav-link {{ is_active_route('amc.*') }}"><i class="bi bi-file-earmark-text"></i> AMC Contracts</a>
        <a href="{{ route('vendors.index') }}" class="nav-link {{ is_active_route('vendors.*') }}"><i class="bi bi-building"></i> Vendors</a>
        @endcan
        @can('pm.compliance.view')
        <a href="{{ route('compliances.index') }}" class="nav-link {{ is_active_route('compliances.*') }}"><i class="bi bi-patch-check"></i> Statutory Compliance</a>
        <a href="{{ route('calibrations.index') }}" class="nav-link {{ is_active_route('calibrations.*') }}"><i class="bi bi-rulers"></i> Calibration</a>
        @endcan

        @can('pm.reports.view')
        <div class="nav-section">Analytics</div>
        <a href="{{ route('reports.index') }}" class="nav-link {{ is_active_route('reports.*') }}"><i class="bi bi-bar-chart-line"></i> Reports</a>
        @endcan

        @canany(['pm.users.manage', 'pm.roles.manage', 'pm.settings.manage', 'pm.audit.view'])
        <div class="nav-section">Administration</div>
        @endcanany
        @can('pm.users.manage')
        <a href="{{ route('admin.users.index') }}" class="nav-link {{ is_active_route('admin.users.*') }}"><i class="bi bi-people"></i> Users</a>
        @endcan
        @can('pm.roles.manage')
        <a href="{{ route('admin.roles.index') }}" class="nav-link {{ is_active_route('admin.roles.*') }}"><i class="bi bi-person-badge"></i> Roles &amp; Permissions</a>
        @endcan
        @can('pm.settings.manage')
        <a href="{{ route('admin.departments.index') }}" class="nav-link {{ is_active_route(['admin.plants.*', 'admin.departments.*', 'admin.categories.*', 'admin.shifts.*', 'admin.teams.*', 'admin.log-types.*']) }}"><i class="bi bi-database"></i> Masters</a>
        <a href="{{ route('admin.settings.edit') }}" class="nav-link {{ is_active_route('admin.settings.*') }}"><i class="bi bi-sliders"></i> Settings</a>
        @endcan
        @can('pm.audit.view')
        <a href="{{ route('admin.audit.index') }}" class="nav-link {{ is_active_route('admin.audit.*') }}"><i class="bi bi-clock-history"></i> Audit Trail</a>
        @endcan
    </nav>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
