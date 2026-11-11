<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Configurable daily log forms. Each log type has a list of extra fields
 * (key, label, unit, type, options) stored as JSON and rendered dynamically.
 */
class LogTypeController extends Controller
{
    public function index()
    {
        return view('admin.masters.log-types.index', ['logTypes' => LogType::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.masters.log-types.form', ['logType' => new LogType(['has_meter_readings' => true, 'is_active' => true, 'fields' => []])]);
    }

    public function store(Request $request)
    {
        LogType::create($this->validated($request));
        flash_toast('success', 'Log type created.');
        return redirect()->route('admin.log-types.index');
    }

    public function edit(LogType $logType)
    {
        return view('admin.masters.log-types.form', compact('logType'));
    }

    public function update(Request $request, LogType $logType)
    {
        $logType->update($this->validated($request, $logType));
        flash_toast('success', 'Log type updated.');
        return redirect()->route('admin.log-types.index');
    }

    public function destroy(LogType $logType)
    {
        $logType->update(['is_active' => false]);
        flash_toast('success', 'Log type deactivated.');
        return back();
    }

    protected function validated(Request $request, ?LogType $logType = null): array
    {
        $data = $request->validate([
            'code'               => 'required|string|max:30|alpha_dash|unique:pm_log_types,code,' . ($logType?->id ?? 'NULL'),
            'name'               => 'required|string|max:100',
            'description'        => 'nullable|string|max:255',
            'fields'             => 'nullable|array',
            'fields.*.label'     => 'nullable|string|max:60',
            'fields.*.unit'      => 'nullable|string|max:20',
            'fields.*.type'      => 'nullable|in:number,text,select',
            'fields.*.options'   => 'nullable|string|max:255',
        ]);
        $data['has_meter_readings'] = $request->boolean('has_meter_readings');
        $data['is_active'] = $request->boolean('is_active', true);

        // Build clean field definitions; the key is generated from the label
        $fields = [];
        foreach ($data['fields'] ?? [] as $f) {
            if (empty($f['label'])) {
                continue;
            }
            $fields[] = ['key' => Str::slug($f['label'], '_'), 'label' => $f['label'], 'unit' => $f['unit'] ?? '', 'type' => $f['type'] ?? 'number', 'options' => $f['options'] ?? ''];
        }
        $data['fields'] = $fields;
        return $data;
    }
}
