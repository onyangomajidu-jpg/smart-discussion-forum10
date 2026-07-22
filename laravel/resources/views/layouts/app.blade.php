<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/forum-favicon.png') }}">
    <title>@yield('title', 'Discussion Hub')</title>
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

        /* Mobile sidebar toggle — hidden on desktop, shown via media query below */
        .mobile-menu-btn {
            display:none; background:rgba(255,255,255,.15); border:1.5px solid rgba(255,255,255,.3);
            color:#fff; width:38px; height:38px; border-radius:9px; align-items:center; justify-content:center;
            font-size:16px; cursor:pointer; flex-shrink:0;
        }
        .mobile-menu-btn:hover { background:rgba(255,255,255,.25); }

        .sidebar-backdrop {
            display:none; position:fixed; inset:0; background:rgba(15,23,42,.5);
            z-index:399; opacity:0; transition:opacity .2s;
        }
        .sidebar-backdrop.show { display:block; opacity:1; }

        /* Notification bell + dropdown */
        .notif-wrap { position:relative; }
        .notif-badge {
            position:absolute; top:-4px; right:-4px;
            width:16px; height:16px; border-radius:50%;
            background:#ef4444; color:#fff;
            font-size:9px; font-weight:800;
            display:flex; align-items:center; justify-content:center;
            border:2px solid transparent; pointer-events:none;
        }
        .notif-dropdown {
            display:none; position:absolute; top:calc(100% + 10px); right:0;
            width:320px; background:#fff; border-radius:14px;
            border:1px solid #e2e8f0; box-shadow:0 12px 40px rgba(0,0,0,.14);
            z-index:400; overflow:hidden;
        }
        .notif-dropdown.open { display:block; }
        .notif-dd-header {
            padding:14px 18px; border-bottom:1px solid #f1f5f9;
            display:flex; align-items:center; justify-content:space-between;
        }
        .notif-dd-title { font-size:13px; font-weight:700; color:#0f172a; }
        .notif-mark-all {
            font-size:11px; color:#6366f1; font-weight:600;
            background:none; border:none; cursor:pointer; font-family:inherit;
            padding:0;
        }
        .notif-mark-all:hover { text-decoration:underline; }
        .notif-item {
            display:flex; align-items:flex-start; gap:12px;
            padding:12px 18px; border-bottom:1px solid #f8fafc;
            transition:background .15s; cursor:default;
        }
        .notif-item:last-child { border-bottom:none; }
        .notif-item.unread { background:#fafbff; }
        .notif-item:hover { background:#f1f5f9; }
        .notif-icon {
            width:34px; height:34px; border-radius:10px; flex-shrink:0;
            display:flex; align-items:center; justify-content:center; font-size:14px;
        }
        .notif-icon.info    { background:#eff6ff; color:#3b82f6; }
        .notif-icon.success { background:#ecfdf5; color:#10b981; }
        .notif-icon.warning { background:#fffbeb; color:#f59e0b; }
        .notif-icon.danger  { background:#fef2f2; color:#ef4444; }
        .notif-body { flex:1; min-width:0; }
        .notif-text { font-size:12px; color:#374151; line-height:1.5; }
        .notif-time { font-size:10px; color:#94a3b8; margin-top:3px; }
        .notif-unread-dot {
            width:7px; height:7px; border-radius:50%;
            background:#6366f1; flex-shrink:0; margin-top:5px;
        }
        .notif-empty {
            padding:36px 18px; text-align:center;
            color:#94a3b8; font-size:13px;
        }
        .notif-empty i { font-size:28px; display:block; margin-bottom:10px; opacity:.35; }
        .notif-dd-footer {
            padding:10px 18px; border-top:1px solid #f1f5f9; text-align:center;
        }
        .notif-dd-footer a {
            font-size:12px; color:#6366f1; font-weight:600; text-decoration:none;
        }
        .notif-dd-footer a:hover { text-decoration:underline; }

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
            .mobile-menu-btn { display:flex; }
            .sidebar {
                position:fixed; top:64px; left:0; z-index:400;
                width:260px; height:calc(100vh - 64px);
                transform:translateX(-100%);
                transition:transform .25s ease;
                box-shadow:4px 0 24px rgba(0,0,0,.15);
            }
            .sidebar.open { transform:translateX(0); }
            .main { padding:12px; }
            .form-row { grid-template-columns:1fr; }
            .stats-grid { grid-template-columns:repeat(2,1fr); }

            /* Keep the top bar from overflowing on narrow phones */
            .topnav { padding:0 10px; gap:6px; }
            .topnav-brand .sub { display:none; }
            .topnav-brand .name { font-size:14px; }
            .topnav-user-info { display:none; }
            .topnav-logout-btn span { display:none; }
            .topnav-logout-btn { padding:7px 9px; }
            .topnav-divider { display:none; }

            /* Notification dropdown: fixed full-width panel on mobile */
            .notif-dropdown {
                position:fixed;
                top:64px;
                left:8px;
                right:8px;
                width:auto;
                max-height:70vh;
                overflow-y:auto;
                border-radius:12px;
            }

            /* Cards & tables */
            .card-body { padding:14px; }
            .card-header { padding:12px 14px; }
            thead th, tbody td { padding:10px 10px; font-size:12px; }
            .btn { padding:9px 14px; font-size:12px; }
            .page-header h1 { font-size:20px; }
        }
        @media(max-width:480px) {
            .topnav-brand .name { font-size:13px; }
            .stats-grid { grid-template-columns:1fr 1fr; }
            .main { padding:10px; }
        }
    </style>
    @stack('styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<nav class="topnav">
    <button class="mobile-menu-btn" id="sidebarToggle" type="button" aria-label="Toggle navigation menu" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
    </button>
    <a href="{{ auth()->check() && auth()->user()->isLecturer() ? route('lecturer.dashboard') : (auth()->check() && auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard')) }}" class="topnav-brand">
        <div class="brand-icon"><img src="{{ asset('images/forum.png') }}" alt="SmartForum Logo"></div>
        <div>
            <div class="name">Discussion Hub</div>
            <div class="sub">Assessment Platform</div>
        </div>
    </a>

    <div class="topnav-right">
        @auth
        {{-- Notification bell --}}
        <div class="notif-wrap">
            <button class="topnav-icon-btn" id="notifBtn" title="Notifications" aria-haspopup="true" aria-expanded="false">
                <i class="fa-solid fa-bell"></i>
            </button>
            @auth
            @php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp
            <span class="notif-badge" id="notifBadge" style="{{ $unreadCount ? '' : 'display:none' }}">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
            @endauth

            <div class="notif-dropdown" id="notifDropdown" role="menu">
                <div class="notif-dd-header">
                    <span class="notif-dd-title"><i class="fa-solid fa-bell" style="color:#6366f1"></i> Notifications</span>
                    <button class="notif-mark-all" id="notifMarkAll">Mark all as read</button>
                </div>
                <div id="notifList">
                    <div class="notif-empty">
                        <i class="fa-solid fa-bell-slash"></i>
                        No notifications yet
                    </div>
                </div>
                <div class="notif-dd-footer">
                    <a href="#">View all notifications</a>
                </div>
            </div>
        </div>

        {{-- Divider --}}
        <div class="topnav-divider"></div>

        {{-- User profile chip --}}
        <div class="topnav-profile">
            <a href="{{ route('profile.edit') }}" style="display:flex;align-items:center;gap:0;text-decoration:none" title="Edit Profile">
                <div class="topnav-avatar">
                    @if(auth()->user()->avatar)
                        <img src="{{ asset('storage/' . auth()->user()->avatar) }}" style="width:100%;height:100%;object-fit:cover;border-radius:7px" alt="">
                    @else
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    @endif
                </div>
                <div class="topnav-user-info" style="padding-left:10px">
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
            </a>
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
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
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
            <a href="{{ route('lecturer.analytics') }}" class="sidebar-link {{ request()->routeIs('lecturer.analytics') ? 'active' : '' }}">
                <span class="ico"><i class="fa-solid fa-chart-bar"></i></span> Analytics
            </a>
            <a href="{{ route('lecturer.topics.index') }}" class="sidebar-link {{ request()->routeIs('lecturer.topics.*') ? 'active' : '' }}">
                <span class="ico"><i class="fa-solid fa-comments"></i></span> Topic Discussions
            </a>
            <a href="{{ route('lecturer.groups.index') }}" class="sidebar-link {{ request()->routeIs('lecturer.groups.*') ? 'active' : '' }}">
                <span class="ico"><i class="fa-solid fa-people-group"></i></span> Groups
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
        <div class="sidebar-footer" style="flex-direction:column;align-items:stretch;gap:0;padding:0">
            <a href="{{ route('profile.edit') }}" style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;transition:background .15s" onmouseover="this.style.background='rgba(255,255,255,.1)'" onmouseout="this.style.background=''">
                <div class="sidebar-footer-avatar">
                    @if(auth()->user()->avatar)
                        <img src="{{ asset('storage/' . auth()->user()->avatar) }}" style="width:100%;height:100%;object-fit:cover;border-radius:8px" alt="">
                    @else
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    @endif
                </div>
                <div style="flex:1;min-width:0">
                    <div class="sidebar-footer-name" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ auth()->user()->name }}</div>
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
                <i class="fa-solid fa-pen-to-square" style="color:rgba(255,255,255,.5);font-size:12px;flex-shrink:0"></i>
            </a>
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

<script>
    (function () {
        var toggleBtn = document.getElementById('sidebarToggle');
        var sidebar = document.querySelector('.sidebar');
        var backdrop = document.getElementById('sidebarBackdrop');
        if (!toggleBtn || !sidebar || !backdrop) return;

        function closeSidebar() {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
            toggleBtn.setAttribute('aria-expanded', 'false');
        }
        function openSidebar() {
            sidebar.classList.add('open');
            backdrop.classList.add('show');
            toggleBtn.setAttribute('aria-expanded', 'true');
        }

        toggleBtn.addEventListener('click', function () {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });
        backdrop.addEventListener('click', closeSidebar);

        // Close the drawer automatically after tapping a nav link on mobile
        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeSidebar);
        });
    })();
</script>

@auth
@if(auth()->user()->isMember())
<style>
.qpop-overlay {
    position:fixed;inset:0;background:rgba(15,23,42,.92);
    z-index:99999;display:none;align-items:center;justify-content:center;
    backdrop-filter:blur(6px);
}
.qpop-overlay.active { display:flex !important; }
.qpop-box {
    background:#fff;border-radius:24px;padding:44px 40px;
    max-width:460px;width:90%;text-align:center;
    box-shadow:0 32px 80px rgba(0,0,0,.5);
    animation:qpopIn .35s cubic-bezier(.34,1.56,.64,1);
}
@keyframes qpopIn { from{transform:scale(.7);opacity:0} to{transform:scale(1);opacity:1} }
@keyframes qpopBell { from{transform:rotate(-15deg)} to{transform:rotate(15deg)} }
.qpop-icon { font-size:52px;margin-bottom:14px; }
.qpop-title { font-size:21px;font-weight:900;color:#0f172a;margin-bottom:8px; }
.qpop-name { font-size:16px;font-weight:700;color:#6366f1;background:#ede9fe;border-radius:10px;padding:9px 14px;margin-bottom:12px; }
.qpop-meta { display:flex;justify-content:center;gap:14px;flex-wrap:wrap;font-size:12px;color:#64748b;font-weight:600;margin-bottom:14px; }
.qpop-meta i { color:#6366f1; }
.qpop-desc { font-size:13px;color:#64748b;line-height:1.6;margin-bottom:24px; }
.qpop-btn {
    display:inline-flex;align-items:center;gap:8px;
    background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;
    text-decoration:none;border-radius:12px;padding:13px 32px;
    font-size:15px;font-weight:800;
    box-shadow:0 8px 24px rgba(99,102,241,.45);transition:all .2s;
}
.qpop-btn:hover { opacity:.9;transform:translateY(-2px); }
body.qpop-locked { overflow:hidden;pointer-events:none; }
body.qpop-locked .qpop-overlay { pointer-events:all; }
</style>
<div id="qpop-container"></div>
<script>
(function(){
    function isDismissed(id) {
        return localStorage.getItem('quiz_started_' + id) === '1';
    }

    window.qpopClose = function(id) {
        localStorage.setItem('quiz_started_' + id, '1');
        var el = document.getElementById('qpop_' + id);
        if (el) el.classList.remove('active');
        if (!document.querySelector('.qpop-overlay.active')) {
            document.body.classList.remove('qpop-locked');
        }
    };

    function showPopup(q) {
        if (isDismissed(q.id)) return;
        if (document.getElementById('qpop_' + q.id)) return;
        var deadline = q.hard_deadline
            ? '<span><i class="fa-solid fa-flag-checkered"></i> Due ' + q.hard_deadline + '</span>'
            : '';
        var html = '<div id="qpop_' + q.id + '" class="qpop-overlay active">'
            + '<div class="qpop-box">'
            + '<div class="qpop-icon"><i class="fa-solid fa-bell" style="color:#f59e0b;animation:qpopBell .6s ease infinite alternate"></i></div>'
            + '<div class="qpop-title">Quiz is Live Now!</div>'
            + '<div class="qpop-name">' + q.title + '</div>'
            + '<div class="qpop-meta">'
            + '<span><i class="fa-solid fa-users"></i> ' + q.group + '</span>'
            + '<span><i class="fa-solid fa-stopwatch"></i> ' + q.duration + ' min</span>'
            + deadline
            + '</div>'
            + '<div class="qpop-desc">This quiz is now open. Click Start Quiz to begin.</div>'
            + '<a href="' + q.url + '" class="qpop-btn" onclick="qpopClose(' + q.id + ')">' 
            + '<i class="fa-solid fa-play"></i> Start Quiz Now</a>'
            + '</div></div>';
        document.getElementById('qpop-container').insertAdjacentHTML('beforeend', html);
        document.body.classList.add('qpop-locked');
    }

    // WebSocket: listen for QuizLive broadcast (backup for cross-device)
    document.addEventListener('DOMContentLoaded', function(){
        if (window.Echo) {
            window.Echo.channel('quiz-alerts')
                .listen('.quiz.live', function(e) {
                    showPopup(e.quiz);
                });
        }

        // Fetch all pending+upcoming quizzes and countdown to exact unlock_ms
        fetch('/quiz/live-check', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(function(r){ return r.json(); })
        .then(function(quizzes){
            quizzes.forEach(function(q) {
                if (isDismissed(q.id)) return;
                var now = Date.now();
                if (q.unlock_ms === 0 || now >= q.unlock_ms) {
                    // Already open — show immediately
                    showPopup(q);
                } else {
                    // Schedule to fire at exact unlock millisecond
                    var delay = q.unlock_ms - now;
                    setTimeout(function(){ showPopup(q); }, delay);
                }
            });
        })
        .catch(function(){});
    });
})();
</script>
@endif
@endauth

<script>
// ── Notification bell ────────────────────────────────────────────────
(function () {
    const btn      = document.getElementById('notifBtn');
    const dropdown = document.getElementById('notifDropdown');
    const badge    = document.getElementById('notifBadge');
    const list     = document.getElementById('notifList');
    const markAll  = document.getElementById('notifMarkAll');
    if (!btn) return;

    const raw = [
        @auth
        @foreach(auth()->user()->unreadNotifications()->latest()->take(20)->get() as $n)
        @php
            $d    = is_string($n->data) ? json_decode($n->data, true) : $n->data;
            $type = $d['type'] ?? 'info';
            $jsType = $type === 'warning' ? 'warning' : ($type === 'blacklist' ? 'danger' : 'info');
        @endphp
        { type: {{ Js::from($jsType) }}, text: {{ Js::from($d['message'] ?? '') }}, time: {{ Js::from($n->created_at->diffForHumans()) }}, unread: true },
        @endforeach
        @if(session('success'))
        { type:'success', text: {{ Js::from(session('success')) }}, time:'Just now', unread:true },
        @endif
        @if(session('info'))
        { type:'info',    text: {{ Js::from(session('info')) }},    time:'Just now', unread:true },
        @endif
        @if(session('error'))
        { type:'danger',  text: {{ Js::from(session('error')) }},   time:'Just now', unread:true },
        @endif
        @endauth
    ];

    // Mark DB notifications as read after rendering
    @auth
    @if(auth()->user()->unreadNotifications()->exists())
    fetch('{{ route("notifications.read") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
    });
    @endif
    @endauth

    const ICONS = { info:'fa-circle-info', success:'fa-circle-check', warning:'fa-triangle-exclamation', danger:'fa-circle-xmark' };

    function render(items) {
        if (!items.length) {
            list.innerHTML = '<div class="notif-empty"><i class="fa-solid fa-bell-slash"></i>No notifications yet</div>';
            badge.style.display = 'none';
            return;
        }
        const unread = items.filter(n => n.unread).length;
        badge.textContent    = unread > 9 ? '9+' : unread;
        badge.style.display  = unread ? 'flex' : 'none';
        list.innerHTML = items.map(n => `
            <div class="notif-item ${n.unread ? 'unread' : ''}">
                <div class="notif-icon ${n.type}"><i class="fa-solid ${ICONS[n.type] ?? 'fa-bell'}"></i></div>
                <div class="notif-body">
                    <div class="notif-text">${n.text}</div>
                    <div class="notif-time">${n.time}</div>
                </div>
                ${n.unread ? '<div class="notif-unread-dot"></div>' : ''}
            </div>`).join('');
    }

    render(raw);

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const open = dropdown.classList.toggle('open');
        btn.setAttribute('aria-expanded', open);
    });

    markAll.addEventListener('click', function () {
        raw.forEach(n => n.unread = false);
        render(raw);
    });

    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && e.target !== btn) {
            dropdown.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            dropdown.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });
}());
</script>
</body>
</html>
