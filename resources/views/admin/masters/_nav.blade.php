<ul class="nav nav-pills mb-3 flex-wrap gap-1">
    <li class="nav-item"><a class="nav-link {{ is_active_route('admin.departments.*') }}" href="{{ route('admin.departments.index') }}">Departments</a></li>
    <li class="nav-item"><a class="nav-link {{ is_active_route('admin.plants.*') }}" href="{{ route('admin.plants.index') }}">Plants</a></li>
    <li class="nav-item"><a class="nav-link {{ is_active_route('admin.categories.*') }}" href="{{ route('admin.categories.index') }}">Categories</a></li>
    <li class="nav-item"><a class="nav-link {{ is_active_route('admin.shifts.*') }}" href="{{ route('admin.shifts.index') }}">Shifts</a></li>
    <li class="nav-item"><a class="nav-link {{ is_active_route('admin.teams.*') }}" href="{{ route('admin.teams.index') }}">Teams</a></li>
    <li class="nav-item"><a class="nav-link {{ is_active_route('admin.log-types.*') }}" href="{{ route('admin.log-types.index') }}">Log Types</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('vendors.index') }}">Vendors</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('pm.checklists.index') }}">PM Checklists</a></li>
</ul>
