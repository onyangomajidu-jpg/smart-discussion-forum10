@extends('layouts.app')

@section('title', 'Edit Profile — Discussion Hub')

@push('styles')
<style>
    .profile-page { display: grid; grid-template-columns: 280px 1fr; gap: 24px; align-items: start; max-width: 900px; }
    @media (max-width: 720px) { .profile-page { grid-template-columns: 1fr; } }

    .profile-card { background: #fff; border: 1px solid var(--border); border-radius: 14px; overflow: hidden; text-align: center; }
    .profile-card-banner { height: 72px; background: var(--grad); }
    .profile-card-body { padding: 0 20px 24px; }
    .profile-avatar-wrap { margin-top: -44px; margin-bottom: 14px; display: flex; justify-content: center; }
    .profile-avatar {
        width: 88px; height: 88px; border-radius: 50%;
        border: 4px solid #fff; background: var(--grad);
        display: flex; align-items: center; justify-content: center;
        font-size: 34px; font-weight: 800; color: #fff;
        box-shadow: 0 4px 16px rgba(99,102,241,.25);
        overflow: hidden; flex-shrink: 0; cursor: pointer;
        position: relative;
    }
    .profile-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; }
    .profile-avatar-overlay {
        position: absolute; inset: 0; border-radius: 50%;
        background: rgba(0,0,0,.45); display: flex; align-items: center;
        justify-content: center; opacity: 0; transition: opacity .2s;
        font-size: 20px; color: #fff;
    }
    .profile-avatar:hover .profile-avatar-overlay { opacity: 1; }

    .profile-name { font-size: 16px; font-weight: 800; color: var(--text); }
    .profile-role {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 11px; font-weight: 600; padding: 3px 10px;
        border-radius: 20px; margin: 6px 0 14px;
        background: #e0e7ff; color: var(--primary);
    }
    .profile-meta { text-align: left; border-top: 1px solid var(--border); padding-top: 14px; }
    .profile-meta-row { display: flex; align-items: flex-start; gap: 10px; font-size: 12px; padding: 7px 0; border-bottom: 1px solid #f1f5f9; }
    .profile-meta-row:last-child { border-bottom: none; }
    .pm-icon { color: var(--primary); width: 16px; text-align: center; flex-shrink: 0; margin-top: 1px; }
    .pm-label { color: var(--muted); font-weight: 600; min-width: 64px; flex-shrink: 0; }
    .pm-val { color: var(--text); font-weight: 500; word-break: break-all; }

    .section-divider { border: none; border-top: 1px solid var(--border); margin: 22px 0; }
    .avatar-hint { font-size: 11px; color: var(--muted); margin-top: 8px; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1><i class="fa-solid fa-user-pen"></i> Edit Profile</h1>
    <p>Update your personal information and account settings.</p>
</div>

{{-- ONE form wraps BOTH cards so the file input is unambiguously inside it --}}
<form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- Hidden file input — inside the form, triggered by clicking the avatar --}}
    <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display:none">

    <div class="profile-page">

        {{-- ── Left: Profile card ── --}}
        <div class="profile-card">
            <div class="profile-card-banner"></div>
            <div class="profile-card-body">

                {{-- Clicking the avatar opens the file picker --}}
                <div class="profile-avatar-wrap">
                    <div class="profile-avatar" onclick="document.getElementById('avatarInput').click()" title="Click to change photo">
                        @if($user->avatar)
                            <img id="avatarPreview" src="{{ storage_url($user->avatar) }}" alt="Avatar">
                        @else
                            <img id="avatarPreview" src="" alt="" style="display:none">
                            <span id="avatarInitial">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        @endif
                        <div class="profile-avatar-overlay"><i class="fa-solid fa-camera"></i></div>
                    </div>
                </div>

                <div class="profile-name">{{ $user->name }}</div>
                <div class="profile-role">
                    @if($user->isLecturer()) <i class="fa-solid fa-chalkboard-user"></i> Lecturer
                    @elseif($user->isMember()) <i class="fa-solid fa-user-graduate"></i> Student
                    @else <i class="fa-solid fa-shield-halved"></i> Admin
                    @endif
                </div>

                <div style="margin-top:10px">
                    <button type="button" onclick="document.getElementById('avatarInput').click()" class="btn btn-outline btn-sm">
                        <i class="fa-solid fa-camera"></i> Change Photo
                    </button>
                </div>
                <div class="avatar-hint" id="avatarHint" style="margin-top:6px"></div>
                @error('avatar')<div style="font-size:12px;color:var(--danger);margin-top:4px">{{ $message }}</div>@enderror

                <div class="profile-meta">
                    <div class="profile-meta-row">
                        <i class="fa-solid fa-envelope pm-icon"></i>
                        <span class="pm-label">Email</span>
                        <span class="pm-val">{{ $user->email }}</span>
                    </div>
                    <div class="profile-meta-row">
                        <i class="fa-solid fa-id-badge pm-icon"></i>
                        <span class="pm-label">Role</span>
                        <span class="pm-val">{{ ucfirst($user->role) }}</span>
                    </div>
                    <div class="profile-meta-row">
                        <i class="fa-solid fa-calendar-plus pm-icon"></i>
                        <span class="pm-label">Joined</span>
                        <span class="pm-val">{{ $user->created_at->format('d M Y') }}</span>
                    </div>
                    <div class="profile-meta-row">
                        <i class="fa-solid fa-clock pm-icon"></i>
                        <span class="pm-label">Member for</span>
                        <span class="pm-val">{{ $user->created_at->diffForHumans() }}</span>
                    </div>
                    @if($user->bio)
                    <div class="profile-meta-row">
                        <i class="fa-solid fa-quote-left pm-icon"></i>
                        <span class="pm-label">Bio</span>
                        <span class="pm-val">{{ $user->bio }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Right: Fields ── --}}
        <div class="card">
            <div class="card-header">
                <h2><i class="fa-solid fa-pen-to-square"></i> Account Details</h2>
            </div>
            <div class="card-body">

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

                <hr class="section-divider">

                <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:14px">
                    <i class="fa-solid fa-lock"></i> Change Password
                    <span style="font-weight:400;color:var(--muted);font-size:12px"> — leave blank to keep current</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password"
                           class="form-control @error('current_password') is-invalid @enderror"
                           autocomplete="current-password">
                    @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               autocomplete="new-password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation"
                               class="form-control" autocomplete="new-password">
                    </div>
                </div>

                <div style="display:flex;gap:10px;margin-top:8px">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Save Changes
                    </button>
                    <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancel</a>
                </div>

            </div>
        </div>

    </div>{{-- end .profile-page --}}
</form>

<script>
document.getElementById('avatarInput').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('avatarPreview');
        const initial = document.getElementById('avatarInitial');
        preview.src = e.target.result;
        preview.style.display = 'block';
        if (initial) initial.style.display = 'none';
        document.getElementById('avatarHint').textContent = '✓ ' + file.name + ' — click Save Changes to apply';
        document.getElementById('avatarHint').style.color = '#10b981';
    };
    reader.readAsDataURL(file);
});
</script>
@endsection
