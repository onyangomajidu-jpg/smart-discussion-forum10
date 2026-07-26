@extends('layouts.app')

@section('title', 'Dashboard — Discussion Hub')

@push('styles')
<style>
    :root {
        --primary:   #4f46e5;
        --primary-light: #e0e7ff;
        --primary-mid:   #c7d2fe;
        --danger:    #ef4444;
    }

    .dash-header { margin-bottom: 24px; }
    .dash-header h1 { font-size: 22px; font-weight: 800; letter-spacing: -.4px; color: var(--text); }
    .dash-header p  { color: var(--muted); font-size: 13px; margin-top: 4px; }

    /* ── Stat cards ── */
    .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    .dash-stat-card {
        background: #fff; border: 1px solid var(--primary-mid);
        border-radius: 12px; padding: 20px;
        border-left: 4px solid var(--primary);
        transition: box-shadow .2s, transform .2s;
        text-decoration: none; display: block;
    }
    .dash-stat-card:hover { box-shadow: 0 4px 16px rgba(79,70,229,.15); transform: translateY(-2px); }
    .dash-stat-card .stat-icon { font-size: 20px; margin-bottom: 10px; color: var(--primary); }
    .dash-stat-card .value { font-size: 28px; font-weight: 800; letter-spacing: -.5px; color: var(--primary); }
    .dash-stat-card .label { font-size: 11px; color: var(--muted); margin-top: 3px; font-weight: 500; }

    /* ── Panel grid ── */
    .panel-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }
    .panel {
        background: #fff; border: 1px solid #e2e8f0;
        border-radius: 12px; overflow: hidden;
        display: flex; flex-direction: column; min-height: 320px;
        transition: box-shadow .2s;
    }
    .panel:hover { box-shadow: 0 4px 16px rgba(0,0,0,.07); }
    .panel-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 18px; font-size: 14px; font-weight: 700;
    }
    .panel-header-left { display: flex; align-items: center; gap: 8px; }
    .panel-view-all {
        font-size: 11px; font-weight: 600;
        color: rgba(255,255,255,.85); text-decoration: none;
        background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.3);
        border-radius: 6px; padding: 3px 10px; transition: background .15s;
    }
    .panel-view-all:hover { background: rgba(255,255,255,.3); color: #fff; }

    .panel-topics  .panel-header { background: linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; }
    .panel-quiz    .panel-header { background: linear-gradient(135deg,#0ea5e9,#06b6d4); color:#fff; }
    .panel-stats   .panel-header { background: linear-gradient(135deg,#f59e0b,#ef4444); color:#fff; }
    .panel-account .panel-header { background: linear-gradient(135deg,#10b981,#059669); color:#fff; }
    .panel-topics  { border-top: 3px solid #8b5cf6; }
    .panel-quiz    { border-top: 3px solid #06b6d4; }
    .panel-stats   { border-top: 3px solid #ef4444; }
    .panel-account { border-top: 3px solid #10b981; }
    .panel-ai      { border-top: 3px solid #db2777; grid-column: 1 / -1; min-height: auto; }
    .panel-ai .panel-header { background: linear-gradient(135deg,#7c3aed,#db2777); color:#fff; }
    .ai-tag { display:inline-block; font-size:10px; font-weight:600; padding:2px 7px; border-radius:8px; background:#fdf4ff; color:#7c3aed; border:1px solid #e9d5ff; margin-right:3px; }
    .ai-score { font-size:10px; color:#db2777; font-weight:700; margin-left:auto; flex-shrink:0; }
    .panel-groups  { border-top: 3px solid #6366f1; }
    .panel-groups  .panel-header { background: linear-gradient(135deg,#6366f1,#4f46e5); color:#fff; }
    .group-chip {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 12px; border-radius: 10px;
        background: #f1f5f9; margin-bottom: 8px;
        font-size: 13px; font-weight: 600; color: #1e293b;
    }
    .group-chip:last-child { margin-bottom: 0; }
    .group-chip .g-icon {
        width: 34px; height: 34px; border-radius: 8px;
        background: linear-gradient(135deg,#6366f1,#8b5cf6);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 14px; flex-shrink: 0;
    }
    .group-chip .g-role {
        margin-left: auto; font-size: 11px; font-weight: 600;
        padding: 2px 9px; border-radius: 10px;
        background: #e0e7ff; color: #3730a3;
    }

    .panel-body { padding: 16px 18px; flex: 1; overflow-y: auto; }

    /* list rows */
    .list-row {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 0; border-bottom: 1px solid #e2e8f0; font-size: 13px;
    }
    .list-row:last-child { border-bottom: none; }
    .list-dot { font-size: 10px; font-weight: 700; flex-shrink: 0; }
    .dash-badge {
        margin-left: auto; font-size: 11px; font-weight: 600;
        padding: 2px 9px; border-radius: 10px;
        background: #f1f5f9; color: #64748b;
    }

    /* progress */
    .progress-row { margin-bottom: 18px; }
    .progress-row:last-child { margin-bottom: 0; }
    .progress-label { display: flex; justify-content: space-between; font-size: 12px; font-weight: 600; margin-bottom: 6px; }
    .progress-label span:last-child { color: #64748b; }
    .progress-track { height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
    .progress-fill  { height: 100%; border-radius: 4px; background: var(--primary); transition: width .7s cubic-bezier(.4,0,.2,1); }

    /* account info */
    .info-row {
        display: flex; align-items: center;
        padding: 11px 0; border-bottom: 1px solid #e0e7ff; font-size: 13px;
    }
    .info-row:last-of-type { border-bottom: none; }
    .info-row .key { color: var(--primary); font-weight: 600; width: 100px; flex-shrink: 0; font-size: 12px; }
    .role-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
    .role-member   { background: var(--primary-light); color: #3730a3; }
    .role-lecturer { background: var(--primary-light); color: #3730a3; }
    .role-admin    { background: #fee2e2; color: #991b1b; }

    .btn-signout-panel {
        display: inline-flex; align-items: center; gap: 6px; margin-top: 16px;
        background: var(--danger); color: #fff; border: none;
        border-radius: 8px; padding: 9px 20px; font-size: 13px; font-weight: 600;
        cursor: pointer; transition: opacity .2s; font-family: inherit;
    }
    .btn-signout-panel:hover { opacity: .88; }

    .empty-state { text-align: center; color: #64748b; font-size: 13px; padding: 36px 0; }
    .empty-state .icon { font-size: 28px; margin-bottom: 10px; }

    .flash { background: #d1fae5; color: #065f46; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; }

    /* ── Quiz announcements ── */
    .quiz-announce {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 1.5px solid #f59e0b;
        border-left: 5px solid #d97706;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 20px;
    }
    .quiz-announce-title {
        font-size: 13px; font-weight: 800; color: #92400e;
        display: flex; align-items: center; gap: 8px; margin-bottom: 10px;
    }
    .quiz-announce-item {
        display: flex; align-items: center; gap: 10px;
        padding: 8px 10px; background: rgba(255,255,255,.6);
        border-radius: 8px; margin-bottom: 6px; font-size: 13px;
    }
    .quiz-announce-item:last-child { margin-bottom: 0; }
    .quiz-announce-item .qa-name { flex: 1; font-weight: 600; color: #1e293b; }
    .quiz-announce-item .qa-group { font-size: 11px; color: #64748b; }
    .quiz-announce-item .qa-badge {
        font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 10px;
    }
    .qa-badge-open     { background: #d1fae5; color: #065f46; }
    .qa-badge-upcoming { background: #e0e7ff; color: #3730a3; }

    @media (max-width: 900px) {
        .stats-row { grid-template-columns: 1fr 1fr; }
        .panel-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 540px) {
        .stats-row { grid-template-columns: 1fr 1fr; }
        .dash-stat-card { padding: 14px; }
        .dash-stat-card .value { font-size: 22px; }
        .panel-body { padding: 12px 14px; }
        .panel-header { padding: 12px 14px; font-size: 13px; }
        .quiz-announce { padding: 10px 12px; }
        .quiz-announce-item { flex-wrap: wrap; gap: 6px; }
        .group-chip { padding: 8px 10px; }
    }
    @media (max-width: 360px) {
        .stats-row { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')

@if(session('success'))
    <div class="flash">{{ session('success') }}</div>
@endif

@php $hasAnnouncements = !empty($quizAnnouncements) && count($quizAnnouncements) > 0; @endphp
@if($hasAnnouncements)
<div class="quiz-announce" id="quizAnnounceBanner">
    <div class="quiz-announce-title">
        <i class="fa-solid fa-bell"></i> Quiz Announcements
    </div>
    @foreach($quizAnnouncements as $quiz)
    <div class="quiz-announce-item">
        <i class="fa-solid fa-clock" style="color:#6366f1;font-size:16px;flex-shrink:0"></i>
        <span class="qa-name">{{ $quiz->title }}</span>
        <span class="qa-group"><i class="fa-solid fa-users" style="font-size:10px"></i> {{ $quiz->group->name }}</span>
        @if($quiz->hard_deadline)
        <span class="qa-group"><i class="fa-solid fa-flag-checkered" style="font-size:10px"></i> Due {{ $quiz->hard_deadline->format('d M, H:i') }}</span>
        @endif
        <span class="qa-badge qa-badge-upcoming">Opens {{ $quiz->unlock_date->format('d M, H:i') }}</span>
    </div>
    @endforeach
</div>
@endif

<div class="dash-header">
    <h1>Dashboard</h1>
    <p>Welcome back, {{ auth()->user()->name }}! Here's your activity overview.</p>
</div>

{{-- Stat cards --}}
<div class="stats-row">
    <a href="{{ route('topics.index') }}" class="dash-stat-card">
        <div class="stat-icon"><i class="fa-solid fa-comments"></i></div>
        <div class="value" id="sc-topics">{{ $topicsJoined }}</div>
        <div class="label">Topics Joined</div>
    </a>
    <a href="{{ route('topics.index') }}" class="dash-stat-card">
        <div class="stat-icon"><i class="fa-solid fa-pen-to-square"></i></div>
        <div class="value" id="sc-posts">{{ $postsMade }}</div>
        <div class="label">Posts Made</div>
    </a>
    <a href="{{ auth()->user()->role === 'member' ? route('quizzes.index') : '#' }}" class="dash-stat-card">
        <div class="stat-icon"><i class="fa-solid fa-bullseye"></i></div>
        <div class="value" id="sc-attempts">{{ $quizAttempts }}</div>
        <div class="label">Quiz Attempts</div>
    </a>
    <a href="{{ auth()->user()->role === 'member' ? route('quizzes.index') : '#' }}" class="dash-stat-card">
        <div class="stat-icon"><i class="fa-solid fa-star"></i></div>
        <div class="value" id="sc-avg">{{ $avgScore !== null ? round($avgScore) . '%' : 'N/A' }}</div>
        <div class="label">Avg Quiz Score</div>
    </a>
</div>

{{-- Panel grid --}}
<div class="panel-grid">

    {{-- Row 1: Topic Participation + Quiz Attempts --}}
    <div class="panel panel-topics">
        <div class="panel-header">
            <div class="panel-header-left"><i class="fa-solid fa-comments"></i> Topic Participation</div>
            <a href="{{ route('topics.index') }}" class="panel-view-all">View All <i class="fa-solid fa-arrow-right" style="font-size:9px"></i></a>
        </div>
        <div class="panel-body">
            @forelse($recentTopics as $t)
                <a href="/topics/{{ $t->id }}" style="text-decoration:none;color:inherit">
                    <div class="list-row">
                        <span class="list-dot" style="color:#8b5cf6">●</span>
                        <span style="flex:1">{{ $t->title }}</span>
                        <i class="fa-solid fa-arrow-right" style="font-size:10px;color:#c4b5fd"></i>
                    </div>
                </a>
            @empty
                <div class="empty-state"><div class="icon">💬</div>No topic participation yet.</div>
            @endforelse
        </div>
    </div>

    <div class="panel panel-quiz">
        <div class="panel-header">
            <div class="panel-header-left"><i class="fa-solid fa-bullseye"></i> Quiz Attempts</div>
            <a href="{{ route('quizzes.index') }}" class="panel-view-all">View All <i class="fa-solid fa-arrow-right" style="font-size:9px"></i></a>
        </div>
        <div class="panel-body">
            @forelse($recentAttempts as $a)
                <a href="/quizzes" style="text-decoration:none;color:inherit">
                    <div class="list-row">
                        <span class="list-dot" style="color:#06b6d4">✓</span>
                        <span style="flex:1">{{ $a->title }}</span>
                        <span class="dash-badge">{{ $a->score !== null ? round($a->score).'%' : '—' }}</span>
                    </div>
                </a>
            @empty
                <div class="empty-state"><div class="icon">📋</div>No quiz attempts yet.</div>
            @endforelse
        </div>
    </div>

    {{-- Panel 3: My Groups --}}
    <div class="panel panel-groups">
        <div class="panel-header">
            <div class="panel-header-left"><i class="fa-solid fa-users"></i> My Groups</div>
            @if(auth()->user()->isMember())
            <a href="{{ route('groups.index') }}" class="panel-view-all">Browse <i class="fa-solid fa-arrow-right" style="font-size:9px"></i></a>
            @endif
        </div>
        <div class="panel-body">
            @if($groups->isEmpty())
                <div class="empty-state"><div class="icon">👥</div>You are not assigned to any group yet.</div>
            @else
                @foreach($groups as $group)
                <div class="group-chip">
                    <div class="g-icon"><i class="fa-solid fa-users"></i></div>
                    <span>{{ $group->name }}</span>
                    <span class="g-role">{{ ucfirst($group->pivot->role ?? 'member') }}</span>
                </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Panel 4: Statistics Review --}}
    <div class="panel panel-stats">
        <div class="panel-header">
            <div class="panel-header-left"><i class="fa-solid fa-chart-bar"></i> Statistics Review</div>
        </div>
        <div class="panel-body">
            <div class="progress-row">
                <div class="progress-label"><span>Forum Engagement</span><span>{{ $engPct }}%</span></div>
                <div class="progress-track"><div class="progress-fill" style="width:{{ $engPct }}%"></div></div>
            </div>
            <div class="progress-row">
                <div class="progress-label"><span>Quiz Completion</span><span>{{ $compPct }}%</span></div>
                <div class="progress-track"><div class="progress-fill" style="width:{{ $compPct }}%"></div></div>
            </div>
            <div class="progress-row">
                <div class="progress-label"><span>Average Score</span><span>{{ $avgScore !== null ? $avgPct.'%' : 'N/A' }}</span></div>
                <div class="progress-track"><div class="progress-fill" style="width:{{ $avgPct }}%"></div></div>
            </div>
        </div>
    </div>

    {{-- Row 3: AI Recommendations (full width) --}}
    <div class="panel panel-ai">
        <div class="panel-header">
            <div class="panel-header-left"><i class="fa-solid fa-robot"></i> AI Recommended Topics</div>
        </div>
        <div class="panel-body">
            @forelse($recommendations as $r)
                <a href="/topics/{{ $r['id'] }}" style="text-decoration:none;color:inherit">
                    <div class="list-row" style="flex-wrap:wrap;gap:6px">
                        <span class="list-dot" style="color:#db2777">★</span>
                        <span style="flex:1;min-width:120px">{{ $r['title'] }}</span>
                        <span>
                            @foreach($r['tags'] as $tag)
                                <span class="ai-tag">{{ $tag }}</span>
                            @endforeach
                        </span>
                        <span class="ai-score">{{ round($r['score'] * 100) }}% match</span>
                    </div>
                </a>
            @empty
                <div class="empty-state"><div class="icon">🤖</div>No recommendations yet — start participating in topics!</div>
            @endforelse
        </div>
    </div>

</div>


@endsection
