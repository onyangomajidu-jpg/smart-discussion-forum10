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

    public function apiShow(Request $request)
    {
        $user = $request->user();
        return response()->json(['user' => [
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'bio'    => $user->bio,
            'avatar' => $user->avatar,
            'role'   => $user->role,
        ]]);
    }

    public function apiUpdate(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'bio'              => ['nullable', 'string', 'max:500'],
            'current_password' => ['nullable', 'string'],
            'password'         => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);
        if ($request->filled('current_password')) {
            if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Current password is incorrect.'], 422);
            }
        }
        $user->name = $data['name'];
        $user->bio  = $data['bio'] ?? null;
        if ($request->filled('password')) {
            $user->password = \Illuminate\Support\Facades\Hash::make($data['password']);
        }
        $user->save();
        return response()->json(['message' => 'Profile updated.', 'user' => [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'bio' => $user->bio, 'role' => $user->role,
        ]]);
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
