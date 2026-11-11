<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        return view('admin.masters.shifts', ['shifts' => Shift::orderBy('start_time')->get(), 'shift' => new Shift()]);
    }

    public function create()
    {
        return redirect()->route('admin.shifts.index');
    }

    public function store(Request $request)
    {
        Shift::create($this->validated($request));
        flash_toast('success', 'Shift added.');
        return back();
    }

    public function edit(Shift $shift)
    {
        return view('admin.masters.shifts', ['shifts' => Shift::orderBy('start_time')->get(), 'shift' => $shift]);
    }

    public function update(Request $request, Shift $shift)
    {
        $shift->update($this->validated($request, $shift));
        flash_toast('success', 'Shift updated.');
        return redirect()->route('admin.shifts.index');
    }

    public function destroy(Shift $shift)
    {
        $shift->update(['is_active' => false]);
        flash_toast('success', 'Shift deactivated.');
        return back();
    }

    protected function validated(Request $request, ?Shift $shift = null): array
    {
        $data = $request->validate(['name' => 'required|string|max:50|unique:pm_shifts,name,' . ($shift?->id ?? 'NULL'), 'start_time' => 'required|date_format:H:i', 'end_time' => 'required|date_format:H:i']);
        $data['is_active'] = $request->boolean('is_active', true);
        return $data;
    }
}
