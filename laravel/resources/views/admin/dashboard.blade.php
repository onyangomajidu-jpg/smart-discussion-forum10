@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<div class="page-header">
    <h1><i class="fa-solid fa-shield-halved"></i> Admin Dashboard</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
        <div class="val">{{ \App\Models\User::where('role','member')->count() }}</div>
        <div class="lbl">Members</div>
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
