@extends('layouts.app')

@section('title', 'Lecturer Analytics — SmartForum')

@push('styles')
<style>
/* ── Hero ─────────────────────────────────────────────────────────── */
.lec-hero {
    background: linear-gradient(135deg,#0f172a 0%,#1e1b4b 50%,#312e81 100%);
    border-radius: 16px; padding: 32px 36px; margin-bottom: 28px;
    color: #fff; display: flex; align-items: center; justify-content: space-between;
    position: relative; overflow: hidden; box-shadow: 0 8px 32px rgba(15,23,42,.4);
}
.lec-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(99,102,241,.15); border-radius:50%;
}
.lec-hero::after {
    content:'\f201'; font-family:'Font Awesome 6 Free'; font-weight:900;
    position:absolute; right:36px; top:50%; transform:translateY(-50%);
    font-size:100px; opacity:.07;
}

/* ── KPI Cards ────────────────────────────────────────────────────── */
.lec-kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
.lec-kpi {
    background:#fff; border-radius:14px; padding:20px;
    border:1px solid #e2e8f0; box-shadow:0 2px 10px rgba(0,0,0,.04);
    border-top:3px solid var(--c,#6366f1); transition:all .2s;
}
.lec-kpi:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(99,102,241,.12); }
.lec-kpi-icon { font-size:20px; margin-bottom:10px; color:var(--c,#6366f1); }
.lec-kpi-val  { font-size:28px; font-weight:900; color:#0f172a; line-height:1; }
.lec-kpi-lbl  { font-size:11px; color:#64748b; font-weight:600; margin-top:4px; text-transform:uppercase; letter-spacing:.4px; }

/* ── Two-column layout ────────────────────────────────────────────── */
.lec-grid { display:grid; grid-template-columns:1fr 380px; gap:22px; align-items:start; }

/* ── Live Evaluation Roster ───────────────────────────────────────── */
.roster-card {
    background:#fff; border-radius:14px; border:1px solid #e2e8f0;
    box-shadow:0 2px 10px rgba(0,0,0,.04); overflow:hidden;
}
.roster-header {
    background:linear-gradient(135deg,#6366f1,#8b5cf6);
    padding:16px 22px; display:flex; align-items:center; justify-content:space-between;
}
.roster-header-title { color:#fff; font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px; }
.live-badge {
    background:rgba(255,255,255,.2); color:#fff; font-size:10px; font-weight:700;
    padding:3px 10px; border-radius:20px; border:1px solid rgba(255,255,255,.3);
    display:flex; align-items:center; gap:5px;
}
.live-dot { width:7px; height:7px; border-radius:50%; background:#4ade80; animation:pulse 1.5s infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

.roster-table { width:100%; border-collapse:collapse; font-size:13px; }
.roster-table thead th {
    background:#f8fafc; padding:11px 16px; text-align:left;
    font-weight:700; color:#64748b; border-bottom:2px solid #e2e8f0;
    font-size:11px; text-transform:uppercase; letter-spacing:.5px;
}
.roster-table tbody td { padding:12px 16px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.roster-table tbody tr:hover td { background:#fafbff; }
.roster-table tbody tr:last-child td { border-bottom:none; }

.student-avatar {
    width:34px; height:34px; border-radius:50%;
    background:linear-gradient(135deg,#6366f1,#8b5cf6);
    color:#fff; display:flex; align-items:center; justify-content:center;
    font-size:13px; font-weight:700; flex-shrink:0;
}
.score-pill {
    display:inline-flex; align-items:center; gap:4px;
    padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700;
}
.score-a { background:#d1fae5; color:#065f46; }
.score-b { background:#dbeafe; color:#1e40af; }
.score-c { background:#fef3c7; color:#92400e; }
.score-d { background:#ffedd5; color:#9a3412; }
.score-f { background:#fee2e2; color:#991b1b; }

/* ── Right Panel ──────────────────────────────────────────────────── */
.right-col { display:flex; flex-direction:column; gap:18px; }

/* Compliance Tracking Registry */
.compliance-card {
    background:#fff; border-radius:14px; border:1px solid #e2e8f0;
    box-shadow:0 2px 10px rgba(0,0,0,.04); overflow:hidden;
}
.compliance-header {
    background:linear-gradient(135deg,#0f172a,#1e1b4b);
    padding:14px 20px; display:flex; align-items:center; gap:8px;
    color:#fff; font-size:14px; font-weight:700;
}
.compliance-body { padding:16px; }
.compliance-row { margin-bottom:16px; }
.compliance-row:last-child { margin-bottom:0; }
.compliance-quiz-name {
    font-size:12px; font-weight:700; color:#0f172a;
    margin-bottom:6px; display:flex; align-items:center; justify-content:space-between;
}
.compliance-meta { font-size:11px; color:#64748b; margin-bottom:6px; display:flex; gap:12px; }
.compliance-track { height:10px; background:#e2e8f0; border-radius:5px; overflow:hidden; }
.compliance-fill  { height:100%; border-radius:5px; transition:width .8s; }
.compliance-rate  { font-size:11px; font-weight:700; margin-top:4px; text-align:right; }

/* Quiz Summary Card */
.quiz-summary-card {
    background:#fff; border-radius:14px; border:1px solid #e2e8f0;
    box-shadow:0 2px 10px rgba(0,0,0,.04); overflow:hidden;
}
.quiz-summary-header {
    background:linear-gradient(135deg,#f59e0b,#d97706);
    padding:14px 20px; color:#fff; font-size:14px; font-weight:700;
    display:flex; align-items:center; gap:8px;
}
.quiz-summary-body { padding:0; }
.quiz-summary-row {
    display:flex; align-items:center; gap:12px;
    padding:12px 16px; border-bottom:1px solid #f1f5f9; font-size:13px;
}
.quiz-summary-row:last-child { border-bottom:none; }
.quiz-summary-icon {
    width:36px; height:36px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    font-size:14px; flex-shrink:0;
}
.qi-draft     { background:#fef3c7; color:#92400e; }
.qi-published { background:#d1fae5; color:#065f46; }
.qi-closed    { background:#fee2e2; color:#991b1b; }

/* ── Roster search ──────────────────────────────────────────────── */
.roster-search-bar {
    padding:12px 16px; border-bottom:1px solid #e2e8f0;
    background:#fafbff;
}
.roster-search-input {
    width:100%; max-width:300px;
    padding:8px 12px 8px 34px;
    border:1.5px solid #e2e8f0; border-radius:8px;
    font-size:13px; font-family:inherit; color:#0f172a;
    background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") no-repeat 10px center;
    transition:border-color .2s, box-shadow .2s;
}
.roster-search-input:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.roster-count { font-size:11px; color:#94a3b8; margin-top:6px; }
.roster-no-results {
    text-align:center; padding:40px; color:#94a3b8; font-size:13px; display:none;
}

/* ── Compliance 100% badge ─────────────────────────────────────── */
.compliance-perfect {
    display:inline-flex; align-items:center; gap:5px;
    background:#d1fae5; color:#065f46;
    font-size:10px; font-weight:700; padding:3px 9px;
    border-radius:20px; border:1px solid #6ee7b7;
    flex-shrink:0;
}

/* ── Export Bar ───────────────────────────────────────────────────── */
.export-bar {
    background:#fff; border-radius:14px; padding:18px 22px;
    border:1px solid #e2e8f0; display:flex; align-items:center;
    justify-content:space-between; flex-wrap:wrap; gap:12px;
    margin-top:22px;
}
.export-btns { display:flex; gap:10px; flex-wrap:wrap; }
.export-btn {
    display:inline-flex; align-items:center; gap:7px;
    padding:9px 16px; border-radius:9px; font-size:12px; font-weight:600;
    text-decoration:none; transition:all .2s;
}
.export-btn:hover { transform:translateY(-1px); opacity:.9; }
.btn-pdf  { background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; }
.btn-csv  { background:linear-gradient(135deg,#10b981,#059669); color:#fff; }

@media(max-width:1024px) {
    .lec-kpi-grid { grid-template-columns:repeat(2,1fr); }
    .lec-grid     { grid-template-columns:1fr; }
}
@media(max-width:540px) {
    .lec-kpi-grid { grid-template-columns:1fr; }
}
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<div class="breadcrumb">
    <a href="{{ route('lecturer.dashboard') }}"><i class="fa-solid fa-house"></i> Dashboard</a>
    <span class="sep"><i class="fa-solid fa-chevron-right" style="font-size:9px"></i></span>
    <span>Analytics</span>
</div>

{{-- Hero --}}
<div class="lec-hero">
    <div>
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;opacity:.65;margin-bottom:6px">
            <i class="fa-solid fa-chart-mixed"></i> Lecturer Analytics
        </div>
        <div style="font-size:24px;font-weight:900;margin-bottom:6px">Evaluation & Compliance Dashboard</div>
        <div style="font-size:13px;opacity:.75">Live evaluation roster · Compliance tracking registry · {{ now()->format('d M Y') }}</div>
    </div>
    <div style="text-align:right;z-index:1">
        <div style="font-size:12px;opacity:.65;margin-bottom:4px">Lecturer</div>
        <div style="font-size:16px;font-weight:800">{{ $lecturer->name }}</div>
    </div>
</div>

{{-- KPI Cards --}}
<div class="lec-kpi-grid">
    <div class="lec-kpi" style="--c:#6366f1">
        <div class="lec-kpi-icon"><i class="fa-solid fa-clipboard-list"></i></div>
        <div class="lec-kpi-val">{{ $quizzes->count() }}</div>
        <div class="lec-kpi-lbl">Total Quizzes</div>
    </div>
    <div class="lec-kpi" style="--c:#10b981">
        <div class="lec-kpi-icon"><i class="fa-solid fa-users"></i></div>
        <div class="lec-kpi-val">{{ $totalStudents }}</div>
        <div class="lec-kpi-lbl">Total Students</div>
    </div>
    <div class="lec-kpi" style="--c:#f59e0b">
        <div class="lec-kpi-icon"><i class="fa-solid fa-paper-plane"></i></div>
        <div class="lec-kpi-val">{{ $totalSubmissions }}</div>
        <div class="lec-kpi-lbl">Total Submissions</div>
    </div>
    <div class="lec-kpi" style="--c:#8b5cf6">
        <div class="lec-kpi-icon"><i class="fa-solid fa-percent"></i></div>
        <div class="lec-kpi-val">{{ round($avgScore, 1) }}%</div>
        <div class="lec-kpi-lbl">Avg Score</div>
    </div>
</div>

{{-- Main Grid --}}
<div class="lec-grid">

    {{-- LEFT: Live Evaluation Roster --}}
    <div class="roster-card">
        <div class="roster-header">
            <div class="roster-header-title">
                <i class="fa-solid fa-users-viewfinder"></i> Live Evaluation Roster
            </div>
            <div class="live-badge">
                <div class="live-dot"></div> Live
            </div>
        </div>

        @if($roster->count())
        {{-- Search bar --}}
        <div class="roster-search-bar">
            <input type="text" id="rosterSearch" class="roster-search-input"
                   placeholder="Search by student name or email…"
                   oninput="filterRoster(this.value)">
            <div class="roster-count" id="rosterCount">{{ $roster->count() }} result{{ $roster->count() !== 1 ? 's' : '' }}</div>
        </div>
        <div style="overflow-x:auto">
            <table class="roster-table" id="rosterTable">
                <thead>
                    <tr>
                        <th><i class="fa-solid fa-user"></i> Student</th>
                        <th><i class="fa-solid fa-clipboard-list"></i> Quiz</th>
                        <th><i class="fa-solid fa-star"></i> Score</th>
                        <th><i class="fa-solid fa-trophy"></i> Grade</th>
                        <th><i class="fa-solid fa-circle-check"></i> Status</th>
                        <th><i class="fa-solid fa-clock"></i> Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roster as $rec)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div class="student-avatar">{{ strtoupper(substr($rec->user->name, 0, 1)) }}</div>
                                <div>
                                    <div style="font-weight:600;font-size:13px">{{ $rec->user->name }}</div>
                                    <div style="font-size:11px;color:#94a3b8">{{ $rec->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="font-size:12px;color:#374151;font-weight:500;max-width:160px">
                            <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $rec->quiz->title }}</div>
                        </td>
                        <td>
                            <span style="font-weight:800;font-size:14px">{{ $rec->score }}</span>
                            <span style="color:#94a3b8;font-size:12px"> / {{ $rec->max_score }}</span>
                            <div class="progress" style="width:70px;margin-top:4px">
                                <div class="progress-bar" style="width:{{ $rec->percentage }}%"></div>
                            </div>
                        </td>
                        <td>
                            @php
                                $g = $rec->grade;
                                $cls = match($g) { 'A' => 'score-a', 'B' => 'score-b', 'C' => 'score-c', 'D' => 'score-d', default => 'score-f' };
                            @endphp
                            <span class="score-pill {{ $cls }}">{{ $g }}</span>
                        </td>
                        <td>
                            @if($rec->percentage >= 50)
                                <span style="color:#065f46;font-weight:700;font-size:12px;display:flex;align-items:center;gap:4px">
                                    <i class="fa-solid fa-circle-check"></i> Pass
                                </span>
                            @else
                                <span style="color:#991b1b;font-weight:700;font-size:12px;display:flex;align-items:center;gap:4px">
                                    <i class="fa-solid fa-circle-xmark"></i> Fail
                                </span>
                            @endif
                        </td>
                        <td style="color:#64748b;font-size:12px">
                            {{ $rec->completed_at?->format('d M Y H:i') ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="roster-no-results" id="rosterNoResults">
                <i class="fa-solid fa-magnifying-glass" style="font-size:28px;opacity:.3;display:block;margin-bottom:10px"></i>
                No students match your search.
            </div>
        </div>
        @else
        <div style="text-align:center;padding:60px 40px;color:#94a3b8">
            <i class="fa-solid fa-inbox" style="font-size:48px;opacity:.3;display:block;margin-bottom:14px"></i>
            <p style="font-size:14px;font-weight:600">No submissions yet</p>
            <p style="font-size:12px;margin-top:4px">Student results will appear here once they submit quizzes.</p>
        </div>
        @endif
    </div>

    {{-- RIGHT: Compliance Tracking + Quiz Summary --}}
    <div class="right-col">

        {{-- Compliance Tracking Registry --}}
        <div class="compliance-card">
            <div class="compliance-header">
                <i class="fa-solid fa-shield-check"></i> Compliance Tracking Registry
            </div>
            <div class="compliance-body">
                @forelse($compliance as $c)
                <div class="compliance-row">
                    <div class="compliance-quiz-name">
                        <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $c['quiz']->title }}</span>
                        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;margin-left:8px">
                            @if($c['rate'] === 100)
                                <span class="compliance-perfect"><i class="fa-solid fa-circle-check"></i> 100%</span>
                            @endif
                            <span class="badge badge-{{ $c['quiz']->status }}">{{ ucfirst($c['quiz']->status) }}</span>
                        </div>
                    </div>
                    <div class="compliance-meta">
                        <span><i class="fa-solid fa-users" style="color:#6366f1"></i> {{ $c['group_size'] }} enrolled</span>
                        <span><i class="fa-solid fa-check" style="color:#10b981"></i> {{ $c['submitted'] }} submitted</span>
                        <span><i class="fa-solid fa-hourglass-half" style="color:#f59e0b"></i> {{ $c['pending'] }} pending</span>
                    </div>
                    <div class="compliance-track">
                        @php
                            $fillColor = $c['rate'] >= 80 ? '#10b981' : ($c['rate'] >= 50 ? '#f59e0b' : '#ef4444');
                        @endphp
                        <div class="compliance-fill" style="width:{{ $c['rate'] }}%;background:{{ $fillColor }}"></div>
                    </div>
                    <div class="compliance-rate" style="color:{{ $fillColor }}">{{ $c['rate'] }}% compliance</div>
                </div>
                @empty
                <div style="text-align:center;padding:30px;color:#94a3b8;font-size:13px">
                    <i class="fa-solid fa-clipboard-list" style="font-size:28px;opacity:.3;display:block;margin-bottom:10px"></i>
                    No quizzes created yet
                </div>
                @endforelse
            </div>
        </div>

        {{-- Quiz Summary --}}
        <div class="quiz-summary-card">
            <div class="quiz-summary-header">
                <i class="fa-solid fa-clipboard-list"></i> Quiz Summary
            </div>
            <div class="quiz-summary-body">
                @php
                    $draftCount     = $quizzes->where('status','draft')->count();
                    $publishedCount = $quizzes->where('status','published')->count();
                    $closedCount    = $quizzes->where('status','closed')->count();
                @endphp
                <div class="quiz-summary-row">
                    <div class="quiz-summary-icon qi-draft"><i class="fa-solid fa-pen"></i></div>
                    <div style="flex:1">
                        <div style="font-weight:600;font-size:13px">Draft Quizzes</div>
                        <div style="font-size:11px;color:#64748b">Not yet published</div>
                    </div>
                    <span style="font-weight:800;font-size:18px;color:#92400e">{{ $draftCount }}</span>
                </div>
                <div class="quiz-summary-row">
                    <div class="quiz-summary-icon qi-published"><i class="fa-solid fa-circle-play"></i></div>
                    <div style="flex:1">
                        <div style="font-weight:600;font-size:13px">Published Quizzes</div>
                        <div style="font-size:11px;color:#64748b">Active & available</div>
                    </div>
                    <span style="font-weight:800;font-size:18px;color:#065f46">{{ $publishedCount }}</span>
                </div>
                <div class="quiz-summary-row">
                    <div class="quiz-summary-icon qi-closed"><i class="fa-solid fa-lock"></i></div>
                    <div style="flex:1">
                        <div style="font-weight:600;font-size:13px">Closed Quizzes</div>
                        <div style="font-size:11px;color:#64748b">Past deadline</div>
                    </div>
                    <span style="font-weight:800;font-size:18px;color:#991b1b">{{ $closedCount }}</span>
                </div>
            </div>
        </div>



    </div>
</div>

{{-- Export Bar --}}
<div class="export-bar">
    <div>
        <div style="font-size:14px;font-weight:700;color:#0f172a"><i class="fa-solid fa-file-export" style="color:#6366f1"></i> Download Report</div>
        <div style="font-size:12px;color:#64748b;margin-top:2px">Export your analytics data</div>
    </div>
    <div class="export-btns">
        <a href="{{ route('reports.export', ['format' => 'pdf', 'type' => 'user']) }}" class="export-btn btn-pdf" target="_blank">
            <i class="fa-solid fa-file-pdf"></i> Export PDF
        </a>
        <a href="{{ route('reports.export', ['format' => 'csv', 'type' => 'user']) }}" class="export-btn btn-csv">
            <i class="fa-solid fa-file-csv"></i> Export CSV
        </a>
    </div>
</div>

@endsection

@push('scripts')
<script>
function filterRoster(query) {
    const q     = query.trim().toLowerCase();
    const rows  = document.querySelectorAll('#rosterTable tbody tr');
    let   shown = 0;
    rows.forEach(row => {
        const name  = row.querySelector('td:first-child')?.textContent.toLowerCase() ?? '';
        const match = !q || name.includes(q);
        row.style.display = match ? '' : 'none';
        if (match) shown++;
    });
    document.getElementById('rosterCount').textContent =
        shown + ' result' + (shown !== 1 ? 's' : '') + (q ? ' for "' + query.trim() + '"' : '');
    document.getElementById('rosterNoResults').style.display = shown === 0 ? 'block' : 'none';
}
</script>
@endpush
