<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results — {{ $quiz->title }}</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',sans-serif;background:#f0f2ff;color:#333}

        .navbar{background:linear-gradient(135deg,#667eea,#764ba2);padding:16px 28px;color:#fff;
            display:flex;justify-content:space-between;align-items:center;
            box-shadow:0 2px 12px rgba(0,0,0,.2)}
        .navbar h1{font-size:19px;font-weight:700}
        .navbar a{color:#fff;text-decoration:none;font-size:13px;opacity:.85;
            padding:7px 14px;border:1px solid rgba(255,255,255,.35);border-radius:6px;transition:.2s}
        .navbar a:hover{background:rgba(255,255,255,.15)}

        .container{max-width:980px;margin:32px auto;padding:0 20px}

        .card{background:#fff;border-radius:14px;padding:28px 32px;
            box-shadow:0 2px 14px rgba(102,126,234,.1);margin-bottom:22px}
        .card-header{display:flex;justify-content:space-between;align-items:center;
            margin-bottom:22px;padding-bottom:14px;border-bottom:2px solid #f0f2ff}
        .card-header h2{font-size:17px;font-weight:700}

        /* ── Stats grid ─────────────────────────────────────────────────── */
        .stats-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:26px}
        .stat{background:#f8f9ff;border-radius:12px;padding:18px;text-align:center;
            border:2px solid #f0f2ff}
        .stat .val{font-size:28px;font-weight:700;color:#667eea}
        .stat .lbl{font-size:11px;color:#9ca3af;margin-top:5px;text-transform:uppercase;letter-spacing:.5px}

        /* ── Grade distribution ─────────────────────────────────────────── */
        .grade-dist{display:flex;gap:10px;margin-bottom:26px}
        .grade-bar-wrap{flex:1;text-align:center}
        .grade-bar-outer{background:#f0f2ff;border-radius:8px;height:80px;
            display:flex;align-items:flex-end;overflow:hidden;margin-bottom:6px}
        .grade-bar-inner{width:100%;border-radius:8px 8px 0 0;transition:height .4s;min-height:4px}
        .bar-A{background:#28a745}.bar-B{background:#17a2b8}
        .bar-C{background:#ffc107}.bar-D{background:#fd7e14}.bar-F{background:#dc3545}
        .grade-label{font-size:13px;font-weight:700}
        .grade-count{font-size:11px;color:#9ca3af}

        /* ── Results table ──────────────────────────────────────────────── */
        table{width:100%;border-collapse:collapse;font-size:14px}
        th{background:#f8f9ff;padding:11px 14px;text-align:left;font-weight:600;
            color:#667eea;border-bottom:2px solid #f0f2ff;
            font-size:12px;text-transform:uppercase;letter-spacing:.4px}
        td{padding:11px 14px;border-bottom:1px solid #f0f2ff}
        tr:hover td{background:#fafbff}
        .grade-A{color:#155724;font-weight:700}
        .grade-B{color:#0c5460;font-weight:700}
        .grade-C{color:#856404;font-weight:700}
        .grade-D{color:#e67e22;font-weight:700}
        .grade-F{color:#dc3545;font-weight:700}
        .pct-bar{background:#e9ecef;border-radius:4px;height:6px;width:80px;
            overflow:hidden;display:inline-block;vertical-align:middle;margin-left:8px}
        .pct-fill{background:linear-gradient(90deg,#667eea,#764ba2);height:100%;border-radius:4px}
        .rank-1{color:#f59e0b;font-weight:700}
        .rank-2{color:#9ca3af;font-weight:700}
        .rank-3{color:#cd7f32;font-weight:700}
    </style>
</head>
<body>

<nav class="navbar">
    <h1>🎓 Smart Discussion Forum</h1>
    <a href="{{ route('lecturer.quizzes.show', $quiz) }}">← Back to Quiz</a>
</nav>

<div class="container">

    {{-- ── Summary Stats ─────────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h2>📊 Results: {{ $quiz->title }}</h2>
            <span style="font-size:13px;color:#9ca3af">Group: {{ $quiz->group->name }}</span>
        </div>

        @php
            $total     = $records->count();
            $avgScore  = $total ? round($records->avg('score'), 1) : 0;
            $avgPct    = $total ? round($records->avg('percentage'), 1) : 0;
            $passCount = $records->where('percentage', '>=', 50)->count();
            $passRate  = $total ? round(($passCount / $total) * 100) : 0;
            $highest   = $total ? $records->max('score') : 0;

            $gradeCounts = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];
            foreach ($records as $r) {
                if (isset($gradeCounts[$r->grade])) $gradeCounts[$r->grade]++;
            }
            $maxGradeCount = max(array_values($gradeCounts)) ?: 1;
        @endphp

        <div class="stats-grid">
            <div class="stat"><div class="val">{{ $total }}</div><div class="lbl">Submissions</div></div>
            <div class="stat"><div class="val">{{ $avgScore }}</div><div class="lbl">Avg Score</div></div>
            <div class="stat"><div class="val">{{ $avgPct }}%</div><div class="lbl">Avg Percentage</div></div>
            <div class="stat"><div class="val">{{ $passRate }}%</div><div class="lbl">Pass Rate</div></div>
            <div class="stat"><div class="val">{{ $highest }}</div><div class="lbl">Highest Score</div></div>
        </div>

        {{-- ── Grade Distribution Bar Chart ──────────────────────────────── --}}
        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#9ca3af;margin-bottom:12px">Grade Distribution</div>
        <div class="grade-dist">
            @foreach(['A','B','C','D','F'] as $g)
                @php $cnt = $gradeCounts[$g]; $h = $maxGradeCount > 0 ? round(($cnt/$maxGradeCount)*80) : 0; @endphp
                <div class="grade-bar-wrap">
                    <div class="grade-bar-outer">
                        <div class="grade-bar-inner bar-{{ $g }}" style="height:{{ max($h,4) }}px"></div>
                    </div>
                    <div class="grade-label grade-{{ $g }}">{{ $g }}</div>
                    <div class="grade-count">{{ $cnt }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── Participation Records Table (SDD §4.2.5 — participationRecord) ── --}}
    <div class="card">
        <div class="card-header">
            <h2>🏆 Participation Records</h2>
            <span style="font-size:12px;color:#9ca3af">assignMarks() · calculateMarks() · participationRecord()</span>
        </div>

        @if($records->count())
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Student</th>
                        <th>Score / {{ $quiz->totalMarks() }}</th>
                        <th>Percentage</th>
                        <th>Grade</th>
                        <th>Status</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $i => $rec)
                        <tr>
                            <td class="{{ $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : '')) }}">
                                {{ $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : $i + 1)) }}
                            </td>
                            <td><strong>{{ $rec->user->name }}</strong></td>
                            <td>
                                {{ $rec->score }}
                                <span class="pct-bar">
                                    <span class="pct-fill" style="width:{{ $rec->percentage }}%"></span>
                                </span>
                            </td>
                            <td>{{ $rec->percentage }}%</td>
                            <td class="grade-{{ $rec->grade }}">{{ $rec->grade }}</td>
                            <td>
                                @if($rec->percentage >= 50)
                                    <span style="color:#155724;font-weight:600">✅ Pass</span>
                                @else
                                    <span style="color:#dc3545;font-weight:600">❌ Fail</span>
                                @endif
                            </td>
                            <td style="color:#9ca3af;font-size:13px">
                                {{ $rec->completed_at?->format('d M Y H:i') ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color:#9ca3af;text-align:center;padding:36px;font-size:15px">
                📭 No submissions yet.
            </p>
        @endif
    </div>

</div>
</body>
</html>
