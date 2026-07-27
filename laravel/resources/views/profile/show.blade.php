<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/forum-favicon.png') }}">
    <title>{{ $user->name }} — Profile</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; min-height: 100vh; }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 12px 20px; color: white;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .navbar h1 { font-size: 18px; display: flex; align-items: center; gap: 8px; }
        .btn-back { background: rgba(255,255,255,0.2); padding: 6px 14px; border: 1px solid white; border-radius: 6px; color: white; cursor: pointer; text-decoration: none; font-size: 13px; }
        .container { max-width: 600px; margin: 40px auto; padding: 0 16px; }
        .card {
            background: white; border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .card-banner {
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-body { padding: 0 28px 28px; }
        .avatar-wrap {
            margin-top: -44px; margin-bottom: 14px;
        }
        .avatar {
            width: 88px; height: 88px; border-radius: 50%;
            border: 4px solid white;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; font-weight: 800; color: white;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .user-name { font-size: 22px; font-weight: 800; color: #1a202c; margin-bottom: 4px; }
        .user-role {
            display: inline-block; font-size: 11px; font-weight: 700;
            padding: 3px 10px; border-radius: 20px; text-transform: uppercase;
            letter-spacing: 0.5px; margin-bottom: 12px;
        }
        .role-member   { background: #e0e7ff; color: #3730a3; }
        .role-lecturer { background: #d1fae5; color: #065f46; }
        .role-admin    { background: #fee2e2; color: #991b1b; }
        .user-email { font-size: 13px; color: #718096; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
        .user-bio { font-size: 14px; color: #4a5568; line-height: 1.6; padding: 14px; background: #f7fafc; border-radius: 10px; margin-bottom: 18px; }
        .user-bio.empty { color: #a0aec0; font-style: italic; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 18px 0; }
        .stats-row { display: flex; gap: 24px; }
        .stat { text-align: center; }
        .stat-val { font-size: 20px; font-weight: 800; color: #2d3748; }
        .stat-lbl { font-size: 11px; color: #a0aec0; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
        .actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn-msg {
            flex: 1; padding: 10px; background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; border: none; border-radius: 10px; font-size: 14px;
            font-weight: 600; cursor: pointer; text-decoration: none;
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-msg:hover { opacity: 0.9; }
        .btn-edit {
            flex: 1; padding: 10px; background: #f1f5f9;
            color: #475569; border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none;
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-edit:hover { background: #e2e8f0; }
    </style>
</head>
<body>
<nav class="navbar">
    <h1><img src="{{ asset('images/forum.png') }}" alt="" style="height:28px;"> Discussion Hub</h1>
    <a href="javascript:history.back()" class="btn-back">← Back</a>
</nav>

<div class="container">
    <div class="card">
        <div class="card-banner"></div>
        <div class="card-body">
            <div class="avatar-wrap">
                <div class="avatar">
                    @if($user->avatar)
                        <img src="{{ storage_url($user->avatar) }}" alt="{{ $user->name }}">
                    @else
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    @endif
                </div>
            </div>
            <div class="user-name">{{ $user->name }}</div>
            <span class="user-role role-{{ $user->role }}">{{ ucfirst($user->role) }}</span>
            <div class="user-email">✉️ {{ $user->email }}</div>
            <div class="user-bio {{ $user->bio ? '' : 'empty' }}">
                {{ $user->bio ?: 'No bio yet.' }}
            </div>
            <hr class="divider">
            <div class="stats-row">
                <div class="stat">
                    <div class="stat-val">{{ $user->posts()->count() }}</div>
                    <div class="stat-lbl">Posts</div>
                </div>
                <div class="stat">
                    <div class="stat-val">{{ $user->topics()->count() }}</div>
                    <div class="stat-lbl">Topics</div>
                </div>
                <div class="stat">
                    <div class="stat-val">{{ $user->created_at->format('M Y') }}</div>
                    <div class="stat-lbl">Joined</div>
                </div>
            </div>
            <div class="actions">
                @if($user->id === auth()->id())
                    <a href="{{ route('profile.edit') }}" class="btn-edit">✏️ Edit Profile</a>
                @else
                    <a href="{{ route('messages.show', $user->id) }}" class="btn-msg">💬 Send Message</a>
                @endif
            </div>
        </div>
    </div>
</div>
</body>
</html>
