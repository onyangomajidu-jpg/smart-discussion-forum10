<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'bio'                   => ['nullable', 'string', 'max:500'],
            'avatar'                => ['nullable', 'image', 'max:2048'],
            'current_password'      => ['nullable', 'string'],
            'password'              => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($request->filled('current_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
            }
        }

        $user->name  = $data['name'];
        $user->email = $data['email'];
        $user->bio   = $data['bio'] ?? null;

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return back()->with('success', 'Profile updated successfully.');
    }
}
