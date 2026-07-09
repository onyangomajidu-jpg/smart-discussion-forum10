@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<div class="page-header">
    <h1><i class="fa-solid fa-shield-halved"></i> Admin Dashboard</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
<<<<<<< HEAD
        <div class="val">{{ $totalUsers ?? \App\Models\User::where('role','member')->count() }}</div>
        <div class="lbl">Members</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-comments"></i></div>
        <div class="val">{{ $totalTopics ?? '—' }}</div>
        <div class="lbl">Topics</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-pen-to-square"></i></div>
        <div class="val">{{ $totalPosts ?? '—' }}</div>
        <div class="lbl">Posts</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-clipboard-list"></i></div>
        <div class="val">{{ $totalQuizzes ?? '—' }}</div>
        <div class="lbl">Quizzes</div>
=======
        <div class="val">{{ \App\Models\User::where('role','member')->count() }}</div>
        <div class="lbl">Members</div>
>>>>>>> 5cbd8be8e44d54b17b700077709c132401e417d7
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="val">{{ \App\Models\Warning::whereNull('resolved_at')->count() }}</div>
        <div class="lbl">Open Warnings</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-ban"></i></div>
        <div class="val">{{ \App\Models\Blacklist::where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at','>',now()))->count() }}</div>
        <div class="lbl">Active Bans</div>
    </div>
</div>

<div style="display:flex;gap:12px;flex-wrap:wrap">
    <a href="{{ route('admin.warnings.index') }}" class="btn btn-warning"><i class="fa-solid fa-triangle-exclamation"></i> Warning Registry</a>
    <a href="{{ route('admin.blacklists.index') }}" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Blacklist Log</a>
</div>
@endsection
