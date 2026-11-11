<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['roles', 'department'])
            ->when($request->q, fn ($q, $v) => $q->where(fn ($s) => $s->where('name', 'like', "%$v%")->orWhere('email', 'like', "%$v%")->orWhere('employee_code', 'like', "%$v%")))
            ->when($request->role_id, fn ($q, $v) => $q->whereHas('roles', fn ($r) => $r->where('pm_roles.id', $v)))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.users.index', ['users' => $users, 'roles' => Role::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User(['is_active' => true])] + $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $user = User::create($data);
        $user->roles()->sync($request->input('roles', []));
        $user->teams()->sync($request->input('teams', []));
        flash_toast('success', 'User created.');
        return redirect()->route('admin.users.index');
    }

    public function edit(User $user)
    {
        $user->load(['roles', 'teams']);
        return view('admin.users.form', compact('user') + $this->formData());
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);
        $user->roles()->sync($request->input('roles', []));
        $user->teams()->sync($request->input('teams', []));
        flash_toast('success', 'User updated.');
        return redirect()->route('admin.users.index');
    }

    public function toggle(User $user)
    {
        if ($user->id === auth()->id()) {
            flash_toast('error', 'You cannot deactivate yourself.');
            return back();
        }
        $user->update(['is_active' => !$user->is_active]);
        flash_toast('success', 'User ' . ($user->is_active ? 'activated' : 'deactivated') . '.');
        return back();
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            flash_toast('error', 'You cannot delete yourself.');
            return back();
        }
        // Users are referenced everywhere (history, approvals). Deactivate instead of deleting.
        $user->update(['is_active' => false]);
        flash_toast('success', 'User deactivated (records are kept for audit).');
        return back();
    }

    protected function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'employee_code' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user?->id)],
            'name'          => 'required|string|max:100',
            'email'         => ['required', 'email', Rule::unique('users')->ignore($user?->id)],
            'password'      => [$user ? 'nullable' : 'required', 'string', 'min:6'],
            'department_id' => 'nullable|exists:pm_departments,id',
            'designation'   => 'nullable|string|max:100',
            'phone'         => 'nullable|string|max:30',
            'roles'         => 'required|array|min:1',
            'roles.*'       => 'exists:pm_roles,id',
            'teams'         => 'nullable|array',
            'teams.*'       => 'exists:pm_teams,id',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        unset($data['roles'], $data['teams']);
        return $data;
    }

    protected function formData(): array
    {
        return ['roles' => Role::orderBy('name')->get(), 'departments' => Department::orderBy('name')->get(), 'teams' => Team::orderBy('name')->get()];
    }
}
