@extends('layouts.app')

@section('title', 'Analytics — SmartForum')

@push('styles')
<style>
/* ── Hero ─────────────────────────────────────────────────────────── */
.analytics-hero {
    background: linear-gradient(135deg,#6366f1 0%,#8b5cf6 60%,#a78bfa 100%);
    border-radius: 16px; padding: 32px 36px; margin-bottom: 28px;
    color: #fff; position: relative; overflow: hidden;
    box-shadow: 0 8px 32px rgba(99,102,241,.35);
    display: flex; align-items: center; justify-content: space-between;
}
.analytics-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.07); border-radius:50%;
}
.analytics-hero::after {
    content:'\f080'; font-family:'Font Awesome 6 Free'; font-weight:900;
    position:absolute; right:36px; top:50%; transform:translateY(-50%);
    font-size:100px; opacity:.08;
}
.hero-title { font-size:26px; font-weight:900; margin-bottom:6px; }
.hero-sub   { font-size:14px; opacity:.8; }

/* ── Stat Cards ───────────────────────────────────────────────────── */
.kpi-grid {
    display: grid; grid-template-columns: repeat(4,1fr);
    gap: 16px; margin-bottom: 24px;
}
.kpi-card {
    background:#fff; border-radius:14px; padding:22px 20px;
    border:1px solid #e2e8f0; box-shadow:0 2px 10px rgba(0,0,0,.04);
    display:flex; align-items:center; gap:16px;
    transition:all .2s; text-decoration:none; color:inherit;
}
.kpi-card:hover { transform:translateY(-3px); box-shadow:0 8px 28px rgba(99,102,241,.15); border-color:#c7d2fe; }
.kpi-icon {
    width:52px; height:52px; border-radius:14px;
    display:flex; align-items:center; justify-content:center;
    font-size:22px; flex-shrink:0;
}
.kpi-icon.purple { background:linear-gradient(135deg,#ede9fe,#ddd6fe); color:#6d28d9; }
.kpi-icon.blue   { background:linear-gradient(135deg,#dbeafe,#bfdbfe); color:#1d4ed8; }
.kpi-icon.green  { background:linear-gradient(135deg,#d1fae5,#a7f3d0); color:#065f46; }
.kpi-icon.amber  { background:linear-gradient(135deg,#fef3c7,#fde68a); color:#92400e; }
.kpi-value { font-size:30px; font-weight:900; color:#0f172a; line-height:1; }
.kpi-label { font-size:12px; color:#64748b; font-weight:600; margin-top:4px; text-transform:uppercase; letter-spacing:.4px; }

/* ── Charts Grid ──────────────────────────────────────────────────── */
.charts-row { display:grid; grid-template-columns:1.6fr 1fr; gap:20px; margin-bottom:24px; }
.chart-card {
    background:#fff; border-radius:14px; padding:22px;
    border:1px solid #e2e8f0; box-shadow:0 2px 10px rgba(0,0,0,.04);
}
.chart-card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; }
.chart-card-title  { font-size:14px; font-weight:700; color:#0f172a; }
.chart-card-sub    { font-size:12px; color:#64748b; margin-top:2px; }

/* Line Chart */
.line-chart-wrap { position:relative; height:220px; overflow:hidden; }
.line-chart-wrap svg { width:100%; height:100%; }

/* Pie Chart */
.pie-wrap { display:flex; flex-direction:column; align-items:center; }
.pie-svg  { width:180px; height:180px; }
.pie-legend { width:100%; margin-top:16px; }
.pie-legend-item {
    display:flex; align-items:center; gap:8px;
    padding:6px 0; border-bottom:1px solid #f1f5f9;
    font-size:12px;
}
.pie-legend-item:last-child { border-bottom:none; }
.pie-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.pie-legend-label { flex:1; color:#374151; font-weight:500; }
.pie-legend-val   { font-weight:700; color:#0f172a; }

/* ── Bottom Row ───────────────────────────────────────────────────── */
.bottom-row { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; }

/* Progress Panel */
.progress-panel { background:#fff; border-radius:14px; padding:22px; border:1px solid #e2e8f0; }
.progress-panel-title { font-size:14px; font-weight:700; color:#0f172a; margin-bottom:18px; }
.prog-row { margin-bottom:16px; }
.prog-row:last-child { margin-bottom:0; }
.prog-label { display:flex; justify-content:space-between; font-size:12px; font-weight:600; margin-bottom:6px; }
.prog-label span:last-child { color:#64748b; }
.prog-track { height:10px; background:#e2e8f0; border-radius:5px; overflow:hidden; }
.prog-fill  { height:100%; border-radius:5px; transition:width .8s cubic-bezier(.4,0,.2,1); }

/* Quick Stats Panel */
.quick-stats { background:#fff; border-radius:14px; padding:22px; border:1px solid #e2e8f0; }
.quick-stats-title { font-size:14px; font-weight:700; color:#0f172a; margin-bottom:18px; }
.qs-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:10px 0; border-bottom:1px solid #f1f5f9; font-size:13px;
}
.qs-row:last-child { border-bottom:none; }
.qs-label { color:#64748b; display:flex; align-items:center; gap:7px; }
.qs-val   { font-weight:700; color:#0f172a; }

/* ── Export Bar ───────────────────────────────────────────────────── */
.export-bar {
    background:#fff; border-radius:14px; padding:20px 24px;
    border:1px solid #e2e8f0; display:flex; align-items:center;
    justify-content:space-between; flex-wrap:wrap; gap:12px;
}
.export-bar-title { font-size:14px; font-weight:700; color:#0f172a; }
.export-bar-sub   { font-size:12px; color:#64748b; margin-top:2px; }
.export-btns { display:flex; gap:10px; flex-wrap:wrap; }
.export-btn {
    display:inline-flex; align-items:center; gap:7px;
    padding:10px 18px; border-radius:9px; font-size:13px; font-weight:600;
    text-decoration:none; transition:all .2s; border:none; cursor:pointer;
}
.export-btn:hover { transform:translateY(-2px); opacity:.9; }
.btn-pdf  { background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; box-shadow:0 4px 12px rgba(239,68,68,.3); }
.btn-csv  { background:linear-gradient(135deg,#10b981,#059669); color:#fff; box-shadow:0 4px 12px rgba(16,185,129,.3); }
.btn-json { background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; box-shadow:0 4px 12px rgba(245,158,11,.3); }

/* ── Responsive ───────────────────────────────────────────────────── */
@media(max-width:1024px) {
    .kpi-grid    { grid-template-columns:repeat(2,1fr); }
    .charts-row  { grid-template-columns:1fr; }
    .bottom-row  { grid-template-columns:1fr; }
}
@media(max-width:540px) {
    .kpi-grid { grid-template-columns:1fr; }
}
</style>
@endpush

@section('content')

{{-- Hero --}}
<div class="analytics-hero">
    <div>
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;opacity:.65;margin-bottom:6px">
            <i class="fa-solid fa-chart-mixed"></i> Statistics Screen
        </div>
        <div class="hero-title">Analytics Dashboard</div>
        <div class="hero-sub">Your performance overview — {{ now()->format('d M Y') }}</div>
    </div>
    <div style="text-align:right;z-index:1">
        <div style="font-size:12px;opacity:.65;margin-bottom:4px">Logged in as</div>
        <div style="font-size:16px;font-weight:800">{{ $user->name }}</div>
        <div style="font-size:11px;opacity:.65;margin-top:2px">{{ ucfirst($user->role) }}</div>
    </div>
</div>

{{-- KPI Cards (Fig 6.5 — stat cards) --}}
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon purple"><i class="fa-solid fa-calculator"></i></div>
        <div>
            <div class="kpi-value">{{ $stats['quiz']['total_attempts'] }}</div>
            <div class="kpi-label">Total Quizzes Taken</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon blue"><i class="fa-solid fa-percent"></i></div>
        <div>
            <div class="kpi-value">{{ $stats['quiz']['average_score'] }}%</div>
            <div class="kpi-label">Average Quiz Score</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div>
            <div class="kpi-value">{{ $stats['quiz']['completion_rate'] }}%</div>
            <div class="kpi-label">Completion Rate</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon amber"><i class="fa-solid fa-comments"></i></div>
        <div>
            <div class="kpi-value">{{ $stats['forum']['topics_joined'] }}</div>
            <div class="kpi-label">Topics Joined</div>
        </div>
    </div>
</div>

{{-- Charts Row --}}
<div class="charts-row">

    {{-- Weekly Performance Trend Line Chart --}}
    <div class="chart-card">
        <div class="chart-card-header">
            <div>
                <div class="chart-card-title"><i class="fa-solid fa-chart-line" style="color:#6366f1"></i> Weekly Performance Trend</div>
                <div class="chart-card-sub">Last 7 days average quiz score</div>
            </div>
        </div>
        <div class="line-chart-wrap">
            @php
                $weekly = $stats['weekly_performance'] ?? [];
                $count  = count($weekly);
            @endphp
            @if($count > 0)
            <svg viewBox="0 0 600 220" preserveAspectRatio="none" id="lineChart">
                <defs>
                    <linearGradient id="lineGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#6366f1" stop-opacity=".25"/>
                        <stop offset="100%" stop-color="#6366f1" stop-opacity="0"/>
                    </linearGradient>
                </defs>
                @php
                    $maxVal = max(array_column($weekly, 'avg_score')) ?: 100;
                    $maxVal = max($maxVal, 100);
                    $pts = [];
                    foreach ($weekly as $i => $w) {
                        $x = $count > 1 ? ($i / ($count - 1)) * 560 + 20 : 300;
                        $y = 200 - (($w['avg_score'] / $maxVal) * 180);
                        $pts[] = [$x, $y, $w];
                    }
                    $polyline = implode(' ', array_map(fn($p) => "{$p[0]},{$p[1]}", $pts));
                    $area = $polyline . " {$pts[count($pts)-1][0]},200 {$pts[0][0]},200";
                @endphp
                {{-- Grid lines --}}
                @foreach([0,25,50,75,100] as $g)
                    @php $gy = 200 - ($g / $maxVal) * 180; @endphp
                    <line x1="20" y1="{{ $gy }}" x2="580" y2="{{ $gy }}" stroke="#e2e8f0" stroke-width="1"/>
                    <text x="8" y="{{ $gy + 4 }}" font-size="9" fill="#94a3b8" text-anchor="end">{{ $g }}%</text>
                @endforeach
                {{-- Area fill --}}
                <polygon points="{{ $area }}" fill="url(#lineGrad)"/>
                {{-- Line --}}
                <polyline points="{{ $polyline }}" fill="none" stroke="#6366f1" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
                {{-- Points --}}
                @foreach($pts as [$x, $y, $w])
                    <circle cx="{{ $x }}" cy="{{ $y }}" r="5" fill="#6366f1" stroke="#fff" stroke-width="2"/>
                    <text x="{{ $x }}" y="{{ $y - 10 }}" font-size="9" fill="#6366f1" text-anchor="middle" font-weight="700">{{ $w['avg_score'] }}%</text>
                    <text x="{{ $x }}" y="215" font-size="9" fill="#94a3b8" text-anchor="middle">{{ \Carbon\Carbon::parse($w['date'])->format('M d') }}</text>
                @endforeach
            </svg>
            @else
            <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#94a3b8;flex-direction:column;gap:10px">
                <i class="fa-solid fa-chart-line" style="font-size:36px;opacity:.3"></i>
                <span style="font-size:13px">No performance data yet</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Quiz Subject Allocation Pie Chart --}}
    <div class="chart-card">
        <div class="chart-card-header">
            <div>
                <div class="chart-card-title"><i class="fa-solid fa-chart-pie" style="color:#8b5cf6"></i> Subject Allocation</div>
                <div class="chart-card-sub">Quiz attempts by subject</div>
            </div>
        </div>
        <div class="pie-wrap">
            @php
                $subjects = $stats['subject_allocation'] ?? [];
                $colors   = ['#6366f1','#8b5cf6','#10b981','#f59e0b','#ef4444','#3b82f6','#ec4899'];
                $total    = $subjects->sum('attempts') ?: 1;
                $offset   = 0;
                $r = 80; $cx = 90; $cy = 90;
            @endphp
            @if(count($subjects) > 0)
            <svg class="pie-svg" viewBox="0 0 180 180">
                @foreach($subjects as $i => $sa)
                    @php
                        $pct   = $sa['attempts'] / $total;
                        $angle = $pct * 360;
                        $color = $colors[$i % count($colors)];
                        $startRad = deg2rad($offset - 90);
                        $endRad   = deg2rad($offset + $angle - 90);
                        $x1 = $cx + $r * cos($startRad);
                        $y1 = $cy + $r * sin($startRad);
                        $x2 = $cx + $r * cos($endRad);
                        $y2 = $cy + $r * sin($endRad);
                        $large = $angle > 180 ? 1 : 0;
                        $offset += $angle;
                    @endphp
                    <path d="M{{ $cx }},{{ $cy }} L{{ round($x1,2) }},{{ round($y1,2) }} A{{ $r }},{{ $r }} 0 {{ $large }},1 {{ round($x2,2) }},{{ round($y2,2) }} Z"
                          fill="{{ $color }}" stroke="#fff" stroke-width="2"/>
                @endforeach
                <circle cx="{{ $cx }}" cy="{{ $cy }}" r="40" fill="#fff"/>
                <text x="{{ $cx }}" y="{{ $cy - 6 }}" text-anchor="middle" font-size="11" font-weight="700" fill="#0f172a">{{ $total }}</text>
                <text x="{{ $cx }}" y="{{ $cy + 10 }}" text-anchor="middle" font-size="9" fill="#64748b">attempts</text>
            </svg>
            <div class="pie-legend">
                @foreach($subjects as $i => $sa)
                <div class="pie-legend-item">
                    <div class="pie-dot" style="background:{{ $colors[$i % count($colors)] }}"></div>
                    <span class="pie-legend-label">{{ $sa['subject'] }}</span>
                    <span class="pie-legend-val">{{ $sa['attempts'] }} ({{ round(($sa['attempts']/$total)*100) }}%)</span>
                </div>
                @endforeach
            </div>
            @else
            <div style="display:flex;align-items:center;justify-content:center;height:180px;color:#94a3b8;flex-direction:column;gap:10px">
                <i class="fa-solid fa-chart-pie" style="font-size:36px;opacity:.3"></i>
                <span style="font-size:13px">No subject data yet</span>
            </div>
            @endif
        </div>
    </div>

</div>

{{-- Bottom Row --}}
<div class="bottom-row">

    {{-- Progress Summary --}}
    <div class="progress-panel">
        <div class="progress-panel-title"><i class="fa-solid fa-bars-progress" style="color:#6366f1"></i> Progress Summary</div>

        <div class="prog-row">
            <div class="prog-label">
                <span>Quiz Completion Rate</span>
                <span>{{ $stats['quiz']['completion_rate'] }}%</span>
            </div>
            <div class="prog-track">
                <div class="prog-fill" style="width:{{ $stats['quiz']['completion_rate'] }}%;background:linear-gradient(90deg,#10b981,#059669)"></div>
            </div>
        </div>

        <div class="prog-row">
            <div class="prog-label">
                <span>Average Score</span>
                <span>{{ $stats['quiz']['average_score'] }}%</span>
            </div>
            <div class="prog-track">
                <div class="prog-fill" style="width:{{ $stats['quiz']['average_score'] }}%;background:linear-gradient(90deg,#6366f1,#8b5cf6)"></div>
            </div>
        </div>

        <div class="prog-row">
            <div class="prog-label">
                <span>Best Score</span>
                <span>{{ $stats['quiz']['max_score'] }}%</span>
            </div>
            <div class="prog-track">
                <div class="prog-fill" style="width:{{ $stats['quiz']['max_score'] }}%;background:linear-gradient(90deg,#f59e0b,#d97706)"></div>
            </div>
        </div>

        <div class="prog-row">
            <div class="prog-label">
                <span>Forum Engagement</span>
                <span>{{ $stats['forum']['total_posts'] }} posts</span>
            </div>
            <div class="prog-track">
                <div class="prog-fill" style="width:{{ min($stats['forum']['total_posts'] * 5, 100) }}%;background:linear-gradient(90deg,#3b82f6,#1d4ed8)"></div>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="quick-stats">
        <div class="quick-stats-title"><i class="fa-solid fa-chart-simple" style="color:#6366f1"></i> Quick Stats</div>

        <div class="qs-row">
            <span class="qs-label"><i class="fa-solid fa-calculator" style="color:#6366f1;width:14px"></i> Total Quizzes Taken</span>
            <span class="qs-val">{{ $stats['quiz']['total_attempts'] }}</span>
        </div>
        <div class="qs-row">
            <span class="qs-label"><i class="fa-solid fa-arrow-up" style="color:#10b981;width:14px"></i> Best Score</span>
            <span class="qs-val" style="color:#10b981">{{ $stats['quiz']['max_score'] }}%</span>
        </div>
        <div class="qs-row">
            <span class="qs-label"><i class="fa-solid fa-arrow-down" style="color:#ef4444;width:14px"></i> Lowest Score</span>
            <span class="qs-val" style="color:#ef4444">{{ $stats['quiz']['min_score'] }}%</span>
        </div>
        <div class="qs-row">
            <span class="qs-label"><i class="fa-solid fa-pen-to-square" style="color:#8b5cf6;width:14px"></i> Total Posts</span>
            <span class="qs-val">{{ $stats['forum']['total_posts'] }}</span>
        </div>
        <div class="qs-row">
            <span class="qs-label"><i class="fa-solid fa-comments" style="color:#3b82f6;width:14px"></i> Topics Joined</span>
            <span class="qs-val">{{ $stats['forum']['topics_joined'] }}</span>
        </div>
        <div class="qs-row">
            <span class="qs-label"><i class="fa-solid fa-layer-group" style="color:#f59e0b;width:14px"></i> Subjects Covered</span>
            <span class="qs-val">{{ count($stats['subject_allocation']) }}</span>
        </div>
    </div>

</div>

{{-- Export Bar — Download Report Button --}}
<div class="export-bar">
    <div>
        <div class="export-bar-title"><i class="fa-solid fa-file-export" style="color:#6366f1"></i> Download Report</div>
        <div class="export-bar-sub">Export your analytics data in your preferred format</div>
    </div>
    <div class="export-btns">
        <a href="{{ route('reports.export', ['format' => 'pdf', 'type' => 'user']) }}" class="export-btn btn-pdf" target="_blank">
            <i class="fa-solid fa-file-pdf"></i> Export PDF
        </a>
        <a href="{{ route('reports.export', ['format' => 'csv', 'type' => 'user']) }}" class="export-btn btn-csv">
            <i class="fa-solid fa-file-csv"></i> Export CSV
        </a>
        <a href="{{ route('reports.export', ['format' => 'json', 'type' => 'user']) }}" class="export-btn btn-json">
            <i class="fa-solid fa-file-code"></i> Export JSON
        </a>
    </div>
</div>

@endsection
