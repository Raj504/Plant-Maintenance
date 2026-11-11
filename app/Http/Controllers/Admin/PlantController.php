<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    public function index()
    {
        return view('admin.masters.plants', ['plants' => Plant::withCount('departments')->orderBy('name')->get(), 'plant' => new Plant()]);
    }

    public function create()
    {
        return redirect()->route('admin.plants.index');
    }

    public function store(Request $request)
    {
        Plant::create($this->validated($request));
        flash_toast('success', 'Plant added.');
        return back();
    }

    public function edit(Plant $plant)
    {
        return view('admin.masters.plants', ['plants' => Plant::withCount('departments')->orderBy('name')->get(), 'plant' => $plant]);
    }

    public function update(Request $request, Plant $plant)
    {
        $plant->update($this->validated($request, $plant));
        flash_toast('success', 'Plant updated.');
        return redirect()->route('admin.plants.index');
    }

    public function destroy(Plant $plant)
    {
        if ($plant->departments()->exists() || $plant->assets()->exists()) {
            flash_toast('error', 'Plant is in use.');
            return back();
        }
        $plant->delete();
        flash_toast('success', 'Plant deleted.');
        return back();
    }

    protected function validated(Request $request, ?Plant $plant = null): array
    {
        $data = $request->validate(['code' => 'required|string|max:30|unique:pm_plants,code,' . ($plant?->id ?? 'NULL'), 'name' => 'required|string|max:150', 'location' => 'nullable|string|max:255']);
        $data['is_active'] = $request->boolean('is_active', true);
        return $data;
    }
}
