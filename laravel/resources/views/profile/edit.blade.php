@extends('layouts.app')

@section('title', 'Edit Profile — Smart Discussion Forum')

@push('styles')
<style>
    .profile-avatar-wrap {
        display: flex; align-items: center; gap: 20px; margin-bottom: 24px;
    }
    .profile-avatar-img {
        width: 80px; height: 80px; border-radius: 16px; object-fit: cover;
        border: 3px solid var(--border);
    }
    .profile-avatar-placeholder {
        width: 80px; height: 80px; border-radius: 16px;
        background: var(--grad); color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 32px; font-weight: 800; flex-shrink: 0;
    }
    .section-divider { border: none; border-top: 1px solid var(--border); margin: 24px 0; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1><i class="fa-solid fa-user-pen"></i> Edit Profile</h1>
    <p>Update your personal information and account settings.</p>
</div>

<div class="card" style="max-width:640px">
    <div class="card-header">
        <h2><i class="fa-solid fa-circle-user"></i> Profile Information</h2>
    </div>
    <div class="card-body">

        {{-- Avatar preview --}}
        <div class="profile-avatar-wrap">
            @if($user->avatar)
                <img src="{{ asset('storage/' . $user->avatar) }}" alt="Avatar" class="profile-avatar-img">
            @else
                <div class="profile-avatar-placeholder">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
            @endif
            <div>
                <div style="font-size:14px;font-weight:700;color:var(--text)">{{ $user->name }}</div>
                <div style="font-size:12px;color:var(--muted);margin-top:3px">{{ ucfirst($user->role) }}</div>
                <div style="font-size:12px;color:var(--muted)">{{ $user->email }}</div>
            </div>
        </div>

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Basic info --}}
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Bio <span style="font-weight:400;color:var(--muted)">(optional)</span></label>
                <textarea name="bio" rows="3" class="form-control @error('bio') is-invalid @enderror"
                          placeholder="Tell others a little about yourself…">{{ old('bio', $user->bio) }}</textarea>
                @error('bio')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Profile Picture <span style="font-weight:400;color:var(--muted)">(optional, max 2 MB)</span></label>
                <input type="file" name="avatar" accept="image/*" class="form-control @error('avatar') is-invalid @enderror">
                @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <hr class="section-divider">

            {{-- Password change --}}
            <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:14px">
                <i class="fa-solid fa-lock"></i> Change Password
                <span style="font-weight:400;color:var(--muted);font-size:12px"> — leave blank to keep current password</span>
            </div>

            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror"
                       autocomplete="current-password">
                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                           autocomplete="new-password">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:8px">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
                <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>

    </div>
</div>
@endsection
