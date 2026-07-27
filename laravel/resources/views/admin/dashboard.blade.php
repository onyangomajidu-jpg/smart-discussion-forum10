@extends('layouts.app')

@section('title', 'Admin Dashboard — SmartForum')

@push('styles')
<style>
.admin-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 28px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(15,23,42,.4);
}
.admin-hero::before {
    content: '';
    position: absolute; top: -60px; right: -60px;
    width: 220px; height: 220px;
    background: rgba(99,102,241,.15); border-radius: 50%;
}
.admin-hero::after {
    content: '\f3ed';
    font-family: 'Font Awesome 6 Free'; font-weight: 900;
    position: absolute; right: 36px; top: 50%;
    transform: translateY(-50%);
    font-size: 90px; opacity: .07;
}
.admin-hero-title { font-size: 24px; font-weight: 900; margin-bottom: 6px; }
.admin-hero-sub   { font-size: 13px; opacity: .7; }

.quick-action {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 20px;
    background: #fff;
    border-radius: 12px;
    border: 1.5px solid #e2e8f0;
    text-decoration: none;
    color: #0f172a;
    transition: all .2s;
    margin-bottom: 10px;
}
.quick-action:hover {
    border-color: #c7d2fe;
    transform: translateX(4px);
    box-shadow: 0 4px 16px rgba(99,102,241,.1);
}
.quick-action-icon {
    width: 42px; height: 42px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
}
.qa-warning { background: linear-gradient(135deg,#fef3c7,#fde68a); color: #92400e; }
.qa-ban     { background: linear-gradient(135deg,#fee2e2,#fecaca); color: #991b1b; }
.qa-users   { background: linear-gradient(135deg,#dbeafe,#bfdbfe); color: #1e40af; }
.qa-quiz    { background: linear-gradient(135deg,#ede9fe,#ddd6fe); color: #5b21b6; }
.quick-action-label { font-size: 13px; font-weight: 700; }
.quick-action-sub   { font-size: 11px; color: #64748b; margin-top: 2px; }
@media (max-width: 768px) {
    .admin-hero { padding: 18px 16px; }
    .admin-hero-title { font-size: 18px; }
    .admin-hero::after { display: none; }
}
@media (max-width: 900px) {
    .admin-stats-grid { grid-template-columns: repeat(3, 1fr) !important; }
    .admin-main-grid  { grid-template-columns: 1fr !important; }
}
@media (max-width: 540px) {
    .admin-stats-grid { grid-template-columns: repeat(2, 1fr) !important; }
}
</style>
@endpush

@section('content')

<div class="admin-hero">
    <div>
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;opacity:.6;margin-bottom:6px">
            <i class="fa-solid fa-shield-halved"></i> Administrator Portal
        </div>
        <div class="admin-hero-title">Admin Dashboard</div>
        <div class="admin-hero-sub">Monitor users, warnings, bans, and platform activity.</div>
    </div>
    <div style="text-align:right;z-index:1">
        <div style="font-size:12px;opacity:.6;margin-bottom:4px">Logged in as</div>
        <div style="font-size:15px;font-weight:800">{{ auth()->user()->name }}</div>
        <div style="font-size:11px;opacity:.6;margin-top:2px">{{ now()->format('D, d M Y') }}</div>
    </div>
</div>

{{-- Stats --}}
<div class="stats-grid admin-stats-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:28px">
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

<div style="display:grid;grid-template-columns:1fr 300px;gap:22px;align-items:start" class="admin-main-grid">

    {{-- Recent Users --}}
    <div class="card">
        <div class="card-header">
            <h2><i class="fa-solid fa-users"></i> Recent Users</h2>
            <span style="font-size:12px;color:#64748b;font-weight:600">Latest registrations</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><i class="fa-solid fa-user"></i> Name</th>
                        <th><i class="fa-solid fa-envelope"></i> Email</th>
                        <th><i class="fa-solid fa-tag"></i> Role</th>
                        <th><i class="fa-solid fa-clock"></i> Joined</th>
                        <th><i class="fa-solid fa-circle"></i> Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\App\Models\User::latest()->take(8)->get() as $user)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0">
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
                                <span style="color:#065f46;font-size:12px;font-weight:700;display:flex;align-items:center;gap:4px">
                                    <i class="fa-solid fa-circle" style="font-size:7px"></i> Active
                                </span>
                            @else
                                <span style="color:#991b1b;font-size:12px;font-weight:700;display:flex;align-items:center;gap:4px">
                                    <i class="fa-solid fa-circle" style="font-size:7px"></i> Inactive
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div>
        <div class="card">
            <div class="card-header"><h2><i class="fa-solid fa-bolt"></i> Quick Actions</h2></div>
            <div class="card-body">

                @if(\Illuminate\Support\Facades\Route::has('admin.warnings.index'))
                <a href="{{ route('admin.warnings.index') }}" class="quick-action">
                    <div class="quick-action-icon qa-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div>
                        <div class="quick-action-label">Warning Registry</div>
                        <div class="quick-action-sub">View & resolve warnings</div>
                    </div>
                    <i class="fa-solid fa-chevron-right" style="margin-left:auto;color:#94a3b8;font-size:11px"></i>
                </a>
                @endif

                @if(\Illuminate\Support\Facades\Route::has('admin.blacklists.index'))
                <a href="{{ route('admin.blacklists.index') }}" class="quick-action">
                    <div class="quick-action-icon qa-ban"><i class="fa-solid fa-ban"></i></div>
                    <div>
                        <div class="quick-action-label">Blacklist Log</div>
                        <div class="quick-action-sub">Manage banned users</div>
                    </div>
                    <i class="fa-solid fa-chevron-right" style="margin-left:auto;color:#94a3b8;font-size:11px"></i>
                </a>
                @endif

            </div>
        </div>

        {{-- Platform Summary --}}
        <div class="card" style="margin-top:18px">
            <div class="card-header"><h2><i class="fa-solid fa-chart-simple"></i> Platform Summary</h2></div>
            <div class="card-body">
                @php
                    $rows = [
                        ['Total Users',    \App\Models\User::count(),                          'fa-users',           '#6366f1'],
                        ['Total Groups',   \App\Models\Group::count(),                         'fa-people-group',    '#8b5cf6'],
                        ['Total Quizzes',  \App\Models\Quiz::count(),                          'fa-clipboard-list',  '#3b82f6'],
                        ['Published',      \App\Models\Quiz::where('status','published')->count(), 'fa-circle-play', '#10b981'],
                        ['Submissions',    \App\Models\QuizAttempt::count(),                   'fa-paper-plane',     '#f59e0b'],
                    ];
                @endphp
                @foreach($rows as [$label, $val, $icon, $color])
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f1f5f9">
                    <span style="font-size:13px;color:#64748b;display:flex;align-items:center;gap:7px">
                        <i class="fa-solid fa-{{ $icon }}" style="color:{{ $color }};width:14px"></i> {{ $label }}
                    </span>
                    <span style="font-weight:800;font-size:14px;color:{{ $color }}">{{ $val }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

@endsection
