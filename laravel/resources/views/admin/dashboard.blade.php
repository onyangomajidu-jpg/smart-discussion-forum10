@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="page-header">
    <div class="breadcrumb">
        <a href="{{ route('dashboard') }}">Home</a>
        <span class="sep">/</span> Admin Dashboard
    </div>
    <h1><i class="fa-solid fa-shield-halved"></i> Admin Dashboard</h1>
    <p>System overview and management controls</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="val">{{ $totalUsers ?? '—' }}</div>
        <div class="lbl">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">💬</div>
        <div class="val">{{ $totalTopics ?? '—' }}</div>
        <div class="lbl">Topics</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📝</div>
        <div class="val">{{ $totalPosts ?? '—' }}</div>
        <div class="lbl">Posts</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="val">{{ $totalQuizzes ?? '—' }}</div>
        <div class="lbl">Quizzes</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fa-solid fa-users"></i> User Management</h2>
    </div>
    <div class="card-body">
        <p style="color:var(--muted);font-size:13px;">Full user management panel coming in Week 2. You are logged in as Administrator.</p>
        <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
            <a href="{{ route('topics.index') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-comments"></i> View Topics</a>
            <a href="{{ route('lecturer.quizzes.index') }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-clipboard-list"></i> All Quizzes</a>
        </div>
    </div>
</div>
@endsection
