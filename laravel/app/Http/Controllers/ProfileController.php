<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function show(\App\Models\User $user)
    {
        return view('profile.show', ['user' => $user]);
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

    public function apiUploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ]);
        $user = $request->user();
        if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->avatar = (new \App\Services\CloudinaryUploader)->upload($request->file('avatar'), 'avatars');
        $user->save();
        return response()->json(['message' => 'Avatar updated.', 'avatar' => $user->avatar]);
    }

    public function apiUpdate(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'bio'              => ['nullable', 'string', 'max:500'],
            'avatar'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
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
        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = (new \App\Services\CloudinaryUploader)->upload($request->file('avatar'), 'avatars');
        }
        $user->save();
        return response()->json(['message' => 'Profile updated.', 'user' => [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'bio' => $user->bio, 'avatar' => $user->avatar, 'role' => $user->role,
        ]]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'bio'              => ['nullable', 'string', 'max:500'],
            'avatar'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            'current_password' => ['nullable', 'string'],
            'password'         => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($request->filled('password')) {
            if (!$request->filled('current_password')) {
                return back()->withErrors(['current_password' => 'Enter your current password to set a new one.'])->withInput();
            }
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
            }
        }

        $updates = [
            'name'  => $request->input('name'),
            'email' => $request->input('email'),
            'bio'   => $request->input('bio'),
        ];

        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                Storage::disk('public')->delete($user->avatar);
            }
            $updates['avatar'] = (new \App\Services\CloudinaryUploader)->upload($request->file('avatar'), 'avatars');
        }

        if ($request->filled('password')) {
            $updates['password'] = Hash::make($request->input('password'));
        }

        \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update($updates);

        // Refresh the auth guard so the session reflects the new values
        auth()->setUser(\App\Models\User::find($user->id));

        return back()->with('success', 'Profile updated successfully.');
    }
}
