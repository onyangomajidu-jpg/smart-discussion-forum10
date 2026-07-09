<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Smart Discussion Forum')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }
    </style>
</head>
<body>

<nav class="topnav">
    <a href="{{ auth()->check() && auth()->user()->isLecturer() ? route('lecturer.dashboard') : (auth()->check() && auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard')) }}" class="topnav-brand">
        <div class="brand-icon"><i class="fa-solid fa-graduation-cap"></i></div>
        <div>
            <div class="name">SmartForum</div>
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
        {{-- Sidebar user mini-profile --}}
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name">{{ auth()->user()->name }}</div>
                <div class="sidebar-user-role">
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

        <div class="sidebar-divider"></div>

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
