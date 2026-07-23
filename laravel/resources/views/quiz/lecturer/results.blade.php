@extends('layouts.app')

@section('title', 'Results — ' . $quiz->title)

@push('styles')
<style>
.results-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
    border-radius: 16px;
    padding: 30px 36px;
    margin-bottom: 28px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 8px 32px rgba(15,23,42,.4);
    position: relative;
    overflow: hidden;
}
.results-hero::before {
    content: '';
    position: absolute; top: -80px; right: -80px;
    width: 260px; height: 260px;
    background: rgba(99,102,241,.15);
    border-radius: 50%;
}
.results-hero::after {
    content: '\f080';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    right: 36px; top: 50%;
    transform: translateY(-50%);
    font-size: 90px;
    opacity: .08;
}
@media(max-width:768px) {
    .results-hero { flex-direction:column; align-items:flex-start; gap:14px; padding:20px 18px; }
    .results-hero::after { display:none; }
    .results-main-grid { grid-template-columns:1fr !important; }
}

.grade-bar-row { margin-bottom: 14px; }
.grade-bar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.grade-pill { padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 800; }
.grade-pill-A { background: #d1fae5; color: #065f46; }
.grade-pill-B { background: #dbeafe; color: #1e40af; }
.grade-pill-C { background: #fef3c7; color: #92400e; }
.grade-pill-D { background: #ffedd5; color: #9a3412; }
.grade-pill-F { background: #fee2e2; color: #991b1b; }

.rank-badge {
    width: 28px; height: 28px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 800; flex-shrink: 0;
}
.rank-1 { background: linear-gradient(135deg,#f59e0b,#d97706); color: #fff; box-shadow: 0 3px 10px rgba(245,158,11,.4); }
.rank-2 { background: linear-gradient(135deg,#94a3b8,#64748b); color: #fff; }
.rank-3 { background: linear-gradient(135deg,#cd7c2f,#b45309); color: #fff; }
.rank-n { background: #f1f5f9; color: #64748b; }

.pass-fail-card {
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    flex: 1;
}
.pass-card { background: linear-gradient(135deg,#d1fae5,#a7f3d0); border: 1.5px solid #6ee7b7; }
.fail-card { background: linear-gradient(135deg,#fee2e2,#fecaca); border: 1.5px solid #fca5a5; }
.pass-fail-num { font-size: 36px; font-weight: 900; line-height: 1; margin-bottom: 4px; }
.pass-num { color: #065f46; }
.fail-num { color: #991b1b; }
.pass-fail-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
.pass-label { color: #065f46; }
.fail-label { color: #991b1b; }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('lecturer.dashboard') }}"><i class="fa-solid fa-house"></i> Dashboard</a>
    <span class="sep"><i class="fa-solid fa-chevron-right" style="font-size:9px"></i></span>
    <a href="{{ route('lecturer.quizzes.show', $quiz) }}">{{ $quiz->title }}</a>
    <span class="sep"><i class="fa-solid fa-chevron-right" style="font-size:9px"></i></span>
    <span>Results</span>
</div>

<div class="results-hero">
    <div>
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;opacity:.6;margin-bottom:6px">
            <i class="fa-solid fa-chart-bar"></i> Assessment Results
        </div>
        <div style="font-size:22px;font-weight:900;margin-bottom:6px">{{ $quiz->title }}</div>
        <div style="font-size:13px;opacity:.7;display:flex;align-items:center;gap:14px">
            <span><i class="fa-solid fa-users"></i> {{ $quiz->group->name }}</span>
            <span><i class="fa-solid fa-star"></i> {{ $quiz->totalMarks() }} total marks</span>
        </div>
    </div>
    <a href="{{ route('lecturer.quizzes.show', $quiz) }}" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Back to Quiz
    </a>
</div>

@php
    $total     = $records->count();
    $avgScore  = $total ? round($records->avg('score'), 1) : 0;
    $avgPct    = $total ? round($records->avg('percentage'), 1) : 0;
    $passCount = $records->where('percentage', '>=', 50)->count();
    $passRate  = $total ? round(($passCount / $total) * 100) : 0;
    $highest   = $records->max('score') ?? 0;
    $grades    = $records->groupBy('grade')->map->count();
@endphp

<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-users" style="color:#6366f1"></i></div>
        <div class="val">{{ $total }}</div>
        <div class="lbl">Submissions</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-calculator" style="color:#8b5cf6"></i></div>
        <div class="val">{{ $avgScore }}</div>
        <div class="lbl">Avg Score</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-percent" style="color:#3b82f6"></i></div>
        <div class="val">{{ $avgPct }}%</div>
        <div class="lbl">Avg %</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-chart-line" style="color:#10b981"></i></div>
        <div class="val">{{ $passRate }}%</div>
        <div class="lbl">Pass Rate</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-trophy" style="color:#f59e0b"></i></div>
        <div class="val">{{ $highest }}</div>
        <div class="lbl">Highest</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 290px;gap:22px;align-items:start" class="results-main-grid">

    {{-- Results Table --}}
    <div class="card">
        <div class="card-header">
            <h2><i class="fa-solid fa-ranking-star"></i> Student Rankings</h2>
            <span style="font-size:12px;color:#64748b;font-weight:600">Sorted by score</span>
        </div>
        @if($records->count())
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><i class="fa-solid fa-hashtag"></i> Rank</th>
                        <th><i class="fa-solid fa-user"></i> Student</th>
                        <th><i class="fa-solid fa-star"></i> Score</th>
                        <th><i class="fa-solid fa-percent"></i> %</th>
                        <th><i class="fa-solid fa-trophy"></i> Grade</th>
                        <th><i class="fa-solid fa-circle-check"></i> Status</th>
                        <th><i class="fa-solid fa-clock"></i> Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $i => $rec)
                    <tr>
                        <td>
                            <div class="rank-badge {{ $i < 3 ? 'rank-'.($i+1) : 'rank-n' }}">
                                @if($i === 0) <i class="fa-solid fa-crown"></i>
                                @else {{ $i + 1 }}
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0">
                                    {{ strtoupper(substr($rec->user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:13px">{{ $rec->user->name }}</div>
                                    <div style="font-size:11px;color:#94a3b8">{{ $rec->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-weight:800;font-size:15px;color:#0f172a">{{ $rec->score }}</span>
                            <span style="color:#94a3b8"> / {{ $rec->max_score }}</span>
                            <div class="progress" style="width:80px;margin-top:5px">
                                <div class="progress-bar" style="width:{{ $rec->percentage }}%"></div>
                            </div>
                        </td>
                        <td style="font-weight:700;font-size:14px">{{ $rec->percentage }}%</td>
                        <td><span class="badge badge-{{ $rec->grade }}" style="font-size:13px;padding:5px 14px">{{ $rec->grade }}</span></td>
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
                        <td style="color:#64748b;font-size:12px">{{ $rec->completed_at?->format('d M Y H:i') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="card-body" style="text-align:center;padding:60px;color:#94a3b8">
            <i class="fa-solid fa-inbox" style="font-size:48px;margin-bottom:16px;display:block;opacity:.4"></i>
            <p style="font-size:15px;font-weight:600">No submissions yet</p>
            <p style="font-size:13px;margin-top:4px">Results will appear here once students submit.</p>
        </div>
        @endif
    </div>

    {{-- Right Panel --}}
    <div>
        {{-- Pass / Fail --}}
        <div class="card" style="margin-bottom:18px">
            <div class="card-header"><h2><i class="fa-solid fa-scale-balanced"></i> Pass / Fail</h2></div>
            <div class="card-body">
                <div style="display:flex;gap:12px">
                    <div class="pass-fail-card pass-card">
                        <div class="pass-fail-num pass-num">{{ $passCount }}</div>
                        <div class="pass-fail-label pass-label"><i class="fa-solid fa-circle-check"></i> Passed</div>
                    </div>
                    <div class="pass-fail-card fail-card">
                        <div class="pass-fail-num fail-num">{{ $total - $passCount }}</div>
                        <div class="pass-fail-label fail-label"><i class="fa-solid fa-circle-xmark"></i> Failed</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Grade Distribution --}}
        <div class="card" style="margin-bottom:18px">
            <div class="card-header"><h2><i class="fa-solid fa-chart-pie"></i> Grade Distribution</h2></div>
            <div class="card-body">
                @foreach(['A','B','C','D','F'] as $g)
                @php $cnt = $grades[$g] ?? 0; $pct = $total ? round(($cnt/$total)*100) : 0; @endphp
                <div class="grade-bar-row">
                    <div class="grade-bar-header">
                        <span class="grade-pill grade-pill-{{ $g }}">{{ $g }}</span>
                        <span style="font-size:12px;color:#64748b;font-weight:600">{{ $cnt }} <span style="opacity:.6">({{ $pct }}%)</span></span>
                    </div>
                    <div class="progress" style="height:10px">
                        <div class="progress-bar" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="card">
            <div class="card-header"><h2><i class="fa-solid fa-chart-simple"></i> Quick Stats</h2></div>
            <div class="card-body">
                @php $rows = [
                    ['Highest Score', $records->max('score').' / '.$quiz->totalMarks(), 'fa-arrow-up', '#065f46'],
                    ['Lowest Score',  $records->min('score').' / '.$quiz->totalMarks(), 'fa-arrow-down', '#991b1b'],
                    ['Average Score', $avgScore.' / '.$quiz->totalMarks(),              'fa-minus',      '#1e40af'],
                    ['Pass Rate',     $passRate.'%',                                    'fa-percent',    '#065f46'],
                ]; @endphp
                @foreach($rows as [$label, $val, $icon, $color])
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f1f5f9">
                    <span style="font-size:13px;color:#64748b;display:flex;align-items:center;gap:7px">
                        <i class="fa-solid fa-{{ $icon }}" style="color:{{ $color }};width:14px"></i> {{ $label }}
                    </span>
                    <span style="font-weight:700;font-size:13px;color:{{ $color }}">{{ $val ?? '—' }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection
