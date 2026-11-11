<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return $this->page(new Department());
    }

    public function create()
    {
        return redirect()->route('admin.departments.index');
    }

    public function store(Request $request)
    {
        Department::create($this->validated($request));
        flash_toast('success', 'Department added.');
        return back();
    }

    public function edit(Department $department)
    {
        return $this->page($department);
    }

    public function update(Request $request, Department $department)
    {
        $department->update($this->validated($request, $department));
        flash_toast('success', 'Department updated.');
        return redirect()->route('admin.departments.index');
    }

    public function destroy(Department $department)
    {
        if ($department->assets()->exists() || $department->users()->exists()) {
            flash_toast('error', 'Department is in use.');
            return back();
        }
        $department->delete();
        flash_toast('success', 'Department deleted.');
        return back();
    }

    protected function page(Department $department)
    {
        return view('admin.masters.departments', [
            'departments' => Department::with(['plant', 'head'])->withCount('assets')->orderBy('name')->get(),
            'department'  => $department,
            'plants'      => Plant::orderBy('name')->get(),
            'users'       => User::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    protected function validated(Request $request, ?Department $department = null): array
    {
        $data = $request->validate(['code' => 'required|string|max:30|unique:pm_departments,code,' . ($department?->id ?? 'NULL'), 'name' => 'required|string|max:150', 'plant_id' => 'nullable|exists:pm_plants,id', 'head_user_id' => 'nullable|exists:users,id']);
        $data['is_active'] = $request->boolean('is_active', true);
        return $data;
    }
}
