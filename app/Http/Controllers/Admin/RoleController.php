<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        return view('admin.roles.index', ['roles' => Role::withCount(['users', 'permissions'])->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.roles.form', ['role' => new Role(), 'permissions' => Permission::orderBy('module')->orderBy('name')->get()->groupBy('module'), 'selected' => []]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100|unique:pm_roles,name', 'description' => 'nullable|string|max:255', 'permissions' => 'nullable|array', 'permissions.*' => 'exists:pm_permissions,id']);
        $role = Role::create(['name' => $data['name'], 'slug' => Str::slug($data['name']), 'description' => $data['description'] ?? null]);
        $role->permissions()->sync($data['permissions'] ?? []);
        flash_toast('success', 'Role created.');
        return redirect()->route('admin.roles.index');
    }

    public function edit(Role $role)
    {
        return view('admin.roles.form', ['role' => $role, 'permissions' => Permission::orderBy('module')->orderBy('name')->get()->groupBy('module'), 'selected' => $role->permissions->pluck('id')->all()]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate(['name' => 'required|string|max:100|unique:pm_roles,name,' . $role->id, 'description' => 'nullable|string|max:255', 'permissions' => 'nullable|array', 'permissions.*' => 'exists:pm_permissions,id']);
        $role->update(['name' => $data['name'], 'description' => $data['description'] ?? null]);
        if ($role->slug !== 'super-admin') {
            $role->permissions()->sync($data['permissions'] ?? []);
        }
        flash_toast('success', 'Role updated. Changes apply on the users\' next page load.');
        return redirect()->route('admin.roles.index');
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            flash_toast('error', 'System roles cannot be deleted.');
            return back();
        }
        $role->delete();
        flash_toast('success', 'Role deleted.');
        return back();
    }
}
