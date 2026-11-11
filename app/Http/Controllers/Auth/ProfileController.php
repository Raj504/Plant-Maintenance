<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('auth.profile', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone'    => 'nullable|string|max:30',
            'password' => 'nullable|string|min:6|confirmed',
            'avatar'   => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('pm/avatars', 'public');
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);
        flash_toast('success', 'Profile updated.');
        return back();
    }
}
