@extends('layouts.app')

@section('title', 'Admin Dashboard-Discussion Hub')

@push('styles')
<style>
.admin-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 24px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(15,23,42,.4);
}
.admin-hero::before {
    content: '';
    position: absolute; top: -60px; right: -60px;
    width: 200px; height: 200px;
    background: rgba(99,102,241,.15); border-radius: 50%;
}
.admin-hero-title { font-size: 22px; font-weight: 900; margin-bottom: 4px; }
.admin-hero-sub   { font-size: 12px; opacity: .7; }

/* Two-column layout: main area + narrow sidebar */
.dash-grid {
    display: grid;
    grid-template-columns: minmax(0,1fr) 240px;
    gap: 18px;
    align-items: start;
    width: 100%;
    min-width: 0;
}
.dash-grid > * { min-width: 0; }

/* Inactivity settings form fields compact */
.inact-field { margin-bottom: 10px; }
.inact-field label { display:block; font-size:11px; font-weight:700; color:#374151; margin-bottom:4px; }
.inact-field input { width:100%; padding:7px 10px; border:1.5px solid #e2e8f0; border-radius:7px; font-size:12px; font-family:inherit; }
.inact-field input:focus { outline:none; border-color:#6366f1; }
.inact-field small { font-size:10px; color:#94a3b8; }

/* Timeline steps */
.tl-step { display:flex; align-items:center; gap:8px; font-size:11px; padding:5px 0; border-bottom:1px solid #f1f5f9; }
.tl-step:last-child { border-bottom:none; }
.tl-dot { width:20px; height:20px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:9px; flex-shrink:0; }

/* Inactivity monitor badges */
.inact-badge { display:inline-block; padding:2px 7px; border-radius:20px; font-size:10px; font-weight:700; }
.inact-ok { background:#dcfce7; color:#166534; }
.inact-w1 { background:#fef3c7; color:#92400e; }
.inact-w2 { background:#fee2e2; color:#991b1b; }

/* Quick action links */
.quick-action {
    display:flex; align-items:center; gap:12px;
    padding:12px 14px; background:#fff;
    border-radius:10px; border:1.5px solid #e2e8f0;
    text-decoration:none; color:#0f172a;
    transition:all .2s; margin-bottom:8px;
}
.quick-action:hover { border-color:#c7d2fe; transform:translateX(3px); box-shadow:0 3px 12px rgba(99,102,241,.1); }
.qa-icon { width:36px; height:36px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }
.qa-warning { background:linear-gradient(135deg,#fef3c7,#fde68a); color:#92400e; }
.qa-ban     { background:linear-gradient(135deg,#fee2e2,#fecaca); color:#991b1b; }
.qa-label   { font-size:12px; font-weight:700; }
.qa-sub     { font-size:10px; color:#64748b; margin-top:1px; }

@media(max-width:900px) {
    .dash-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')

@php $cfg = \App\Models\InactivitySetting::current(); @endphp

{{-- Hero --}}
<div class="admin-hero">
    <div>
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;opacity:.6;margin-bottom:5px">
            <i class="fa-solid fa-shield-halved"></i> Administrator Portal
        </div>
        <div class="admin-hero-title">Admin Dashboard</div>
        <div class="admin-hero-sub">Monitor users, warnings, bans, and platform activity.</div>
    </div>
    <div style="text-align:right;z-index:1">
        <div style="font-size:11px;opacity:.6;margin-bottom:3px">Logged in as</div>
        <div style="font-size:14px;font-weight:800">{{ auth()->user()->name }}</div>
        <div style="font-size:10px;opacity:.6;margin-top:2px">{{ now()->format('D, d M Y') }}</div>
    </div>
</div>

{{-- Stats row --}}
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-users" style="color:#6366f1"></i></div>
        <div class="val">{{ \App\Models\User::where('role','member')->count() }}</div>
        <div class="lbl">Members</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-chalkboard-user" style="color:#8b5cf6"></i></div>
        <div class="val">{{ \App\Models\User::where('role','lecturer')->count() }}</div>
        <div class="lbl">Lecturers</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-clipboard-list" style="color:#3b82f6"></i></div>
        <div class="val">{{ \App\Models\Quiz::count() }}</div>
        <div class="lbl">Quizzes</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b"></i></div>
        <div class="val">{{ \App\Models\Warning::whereNull('resolved_at')->count() }}</div>
        <div class="lbl">Open Warnings</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-ban" style="color:#ef4444"></i></div>
        <div class="val">{{ \App\Models\Blacklist::where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at','>',now()))->count() }}</div>
        <div class="lbl">Active Bans</div>
    </div>
</div>

{{-- Main two-column grid --}}
<div class="dash-grid">

    {{-- LEFT: main content --}}
    <div>

        {{-- Recent Users --}}
        <div class="card" style="margin-bottom:18px">
            <div class="card-header">
                <h2><i class="fa-solid fa-users"></i> Recent Users</h2>
                <span style="font-size:11px;color:#64748b;font-weight:600">Latest registrations</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(\App\Models\User::latest()->take(8)->get() as $user)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0">
                                        {{ strtoupper(substr($user->name,0,1)) }}
                                    </div>
                                    <span style="font-weight:600;font-size:13px">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td style="color:#64748b;font-size:12px">{{ $user->email }}</td>
                            <td>
                                @if($user->role === 'lecturer')
                                    <span class="badge" style="background:#ede9fe;color:#5b21b6"><i class="fa-solid fa-chalkboard-user"></i> Lecturer</span>
                                @elseif($user->role === 'admin')
                                    <span class="badge" style="background:#fee2e2;color:#991b1b"><i class="fa-solid fa-shield-halved"></i> Admin</span>
                                @else
                                    <span class="badge badge-done"><i class="fa-solid fa-user-graduate"></i> Student</span>
                                @endif
                            </td>
                            <td style="color:#64748b;font-size:12px">{{ $user->created_at?->format('d M Y') ?? '—' }}</td>
                            <td>
                                @if($user->is_active)
                                    <span style="color:#065f46;font-size:12px;font-weight:700"><i class="fa-solid fa-circle" style="font-size:7px"></i> Active</span>
                                @else
                                    <span style="color:#991b1b;font-size:12px;font-weight:700"><i class="fa-solid fa-circle" style="font-size:7px"></i> Inactive</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Inactivity Monitor --}}
        @php
            $inactiveUsers = \App\Models\User::where('role','member')
                ->whereDoesntHave('posts',   fn($q) => $q->where('created_at','>=',now()->subDays($cfg->inactivity_days)))
                ->whereDoesntHave('replies', fn($q) => $q->where('created_at','>=',now()->subDays($cfg->inactivity_days)))
                ->with(['warnings' => fn($q) => $q->where('reason','like','Inactivity warning%')->whereNull('resolved_at')->orderBy('created_at')])
                ->get();
        @endphp
        <div class="card">
            <div class="card-header">
                <h2><i class="fa-solid fa-user-clock" style="color:#f59e0b"></i>
                    Inactivity Monitor
                    <span style="font-size:11px;font-weight:500;color:#64748b;margin-left:6px">inactive &gt; {{ $cfg->inactivity_days }} days</span>
                </h2>
                <form method="POST" action="{{ route('admin.inactivity.run') }}">
                    @csrf
                    <button class="btn btn-warning btn-sm"><i class="fa-solid fa-rotate"></i> Run Check Now</button>
                </form>
            </div>

            @if($inactiveUsers->isEmpty())
                <div style="padding:32px;text-align:center;color:#64748b;font-size:13px">
                    <i class="fa-solid fa-circle-check" style="color:#22c55e;font-size:28px;display:block;margin-bottom:8px"></i>
                    All members are active — no inactivity issues.
                </div>
            @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Last Post</th>
                            <th>Last Reply</th>
                            <th>Warnings</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($inactiveUsers as $u)
                        @php
                            $warnCount = $u->warnings->count();
                            $lastPost  = $u->posts()->latest()->value('created_at');
                            $lastReply = $u->replies()->latest()->value('created_at');
                        @endphp
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <div style="width:28px;height:28px;border-radius:7px;background:linear-gradient(135deg,#f59e0b,#ef4444);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0">
                                        {{ strtoupper(substr($u->name,0,1)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight:600;font-size:13px">{{ $u->name }}</div>
                                        <div style="font-size:11px;color:#64748b">{{ $u->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size:12px;color:#64748b">{{ $lastPost ? \Carbon\Carbon::parse($lastPost)->diffForHumans() : 'Never' }}</td>
                            <td style="font-size:12px;color:#64748b">{{ $lastReply ? \Carbon\Carbon::parse($lastReply)->diffForHumans() : 'Never' }}</td>
                            <td>
                                @if($warnCount === 0)
                                    <span class="inact-badge inact-ok">No warnings</span>
                                @elseif($warnCount === 1)
                                    <span class="inact-badge inact-w1"><i class="fa-solid fa-triangle-exclamation"></i> 1 / 2</span>
                                @else
                                    <span class="inact-badge inact-w2"><i class="fa-solid fa-triangle-exclamation"></i> 2 / 2</span>
                                @endif
                            </td>
                            <td>
                                @if($u->isBanned())
                                    <span class="badge badge-closed"><i class="fa-solid fa-ban"></i> Blacklisted</span>
                                @elseif($warnCount >= 2)
                                    <span class="badge" style="background:#fee2e2;color:#991b1b">⚠ Auto-ban pending</span>
                                @elseif($warnCount === 1)
                                    <span class="badge" style="background:#fef3c7;color:#92400e">⚠ 2nd warning pending</span>
                                @else
                                    <span class="badge" style="background:#fef9c3;color:#713f12">Inactive</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

    </div>{{-- end left --}}

    {{-- RIGHT: sidebar cards --}}
    <div>

        {{-- Quick Actions --}}
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><h2><i class="fa-solid fa-bolt"></i> Quick Actions</h2></div>
            <div class="card-body" style="padding:14px">
                <a href="{{ route('admin.warnings.index') }}" class="quick-action">
                    <div class="qa-icon qa-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div>
                        <div class="qa-label">Warning Registry</div>
                        <div class="qa-sub">View & resolve warnings</div>
                    </div>
                    <i class="fa-solid fa-chevron-right" style="margin-left:auto;color:#94a3b8;font-size:10px"></i>
                </a>
                <a href="{{ route('admin.blacklists.index') }}" class="quick-action">
                    <div class="qa-icon qa-ban"><i class="fa-solid fa-ban"></i></div>
                    <div>
                        <div class="qa-label">Blacklist Log</div>
                        <div class="qa-sub">Manage banned users</div>
                    </div>
                    <i class="fa-solid fa-chevron-right" style="margin-left:auto;color:#94a3b8;font-size:10px"></i>
                </a>
            </div>
        </div>

        {{-- Inactivity Settings --}}
        <div class="card" style="margin-bottom:16px">
            <div class="card-header">
                <h2><i class="fa-solid fa-sliders" style="color:#f59e0b"></i> Inactivity Settings</h2>
            </div>
            <div class="card-body" style="padding:16px">
                <form method="POST" action="{{ route('admin.inactivity.save') }}">
                    @csrf
                    <div class="inact-field">
                        <label><i class="fa-solid fa-hourglass-start" style="color:#f59e0b"></i> Inactivity threshold (days)</label>
                        <input type="number" name="inactivity_days" value="{{ $cfg->inactivity_days }}" min="1" max="365" required>
                        <small>No posts/replies before Warning 1</small>
                    </div>
                    <div class="inact-field">
                        <label><i class="fa-solid fa-hourglass-half" style="color:#f59e0b"></i> Days until Warning 2</label>
                        <input type="number" name="second_warning_days" value="{{ $cfg->second_warning_days }}" min="1" max="365" required>
                        <small>After Warning 1</small>
                    </div>
                    <div class="inact-field">
                        <label><i class="fa-solid fa-hourglass-end" style="color:#ef4444"></i> Days until auto-blacklist</label>
                        <input type="number" name="blacklist_after_days" value="{{ $cfg->blacklist_after_days }}" min="1" max="365" required>
                        <small>After Warning 2</small>
                    </div>
                    <div class="inact-field">
                        <label><i class="fa-solid fa-ban" style="color:#ef4444"></i> Blacklist duration (days)</label>
                        <input type="number" name="blacklist_duration_days" value="{{ $cfg->blacklist_duration_days }}" min="1" max="365" required>
                        <small>How long the auto-ban lasts</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" style="width:100%;margin-top:8px;justify-content:center">
                        <i class="fa-solid fa-floppy-disk"></i> Save Settings
                    </button>
                </form>

                {{-- Enforcement Timeline --}}
                <div style="margin-top:14px;padding:12px;background:#f8fafc;border-radius:9px;border:1px solid #e2e8f0">
                    <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Enforcement Timeline</div>
                    <div class="tl-step">
                        <span class="tl-dot" style="background:#f1f5f9;color:#64748b">0</span>
                        <span>Day <strong>0</strong> — Member goes inactive</span>
                    </div>
                    <div class="tl-step">
                        <span class="tl-dot" style="background:#fef3c7;color:#92400e">1</span>
                        <span>Day <strong>{{ $cfg->inactivity_days }}</strong> — Warning 1 issued</span>
                    </div>
                    <div class="tl-step">
                        <span class="tl-dot" style="background:#fecaca;color:#991b1b">2</span>
                        <span>Day <strong>{{ $cfg->inactivity_days + $cfg->second_warning_days }}</strong> — Warning 2 issued</span>
                    </div>
                    <div class="tl-step">
                        <span class="tl-dot" style="background:#fee2e2;color:#991b1b">🔒</span>
                        <span>Day <strong>{{ $cfg->inactivity_days + $cfg->second_warning_days + $cfg->blacklist_after_days }}</strong> — Auto-blacklisted ({{ $cfg->blacklist_duration_days }}d)</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Platform Summary --}}
        <div class="card">
            <div class="card-header"><h2><i class="fa-solid fa-chart-simple"></i> Platform Summary</h2></div>
            <div class="card-body" style="padding:16px">
                @php
                    $rows = [
                        ['Total Users',   \App\Models\User::count(),                               'fa-users',          '#6366f1'],
                        ['Total Groups',  \App\Models\Group::count(),                              'fa-people-group',   '#8b5cf6'],
                        ['Total Quizzes', \App\Models\Quiz::count(),                               'fa-clipboard-list', '#3b82f6'],
                        ['Published',     \App\Models\Quiz::where('status','published')->count(),  'fa-circle-play',    '#10b981'],
                        ['Submissions',   \App\Models\QuizAttempt::count(),                        'fa-paper-plane',    '#f59e0b'],
                    ];
                @endphp
                @foreach($rows as [$label, $val, $icon, $color])
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9">
                    <span style="font-size:12px;color:#64748b;display:flex;align-items:center;gap:6px">
                        <i class="fa-solid fa-{{ $icon }}" style="color:{{ $color }};width:13px"></i> {{ $label }}
                    </span>
                    <span style="font-weight:800;font-size:13px;color:{{ $color }}">{{ $val }}</span>
                </div>
                @endforeach
            </div>
        </div>

    </div>{{-- end right --}}

</div>{{-- end dash-grid --}}

@endsection
