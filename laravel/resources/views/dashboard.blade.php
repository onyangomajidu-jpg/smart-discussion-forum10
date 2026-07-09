@extends('layouts.app')

@section('title', 'Dashboard — Smart Discussion Forum')

@section('content')
<style>
    :root {
        --primary:   #4f46e5;
        --primary-light: #e0e7ff;
        --primary-mid:   #c7d2fe;
        --danger:    #ef4444;
        --bg:        #f1f5f9;
        --surface:   #ffffff;
        --border:    #e2e8f0;
        --text:      #0f172a;
        --muted:     #64748b;
        --sidebar-w: 240px;
        --nav-h:     64px;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; background: var(--bg); color: var(--text); }

    /* ── Top navbar ── */
    .navbar {
        position: fixed; top: 0; left: 0; right: 0; z-index: 200;
        height: var(--nav-h);
        background: var(--primary);
        display: flex; align-items: center; justify-content: space-between;
        padding: 0 24px;
        box-shadow: 0 2px 12px rgba(79,70,229,.4);
    }
    .navbar-left { display: flex; align-items: center; gap: 12px; }
    .navbar-logo {
        width: 34px; height: 34px; border-radius: 10px;
        background: rgba(255,255,255,.18);
        border: 1px solid rgba(255,255,255,.28);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .navbar-logo i { color: #fff; font-size: 15px; }
    .navbar-brand { font-size: 15px; font-weight: 800; color: #fff; letter-spacing: -.3px; }
    .navbar-brand span { color: rgba(255,255,255,.65); font-weight: 500; }

    .navbar-right { display: flex; align-items: center; gap: 6px; }

    /* notification bell */
    .nav-icon-btn {
        position: relative; width: 36px; height: 36px; border-radius: 9px;
        background: none; border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        color: rgba(255,255,255,.75); font-size: 15px;
        transition: background .15s, color .15s; text-decoration: none;
    }
    .nav-icon-btn:hover { background: rgba(255,255,255,.15); color: #fff; }
    .nav-notif-dot {
        position: absolute; top: 6px; right: 6px;
        width: 8px; height: 8px; border-radius: 50%;
        background: #f87171; border: 2px solid var(--primary);
    }

    /* divider */
    .nav-divider { width: 1px; height: 22px; background: rgba(255,255,255,.2); margin: 0 8px; }

    /* user section */
    .nav-user {
        display: flex; align-items: center; gap: 9px;
        padding: 5px 10px 5px 6px;
        border-radius: 10px; cursor: default;
        transition: background .15s;
    }
    .nav-user:hover { background: rgba(255,255,255,.12); }
    .nav-avatar {
        width: 32px; height: 32px; border-radius: 9px;
        background: rgba(255,255,255,.22);
        border: 1.5px solid rgba(255,255,255,.35);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: 13px; flex-shrink: 0;
    }
    .nav-user-info { line-height: 1.3; }
    .nav-user-name { font-size: 13px; font-weight: 700; color: #fff; }
    .nav-user-role { font-size: 11px; color: rgba(255,255,255,.6); }

    /* sign out */
    .btn-signout {
        display: flex; align-items: center; gap: 6px;
        background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.25);
        border-radius: 8px; color: #fff; font-size: 12px; font-weight: 600;
        padding: 6px 14px; cursor: pointer; transition: all .15s;
        font-family: inherit;
    }
    .btn-signout:hover { background: rgba(255,255,255,.22); border-color: rgba(255,255,255,.4); }

    /* ── Sidebar ── */
    .sidebar {
        position: fixed; top: var(--nav-h); left: 0; bottom: 0;
        width: var(--sidebar-w);
        background: var(--surface);
        border-right: 1px solid var(--border);
        display: flex; flex-direction: column;
        overflow-y: auto;
        z-index: 100;
    }
    .sidebar-section { padding: 20px 16px 8px; }
    .sidebar-label { font-size: 10px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .8px; margin-bottom: 6px; padding: 0 8px; }
    .nav-item {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 12px; border-radius: 8px;
        font-size: 13px; font-weight: 500; color: var(--muted);
        cursor: pointer; transition: all .15s; text-decoration: none;
        margin-bottom: 2px;
    }
    .nav-item:hover { background: #f1f5f9; color: var(--text); }
    .nav-item.active { background: #ede9fe; color: var(--primary); font-weight: 700; }
    .nav-item .icon { font-size: 16px; width: 20px; text-align: center; flex-shrink: 0; }
    .sidebar-footer {
        margin-top: auto;
        background: var(--primary);
        padding: 16px;
    }
    .sidebar-user { display: flex; align-items: center; gap: 10px; }
    .avatar {
        width: 38px; height: 38px; border-radius: 10px;
        background: rgba(255,255,255,.2);
        border: 1.5px solid rgba(255,255,255,.3);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: 14px; flex-shrink: 0;
    }
    .sidebar-user-info .name { font-size: 13px; font-weight: 700; color: #fff; }
    .sidebar-user-info .role { font-size: 11px; color: rgba(255,255,255,.6); }

    /* ── Main content ── */
    .main {
        margin-left: var(--sidebar-w);
        margin-top: var(--nav-h);
        padding: 28px 28px 48px;
        min-height: calc(100vh - var(--nav-h));
    }

    .page-header { margin-bottom: 24px; }
    .page-header h1 { font-size: 22px; font-weight: 800; letter-spacing: -.4px; }
    .page-header p  { color: var(--muted); font-size: 13px; margin-top: 4px; }

    /* ── Stat cards ── */
    .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    .stat-card {
        background: var(--surface); border: 1px solid var(--primary-mid);
        border-radius: 12px; padding: 20px;
        transition: box-shadow .2s, transform .2s; border-left: 4px solid var(--primary);
    }
    .stat-card:hover { box-shadow: 0 4px 16px rgba(79,70,229,.15); transform: translateY(-2px); }
    .stat-card .stat-icon { font-size: 22px; margin-bottom: 10px; color: var(--primary); }
    .stat-card .value { font-size: 30px; font-weight: 800; letter-spacing: -.5px; color: var(--primary); }
    .stat-card .label { font-size: 11px; color: var(--muted); margin-top: 3px; font-weight: 500; }

    /* ── Panel grid ── */
    .panel-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .panel {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: 12px; overflow: hidden;
        display: flex; flex-direction: column; min-height: 300px;
        transition: box-shadow .2s;
    }
    .panel:hover { box-shadow: 0 4px 16px rgba(0,0,0,.07); }
    .panel-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
        font-size: 14px; font-weight: 700;
    }
    .panel-header-left { display: flex; align-items: center; gap: 10px; }
    .panel-view-all {
        font-size: 11px; font-weight: 600;
        color: rgba(255,255,255,.8); text-decoration: none;
        background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.25);
        border-radius: 6px; padding: 3px 10px;
        transition: background .15s;
    }
    .panel-view-all:hover { background: rgba(255,255,255,.28); color: #fff; }
    .panel-topics   .panel-header { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; }
    .panel-quiz     .panel-header { background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: #fff; }
    .panel-stats    .panel-header { background: linear-gradient(135deg, #f59e0b, #ef4444); color: #fff; }
    .panel-account  .panel-header { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
    .panel-topics  { border-top: 3px solid #8b5cf6; }
    .panel-quiz    { border-top: 3px solid #06b6d4; }
    .panel-stats   { border-top: 3px solid #ef4444; }
    .panel-account { border-top: 3px solid #10b981; }
    .accent-bar { width: 4px; height: 20px; border-radius: 2px; flex-shrink: 0; }
    .panel-body { padding: 16px 20px; flex: 1; overflow-y: auto; }

    /* list rows */
    .list-row {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 0; border-bottom: 1px solid var(--border);
        font-size: 13px;
    }
    .list-row:last-child { border-bottom: none; }
    .list-dot { font-size: 10px; font-weight: 700; flex-shrink: 0; }
    .badge {
        margin-left: auto; font-size: 11px; font-weight: 600;
        padding: 2px 9px; border-radius: 10px;
        background: #f1f5f9; color: var(--muted);
    }

    /* panel body tint */
    .panel-topics .panel-body, .panel-quiz .panel-body,
    .panel-stats .panel-body, .panel-account .panel-body {
        background: linear-gradient(180deg, #f5f7ff 0%, #fff 50%);
    }

    /* progress */
    .progress-row { margin-bottom: 20px; }
    .progress-row:last-child { margin-bottom: 0; }
    .progress-label { display: flex; justify-content: space-between; font-size: 12px; font-weight: 600; margin-bottom: 7px; }
    .progress-label span:last-child { color: var(--muted); }
    .progress-track { height: 8px; background: var(--border); border-radius: 4px; overflow: hidden; }
    .progress-fill  { height: 100%; border-radius: 4px; transition: width .7s cubic-bezier(.4,0,.2,1); }

    /* account */
    .info-row {
        display: flex; align-items: center;
        padding: 11px 0; border-bottom: 1px solid #ede9fe; font-size: 13px;
    }
    .info-row:last-of-type { border-bottom: none; }
    .info-row .key { color: var(--primary); font-weight: 600; width: 110px; flex-shrink: 0; font-size: 12px; }
    .role-badge {
        display: inline-block; padding: 3px 10px; border-radius: 12px;
        font-size: 12px; font-weight: 600;
    }
    .role-member   { background: var(--primary-light); color: #3730a3; }
    .role-lecturer { background: var(--primary-light); color: #3730a3; }
    .role-admin    { background: #fee2e2; color: #991b1b; }

    .btn-danger {
        display: inline-block; margin-top: 16px;
        background: var(--danger); color: #fff; border: none;
        border-radius: 8px; padding: 9px 20px; font-size: 13px; font-weight: 600;
        cursor: pointer; text-decoration: none; transition: opacity .2s;
    }
    .btn-danger:hover { opacity: .88; }

    .empty-state { text-align: center; color: var(--muted); font-size: 13px; padding: 36px 0; }
    .empty-state .icon { font-size: 30px; margin-bottom: 10px; }

    .flash { background: #d1fae5; color: #065f46; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; }

    @media (max-width: 900px) {
        .sidebar { display: none; }
        .main { margin-left: 0; }
        .stats-row { grid-template-columns: 1fr 1fr; }
        .panel-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 540px) {
        .stats-row { grid-template-columns: 1fr; }
    }
</style>

{{-- ── Top Navbar ── --}}
<nav class="navbar">
    <div class="navbar-left">
        <div class="navbar-logo"><i class="fa-solid fa-graduation-cap"></i></div>
        <span class="navbar-brand">Smart<span>Forum</span></span>
    </div>
    <div class="navbar-right">
        <a href="{{ route('notifications.index') }}" class="nav-icon-btn" title="Notifications">
            <i class="fa-solid fa-bell"></i>
            <span class="nav-notif-dot"></span>
        </a>
        <div class="nav-divider"></div>
        <div class="nav-user">
            <div class="nav-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div class="nav-user-info">
                <div class="nav-user-name">{{ auth()->user()->name }}</div>
                <div class="nav-user-role">{{ ucfirst(auth()->user()->role) }}</div>
            </div>
        </div>
        <div class="nav-divider"></div>
        <form action="{{ route('logout') }}" method="POST" style="display:inline">
            @csrf
            <button type="submit" class="btn-signout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out
            </button>
        </form>
    </div>
</nav>

{{-- ── Sidebar ── --}}
<aside class="sidebar">
    <div class="sidebar-section">
        <div class="sidebar-label">Main</div>
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="icon">🏠</span> Dashboard
        </a>
        <a href="{{ route('topics.index') }}" class="nav-item {{ request()->routeIs('topics.*') ? 'active' : '' }}">
            <span class="icon">💬</span> Topics
        </a>
        @if(auth()->user()->role === 'member')
        <a href="{{ route('quizzes.index') }}" class="nav-item {{ request()->routeIs('quizzes.*') ? 'active' : '' }}">
            <span class="icon">🎯</span> Quizzes
        </a>
        @endif
        <a href="{{ route('notifications.index') }}" class="nav-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
            <span class="icon">🔔</span> Notifications
        </a>
    </div>

    @if(auth()->user()->role === 'lecturer')
    <div class="sidebar-section">
        <div class="sidebar-label">Lecturer</div>
        <a href="{{ route('lecturer.dashboard') }}" class="nav-item {{ request()->routeIs('lecturer.dashboard') ? 'active' : '' }}">
            <span class="icon">📊</span> Lecturer Panel
        </a>
        <a href="{{ route('lecturer.quizzes.index') }}" class="nav-item {{ request()->routeIs('lecturer.quizzes.*') ? 'active' : '' }}">
            <span class="icon">📝</span> Manage Quizzes
        </a>
    </div>
    @endif

    @if(auth()->user()->role === 'admin')
    <div class="sidebar-section">
        <div class="sidebar-label">Admin</div>
        <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <span class="icon">⚙️</span> Admin Panel
        </a>
    </div>
    @endif

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div class="sidebar-user-info">
                <div class="name">{{ auth()->user()->name }}</div>
                <div class="role">{{ ucfirst(auth()->user()->role) }}</div>
            </div>
        </div>
    </div>
</aside>

{{-- ── Main Content ── --}}
<main class="main">

    @if(session('success'))
        <div class="flash">{{ session('success') }}</div>
    @endif

    <div class="page-header">
        <h1>Dashboard</h1>
        <p>Welcome back, {{ auth()->user()->name }}! Here's your activity overview.</p>
    </div>

    {{-- Stat cards --}}
    <div class="stats-row">
        <a href="{{ route('topics.index') }}" style="text-decoration:none">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-comments"></i></div>
            <div class="value" id="sc-topics">—</div>
            <div class="label">Topics Joined</div>
        </div></a>
        <a href="{{ route('topics.index') }}" style="text-decoration:none">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-pen-to-square"></i></div>
            <div class="value" id="sc-posts">—</div>
            <div class="label">Posts Made</div>
        </div></a>
        @if(auth()->user()->role === 'member')
        <a href="{{ route('quizzes.index') }}" style="text-decoration:none">
        @else
        <a href="#" style="text-decoration:none">
        @endif
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-bullseye"></i></div>
            <div class="value" id="sc-attempts">—</div>
            <div class="label">Quiz Attempts</div>
        </div></a>
        @if(auth()->user()->role === 'member')
        <a href="{{ route('quizzes.index') }}" style="text-decoration:none">
        @else
        <a href="#" style="text-decoration:none">
        @endif
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-star"></i></div>
            <div class="value" id="sc-avg">—</div>
            <div class="label">Avg Quiz Score</div>
        </div></a>
    </div>

    {{-- 2×2 Panel grid --}}
    <div class="panel-grid">

        {{-- Panel 1: Topic Participation --}}
        <div class="panel panel-topics">
            <div class="panel-header">
                <div class="panel-header-left">
                    <i class="fa-solid fa-comments"></i>&nbsp; Topic Participation
                </div>
                <a href="{{ route('topics.index') }}" class="panel-view-all">
                    View All <i class="fa-solid fa-arrow-right" style="font-size:9px"></i>
                </a>
            </div>
            <div class="panel-body" id="topicsPanel">
                <div class="empty-state"><div class="icon">💬</div>No topic participation yet.</div>
            </div>
        </div>

        {{-- Panel 2: Quiz Attempts --}}
        <div class="panel panel-quiz">
            <div class="panel-header">
                <i class="fa-solid fa-bullseye"></i>&nbsp; Quiz Attempts
            </div>
            <div class="panel-body" id="quizPanel">
                <div class="empty-state"><div class="icon">📋</div>No quiz attempts yet.</div>
            </div>
        </div>

        {{-- Panel 3: Statistics Review --}}
        <div class="panel panel-stats">
            <div class="panel-header">
                <i class="fa-solid fa-chart-bar"></i>&nbsp; Statistics Review
            </div>
            <div class="panel-body">
                <div class="progress-row">
                    <div class="progress-label"><span>Forum Engagement</span><span id="pct-eng">0%</span></div>
                    <div class="progress-track"><div class="progress-fill" id="bar-eng" style="width:0%;background:var(--primary)"></div></div>
                </div>
                <div class="progress-row">
                    <div class="progress-label"><span>Quiz Completion</span><span id="pct-comp">0%</span></div>
                    <div class="progress-track"><div class="progress-fill" id="bar-comp" style="width:0%;background:var(--primary)"></div></div>
                </div>
                <div class="progress-row">
                    <div class="progress-label"><span>Average Score</span><span id="pct-avg">N/A</span></div>
                    <div class="progress-track"><div class="progress-fill" id="bar-avg" style="width:0%;background:var(--primary)"></div></div>
                </div>
            </div>
        </div>

        {{-- Panel 4: Account Management --}}
        <div class="panel panel-account">
            <div class="panel-header">
                <i class="fa-solid fa-gear"></i>&nbsp; Account Management
            </div>
            <div class="panel-body">
                <div class="info-row">
                    <span class="key">Full Name</span>
                    <span>{{ auth()->user()->name }}</span>
                </div>
                <div class="info-row">
                    <span class="key">Email</span>
                    <span>{{ auth()->user()->email }}</span>
                </div>
                <div class="info-row">
                    <span class="key">Role</span>
                    <span class="role-badge role-{{ auth()->user()->role }}">{{ ucfirst(auth()->user()->role) }}</span>
                </div>
                <div class="info-row">
                    <span class="key">Joined</span>
                    <span>{{ auth()->user()->created_at->format('M d, Y') }}</span>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-danger">Sign Out</button>
                </form>
            </div>
        </div>

    </div>
</main>

<script>
fetch('/api/dashboard', {
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
    },
    credentials: 'same-origin'
})
    .then(r => r.json())
    .then(({ stats: s }) => {
        document.getElementById('sc-topics').textContent   = s.topicsParticipated;
        document.getElementById('sc-posts').textContent    = s.totalPosts;
        document.getElementById('sc-attempts').textContent = s.quizAttempts;
        document.getElementById('sc-avg').textContent      = s.avgScore != null ? Math.round(s.avgScore) + '%' : '—';

        const engPct  = Math.min(s.totalPosts * 5, 100);
        const total   = s.quizAttempts + s.availableQuizzes;
        const compPct = total > 0 ? Math.round(s.quizAttempts * 100 / total) : 0;
        const avgPct  = s.avgScore != null ? Math.round(s.avgScore) : 0;

        setBar('eng',  engPct,  engPct + '%');
        setBar('comp', compPct, compPct + '%');
        setBar('avg',  avgPct,  s.avgScore != null ? avgPct + '%' : 'N/A');

        const tp = document.getElementById('topicsPanel');
        if (s.recentTopics.length) {
            tp.innerHTML = s.recentTopics.map(t =>
                `<a href="/topics/${t.id}" style="text-decoration:none;color:inherit">
                    <div class="list-row">
                        <span class="list-dot" style="color:#8b5cf6">●</span>
                        <span style="flex:1">${esc(t.title)}</span>
                        <i class="fa-solid fa-arrow-right" style="font-size:10px;color:#c4b5fd"></i>
                    </div>
                </a>`
            ).join('');
        }

        const qp = document.getElementById('quizPanel');
        if (s.recentAttempts.length) {
            qp.innerHTML = s.recentAttempts.map(a =>
                `<a href="/quizzes" style="text-decoration:none;color:inherit"><div class="list-row"><span class="list-dot" style="color:var(--success)">✓</span>${esc(a.title)}<span class="badge">${a.score != null ? Math.round(a.score) + '%' : '—'}</span></div></a>`
            ).join('');
        }
    })
    .catch(() => {});

function setBar(id, pct, label) {
    document.getElementById('bar-' + id).style.width = pct + '%';
    document.getElementById('pct-' + id).textContent = label;
}
function esc(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
@endsection
