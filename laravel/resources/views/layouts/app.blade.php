<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smart Discussion Forum')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        :root {
            --primary:   #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --grad:      linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            --grad-warm: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            --grad-green:linear-gradient(135deg, #10b981 0%, #059669 100%);
            --bg:        #f1f5f9;
            --surface:   #ffffff;
            --border:    #e2e8f0;
            --text:      #0f172a;
            --muted:     #64748b;
            --success:   #10b981;
            --danger:    #ef4444;
            --warning:   #f59e0b;
            --info:      #3b82f6;
            --radius:    14px;
            --shadow:    0 4px 24px rgba(99,102,241,.10);
            --shadow-sm: 0 2px 8px rgba(0,0,0,.06);
            --shadow-lg: 0 12px 40px rgba(99,102,241,.18);
        }
        body { font-family:'Inter',system-ui,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; display:flex; flex-direction:column; }

        /* ── Top Navigation ─────────────────────────────────────────── */
        .topnav {
            background: var(--grad);
            padding: 0 28px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(99,102,241,.35);
            position: sticky; top:0; z-index:300;
        }
        .topnav-brand { display:flex; align-items:center; gap:12px; color:#fff; text-decoration:none; }
        .topnav-brand .brand-icon {
            width:42px; height:42px; border-radius:10px;
            background:rgba(255,255,255,.15);
            display:flex; align-items:center; justify-content:center;
            backdrop-filter:blur(4px);
            border:1.5px solid rgba(255,255,255,.3);
            overflow:hidden; flex-shrink:0;
            padding:4px;
        }
        .topnav-brand .brand-icon img {
            width:100%; height:100%;
            object-fit:contain;
            filter:drop-shadow(0 1px 3px rgba(0,0,0,.25));
        }
        .topnav-brand .name { font-size:16px; font-weight:800; letter-spacing:-.4px; }
        .topnav-brand .sub  { font-size:10px; opacity:.7; font-weight:500; letter-spacing:.3px; text-transform:uppercase; }
        .topnav-right { display:flex; align-items:center; gap:10px; }

        /* Notification button */
        .topnav-icon-btn {
            width:36px; height:36px; border-radius:9px;
            background:rgba(255,255,255,.12);
            border:1.5px solid rgba(255,255,255,.2);
            color:#fff; font-size:14px;
            display:flex; align-items:center; justify-content:center;
            cursor:pointer; transition:all .2s; flex-shrink:0;
        }
        .topnav-icon-btn:hover { background:rgba(255,255,255,.25); }

        /* Vertical divider */
        .topnav-divider { width:1px; height:28px; background:rgba(255,255,255,.2); flex-shrink:0; }

        /* Profile chip */
        .topnav-profile {
            display:flex; align-items:center; gap:10px;
            background:rgba(255,255,255,.12);
            border:1.5px solid rgba(255,255,255,.2);
            border-radius:12px;
            padding:6px 6px 6px 6px;
            backdrop-filter:blur(4px);
        }
        .topnav-avatar {
            width:34px; height:34px; border-radius:9px;
            background:linear-gradient(135deg,rgba(255,255,255,.4),rgba(255,255,255,.2));
            display:flex; align-items:center; justify-content:center;
            font-weight:800; font-size:14px; color:#fff;
            border:1.5px solid rgba(255,255,255,.4);
            flex-shrink:0;
        }
        .topnav-user-info { padding-right:4px; }
        .topnav-user-name { font-size:13px; font-weight:700; color:#fff; line-height:1.2; white-space:nowrap; }
        .topnav-user-role { font-size:10px; color:rgba(255,255,255,.7); font-weight:500; display:flex; align-items:center; gap:4px; margin-top:2px; }

        /* Sign out button inside chip */
        .topnav-logout-btn {
            display:flex; align-items:center; gap:6px;
            background:rgba(255,255,255,.15);
            border:1.5px solid rgba(255,255,255,.3);
            color:#fff; padding:7px 13px; border-radius:8px;
            font-size:12px; font-weight:600; cursor:pointer;
            transition:all .2s; white-space:nowrap; font-family:inherit;
        }
        .topnav-logout-btn:hover { background:rgba(239,68,68,.35); border-color:rgba(239,68,68,.5); }

        /* ── Layout ─────────────────────────────────────────────────── */
        .app-body { display:flex; flex:1; }

        /* ── Sidebar ────────────────────────────────────────────────── */
        .sidebar {
            width:230px; min-height:calc(100vh - 64px);
            background:var(--surface);
            border-right:1px solid var(--border);
            padding:20px 12px 0;
            flex-shrink:0;
            position:sticky; top:64px; height:calc(100vh - 64px); overflow-y:auto;
            display:flex; flex-direction:column;
        }
        .sidebar-nav { flex:1; }

        /* Sidebar bottom footer */
        .sidebar-footer {
            background: #4f46e5;
            margin: 0 -12px;
            padding: 14px 16px;
            display: flex; align-items: center; gap: 10px;
            flex-shrink: 0;
        }
        .sidebar-footer-avatar {
            width: 38px; height: 38px; border-radius: 10px;
            background: rgba(255,255,255,.2);
            border: 1.5px solid rgba(255,255,255,.3);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 15px; color: #fff;
            flex-shrink: 0;
        }
        .sidebar-footer-name { font-size: 13px; font-weight: 700; color: #fff; line-height: 1.2; }
        .sidebar-footer-role { font-size: 11px; color: rgba(255,255,255,.65); margin-top: 2px; display:flex; align-items:center; gap:4px; }
        .sidebar-section { margin-bottom:20px; }
        .sidebar-label { font-size:10px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:1px; padding:0 10px; margin-bottom:8px; display:flex; align-items:center; gap:6px; }
        .sidebar-link {
            display:flex; align-items:center; gap:10px;
            padding:10px 12px; border-radius:10px;
            color:var(--muted); text-decoration:none;
            font-size:13px; font-weight:500;
            transition:all .15s; margin-bottom:2px;
        }
        .sidebar-link:hover { background:#f8fafc; color:var(--primary); }
        .sidebar-link.active {
            background:linear-gradient(135deg,rgba(99,102,241,.1),rgba(139,92,246,.07));
            color:var(--primary); font-weight:600;
            border-left:3px solid var(--primary);
        }
        .sidebar-link .ico { font-size:15px; width:22px; text-align:center; }
        .sidebar-divider { height:1px; background:var(--border); margin:14px 8px; }
        .sidebar-badge { margin-left:auto; background:var(--primary); color:#fff; font-size:10px; font-weight:700; padding:2px 7px; border-radius:10px; }

        /* ── Main Content ───────────────────────────────────────────── */
        .main { flex:1; padding:32px; overflow-x:hidden; }
        .page-header { margin-bottom:28px; }
        .page-header h1 { font-size:24px; font-weight:800; color:var(--text); display:flex; align-items:center; gap:10px; }
        .page-header p  { font-size:13px; color:var(--muted); margin-top:5px; }
        .breadcrumb { display:flex; align-items:center; gap:6px; font-size:12px; color:var(--muted); margin-bottom:10px; }
        .breadcrumb a { color:var(--primary); text-decoration:none; font-weight:500; }
        .breadcrumb a:hover { text-decoration:underline; }
        .breadcrumb .sep { opacity:.4; }

        /* ── Cards ──────────────────────────────────────────────────── */
        .card { background:var(--surface); border-radius:var(--radius); box-shadow:var(--shadow-sm); border:1px solid var(--border); overflow:hidden; }
        .card-header { padding:18px 24px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; background:#fafbff; }
        .card-header h2 { font-size:15px; font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px; }
        .card-header h2 i { color:var(--primary); }
        .card-body { padding:24px; }

        /* ── Alerts ─────────────────────────────────────────────────── */
        .alert { padding:13px 18px; border-radius:10px; font-size:13px; margin-bottom:18px; display:flex; align-items:center; gap:10px; font-weight:500; }
        .alert-success { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
        .alert-danger  { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        .alert-info    { background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; }
        .alert-warning { background:#fffbeb; color:#92400e; border:1px solid #fde68a; }

        /* ── Buttons ────────────────────────────────────────────────── */
        .btn { display:inline-flex; align-items:center; gap:7px; padding:10px 20px; border:none; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; transition:all .2s; white-space:nowrap; font-family:inherit; }
        .btn-primary   { background:var(--grad); color:#fff; box-shadow:0 4px 14px rgba(99,102,241,.35); }
        .btn-primary:hover { opacity:.9; transform:translateY(-1px); box-shadow:0 6px 20px rgba(99,102,241,.45); }
        .btn-success   { background:var(--grad-green); color:#fff; box-shadow:0 4px 14px rgba(16,185,129,.3); }
        .btn-success:hover { opacity:.9; transform:translateY(-1px); }
        .btn-warning   { background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; box-shadow:0 4px 14px rgba(245,158,11,.3); }
        .btn-warning:hover { opacity:.9; transform:translateY(-1px); }
        .btn-danger    { background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; }
        .btn-danger:hover { opacity:.9; transform:translateY(-1px); }
        .btn-secondary { background:#f1f5f9; color:#475569; border:1.5px solid var(--border); }
        .btn-secondary:hover { background:#e2e8f0; }
        .btn-outline   { background:transparent; border:2px solid var(--primary); color:var(--primary); }
        .btn-outline:hover { background:var(--primary); color:#fff; }
        .btn-sm { padding:6px 13px; font-size:12px; border-radius:7px; }
        .btn:disabled { opacity:.5; cursor:not-allowed; transform:none !important; }

        /* ── Badges ─────────────────────────────────────────────────── */
        .badge { display:inline-flex; align-items:center; gap:4px; padding:4px 11px; border-radius:20px; font-size:11px; font-weight:700; }
        .badge-draft     { background:#fef3c7; color:#92400e; }
        .badge-published { background:#d1fae5; color:#065f46; }
        .badge-closed    { background:#fee2e2; color:#991b1b; }
        .badge-open      { background:#d1fae5; color:#065f46; }
        .badge-upcoming  { background:#fef3c7; color:#92400e; }
        .badge-done      { background:#dbeafe; color:#1e40af; }
        .badge-A { background:#d1fae5; color:#065f46; }
        .badge-B { background:#dbeafe; color:#1e40af; }
        .badge-C { background:#fef3c7; color:#92400e; }
        .badge-D { background:#ffedd5; color:#9a3412; }
        .badge-F { background:#fee2e2; color:#991b1b; }

        /* ── Tables ─────────────────────────────────────────────────── */
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        thead th { background:#f8fafc; padding:12px 16px; text-align:left; font-weight:700; color:var(--muted); border-bottom:2px solid var(--border); white-space:nowrap; font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
        tbody td { padding:13px 16px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
        tbody tr:hover td { background:#fafbff; }
        tbody tr:last-child td { border-bottom:none; }

        /* ── Forms ──────────────────────────────────────────────────── */
        .form-group { margin-bottom:20px; }
        .form-label { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:7px; }
        .form-hint  { font-size:11px; color:var(--muted); margin-top:5px; display:flex; align-items:center; gap:4px; }
        .form-control {
            width:100%; padding:11px 14px;
            border:2px solid var(--border); border-radius:9px;
            font-size:13px; color:var(--text);
            transition:border-color .2s, box-shadow .2s;
            background:#fff; font-family:inherit;
        }
        .form-control:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(99,102,241,.1); }
        .form-control.is-invalid { border-color:var(--danger); }
        .invalid-feedback { font-size:12px; color:var(--danger); margin-top:5px; display:flex; align-items:center; gap:4px; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:18px; }

        /* ── Stats Grid ─────────────────────────────────────────────── */
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:18px; margin-bottom:28px; }
        .stat-card {
            background:var(--surface); border-radius:var(--radius); padding:22px 18px;
            text-align:center; border:1px solid var(--border); box-shadow:var(--shadow-sm);
            position:relative; overflow:hidden; transition:transform .2s, box-shadow .2s;
        }
        .stat-card:hover { transform:translateY(-2px); box-shadow:var(--shadow); }
        .stat-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:var(--grad); }
        .stat-card .stat-icon { font-size:22px; margin-bottom:8px; }
        .stat-card .val { font-size:30px; font-weight:900; background:var(--grad); -webkit-background-clip:text; -webkit-text-fill-color:transparent; line-height:1; }
        .stat-card .lbl { font-size:11px; color:var(--muted); margin-top:5px; font-weight:600; text-transform:uppercase; letter-spacing:.6px; }

        /* ── Progress ───────────────────────────────────────────────── */
        .progress { background:#e2e8f0; border-radius:6px; height:8px; overflow:hidden; }
        .progress-bar { background:var(--grad); height:100%; border-radius:6px; transition:width .5s ease; }

        /* ── Responsive ─────────────────────────────────────────────── */
        @media(max-width:768px) {
            .sidebar { display:none; }
            .main { padding:16px; }
            .form-row { grid-template-columns:1fr; }
            .stats-grid { grid-template-columns:repeat(2,1fr); }
        }
    </style>
    @stack('styles')
</head>
<body>

<nav class="topnav">
    <a href="{{ auth()->check() && auth()->user()->isLecturer() ? route('lecturer.dashboard') : (auth()->check() && auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard')) }}" class="topnav-brand">
        <div class="brand-icon"><img src="{{ asset('images/forum.png') }}" alt="SmartForum Logo"></div>
        <div>
            <div class="name">Smart Discussion Forum</div>
            <div class="sub">Assessment Platform</div>
        </div>
    </a>

    <div class="topnav-right">
        @auth
        {{-- Notification bell --}}
        <button class="topnav-icon-btn" title="Notifications">
            <i class="fa-solid fa-bell"></i>
        </button>

        {{-- Divider --}}
        <div class="topnav-divider"></div>

        {{-- User profile chip --}}
        <div class="topnav-profile">
            <div class="topnav-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div class="topnav-user-info">
                <div class="topnav-user-name">{{ auth()->user()->name }}</div>
                <div class="topnav-user-role">
                    @if(auth()->user()->isLecturer())
                        <i class="fa-solid fa-chalkboard-user"></i> Lecturer
                    @elseif(auth()->user()->isMember())
                        <i class="fa-solid fa-user-graduate"></i> Student
                    @else
                        <i class="fa-solid fa-shield-halved"></i> Admin
                    @endif
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST" style="margin:0">
                @csrf
                <button type="submit" class="topnav-logout-btn" title="Sign Out">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>
        @endauth
    </div>
</nav>

<div class="app-body">
    <aside class="sidebar">
        @auth
        <div class="sidebar-nav">
        <div class="sidebar-divider" style="margin-top:4px"></div>

        @if(auth()->user()->isLecturer())
        <div class="sidebar-section">
            <div class="sidebar-label"><i class="fa-solid fa-chalkboard-user"></i> Lecturer</div>
            <a href="{{ route('lecturer.dashboard') }}" class="sidebar-link {{ request()->routeIs('lecturer.dashboard') ? 'active' : '' }}">
                <span class="ico"><i class="fa-solid fa-house"></i></span> Dashboard
            </a>
            <a href="{{ route('lecturer.quizzes.index') }}" class="sidebar-link {{ request()->routeIs('lecturer.quizzes.*') ? 'active' : '' }}">
                <span class="ico"><i class="fa-solid fa-clipboard-list"></i></span> My Quizzes
            </a>
            <a href="{{ route('lecturer.quizzes.create') }}" class="sidebar-link {{ request()->routeIs('lecturer.quizzes.create') ? 'active' : '' }}">
                <span class="ico"><i class="fa-solid fa-circle-plus"></i></span> Create Quiz
            </a>
            <a href="{{ route('lecturer.analytics') }}" class="sidebar-link {{ request()->routeIs('lecturer.analytics') ? 'active' : '' }}">
                <span class="ico"><i class="fa-solid fa-chart-mixed"></i></span> Analytics
            </a>
        </div>
        @elseif(auth()->user()->isMember())
        <div class="sidebar-section">
            <div class="sidebar-label"><i class="fa-solid fa-user-graduate"></i> Student</div>
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="ico"><i class="fa-solid fa-house"></i></span> Dashboard
            </a>
            <a href="{{ route('quizzes.index') }}" class="sidebar-link {{ request()->routeIs('quizzes.*') ? 'active' : '' }}">
                <span class="ico"><i class="fa-solid fa-file-pen"></i></span> My Quizzes
            </a>
            <a href="{{ route('analytics.index') }}" class="sidebar-link {{ request()->routeIs('analytics.index') ? 'active' : '' }}">
                <span class="ico"><i class="fa-solid fa-chart-line"></i></span> Analytics
            </a>
            <a href="{{ route('groups.index') }}" class="sidebar-link {{ request()->routeIs('groups.*') ? 'active' : '' }}">
                <span class="ico"><i class="fa-solid fa-people-group"></i></span> Groups
            </a>
        </div>
        @elseif(auth()->user()->isAdmin())
        <div class="sidebar-section">
            <div class="sidebar-label"><i class="fa-solid fa-shield-halved"></i> Admin</div>
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span class="ico"><i class="fa-solid fa-house"></i></span> Dashboard
            </a>
            <a href="{{ route('admin.warnings.index') }}" class="sidebar-link {{ request()->routeIs('admin.warnings.*') ? 'active' : '' }}">
                <span class="ico"><i class="fa-solid fa-triangle-exclamation"></i></span> Warnings
                @php $openWarnings = \App\Models\Warning::whereNull('resolved_at')->count(); @endphp
                @if($openWarnings > 0)<span class="sidebar-badge">{{ $openWarnings }}</span>@endif
            </a>
            <a href="{{ route('admin.blacklists.index') }}" class="sidebar-link {{ request()->routeIs('admin.blacklists.*') ? 'active' : '' }}">
                <span class="ico"><i class="fa-solid fa-ban"></i></span> Blacklist Log
            </a>
        </div>
        @endif

        </div>{{-- end .sidebar-nav --}}

        {{-- Bottom footer --}}
        <div class="sidebar-footer">
            <div class="sidebar-footer-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div>
                <div class="sidebar-footer-name">{{ auth()->user()->name }}</div>
                <div class="sidebar-footer-role">
                    @if(auth()->user()->isLecturer())
                        <i class="fa-solid fa-chalkboard-user"></i> Lecturer
                    @elseif(auth()->user()->isMember())
                        <i class="fa-solid fa-user-graduate"></i> Student
                    @else
                        <i class="fa-solid fa-shield-halved"></i> Admin
                    @endif
                </div>
            </div>
        </div>

        @endauth
    </aside>

    <main class="main">
        @if(session('success'))
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger"><i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}</div>
        @endif
        @if(session('info'))
            <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i> {{ session('info') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
            </div>
        @endif
        @yield('content')
    </main>
</div>

@stack('scripts')
</body>
</html>
