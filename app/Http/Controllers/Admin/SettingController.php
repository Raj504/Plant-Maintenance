<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings', ['settings' => Setting::orderBy('group')->orderBy('id')->get()->groupBy('group')]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['settings' => 'required|array', 'settings.*' => 'nullable|string|max:500']);
        foreach ($data['settings'] as $key => $value) {
            Setting::set($key, $value);
        }
        flash_toast('success', 'Settings saved.');
        return back();
    }
}
