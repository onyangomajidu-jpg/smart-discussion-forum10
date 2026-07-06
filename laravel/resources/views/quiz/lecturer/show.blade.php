<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quiz — {{ $quiz->title }}</title>
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

        /* ── Lifecycle sequence bar (SDD Fig 3.12) ──────────────────────── */
        .lifecycle{display:flex;align-items:stretch;margin-bottom:26px;
            background:#fff;border-radius:14px;overflow:hidden;
            box-shadow:0 2px 14px rgba(102,126,234,.1)}
        .lc-step{flex:1;padding:14px 10px;text-align:center;position:relative;
            border-right:1px solid #f0f2ff;transition:.2s}
        .lc-step:last-child{border-right:none}
        .lc-step .lc-icon{font-size:20px;display:block;margin-bottom:5px}
        .lc-step .lc-title{font-size:11px;font-weight:700;text-transform:uppercase;
            letter-spacing:.5px;color:#9ca3af}
        .lc-step .lc-sub{font-size:10px;color:#c4c9d4;margin-top:2px}
        .lc-step.done{background:#f0f2ff}
        .lc-step.done .lc-title{color:#667eea}
        .lc-step.active{background:linear-gradient(135deg,#667eea,#764ba2)}
        .lc-step.active .lc-title,.lc-step.active .lc-sub{color:#fff}
        .lc-step.active .lc-icon{filter:brightness(10)}

        /* ── Cards ──────────────────────────────────────────────────────── */
        .card{background:#fff;border-radius:14px;padding:28px 32px;
            box-shadow:0 2px 14px rgba(102,126,234,.1);margin-bottom:22px}
        .card-header{display:flex;justify-content:space-between;align-items:center;
            margin-bottom:20px;padding-bottom:14px;border-bottom:2px solid #f0f2ff}
        .card-header h2{font-size:17px;font-weight:700}

        /* ── Status badge ───────────────────────────────────────────────── */
        .badge{display:inline-block;padding:4px 14px;border-radius:12px;font-size:12px;font-weight:700}
        .badge-draft{background:#fff3cd;color:#856404}
        .badge-published{background:#d4edda;color:#155724}
        .badge-closed{background:#f8d7da;color:#721c24}

        /* ── Meta grid ──────────────────────────────────────────────────── */
        .meta-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}
        .meta-item{background:#f8f9ff;border-radius:10px;padding:16px;text-align:center;
            border:2px solid #f0f2ff}
        .meta-item .val{font-size:24px;font-weight:700;color:#667eea}
        .meta-item .lbl{font-size:11px;color:#9ca3af;margin-top:4px;text-transform:uppercase;letter-spacing:.5px}

        /* ── Info table ─────────────────────────────────────────────────── */
        .info-table{width:100%;border-collapse:collapse;font-size:14px;margin-bottom:20px}
        .info-table th{width:200px;padding:10px 14px;text-align:left;font-weight:600;
            color:#667eea;background:#f8f9ff;border-bottom:1px solid #f0f2ff;font-size:12px;
            text-transform:uppercase;letter-spacing:.4px}
        .info-table td{padding:10px 14px;border-bottom:1px solid #f0f2ff;color:#444}

        /* ── Action buttons ─────────────────────────────────────────────── */
        .actions{display:flex;gap:12px;flex-wrap:wrap}
        .btn{padding:11px 22px;border:none;border-radius:9px;font-size:14px;font-weight:600;
            cursor:pointer;transition:all .2s;text-decoration:none;display:inline-block}
        .btn-success{background:#28a745;color:#fff;box-shadow:0 4px 12px rgba(40,167,69,.3)}
        .btn-warning{background:#ffc107;color:#212529;box-shadow:0 4px 12px rgba(255,193,7,.3)}
        .btn-info{background:#17a2b8;color:#fff;box-shadow:0 4px 12px rgba(23,162,184,.3)}
        .btn:hover{opacity:.88;transform:translateY(-1px)}

        /* ── Alerts ─────────────────────────────────────────────────────── */
        .alert{padding:13px 18px;border-radius:10px;margin-bottom:18px;font-size:14px;font-weight:500}
        .alert-success{background:#d4edda;color:#155724;border-left:4px solid #28a745}
        .alert-info{background:#d1ecf1;color:#0c5460;border-left:4px solid #17a2b8}

        /* ── Questions list ─────────────────────────────────────────────── */
        .q-item{padding:16px 0;border-bottom:1px solid #f0f2ff}
        .q-item:last-child{border-bottom:none}
        .q-text{font-weight:600;font-size:15px;margin-bottom:10px;color:#333}
        .q-marks-tag{font-size:11px;color:#667eea;font-weight:700;
            background:#eef0ff;padding:2px 8px;border-radius:8px;margin-left:8px}
        .options-preview{list-style:none;padding-left:12px;display:flex;flex-direction:column;gap:5px}
        .options-preview li{font-size:13px;padding:5px 10px;border-radius:6px;color:#555}
        .options-preview li.correct{background:#d4edda;color:#155724;font-weight:600}

        /* ── Results table ──────────────────────────────────────────────── */
        table.results{width:100%;border-collapse:collapse;font-size:14px}
        table.results th{background:#f8f9ff;padding:11px 14px;text-align:left;
            font-weight:600;color:#667eea;border-bottom:2px solid #f0f2ff;
            font-size:12px;text-transform:uppercase;letter-spacing:.4px}
        table.results td{padding:11px 14px;border-bottom:1px solid #f0f2ff}
        table.results tr:hover td{background:#fafbff}
        .grade-A{color:#155724;font-weight:700}
        .grade-B{color:#0c5460;font-weight:700}
        .grade-C{color:#856404;font-weight:700}
        .grade-D{color:#e67e22;font-weight:700}
        .grade-F{color:#dc3545;font-weight:700}
        .pct-bar{background:#e9ecef;border-radius:4px;height:6px;width:80px;overflow:hidden;display:inline-block;vertical-align:middle;margin-left:8px}
        .pct-fill{background:linear-gradient(90deg,#667eea,#764ba2);height:100%;border-radius:4px}
    </style>
</head>
<body>

<nav class="navbar">
    <h1>🎓 Smart Discussion Forum</h1>
    <a href="{{ route('lecturer.dashboard') }}">← Dashboard</a>
</nav>

<div class="container">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    {{-- ── Quiz Lifecycle Sequence (SDD Fig 3.12) ───────────────────────── --}}
    @php
        $lcStep = match($quiz->status) {
            'draft'     => 1,
            'published' => $quiz->attempts->count() > 0 ? 4 : ($quiz->isOpen() ? 3 : 2),
            'closed'    => 5,
            default     => 1,
        };
    @endphp
    <div class="lifecycle">
        <div class="lc-step {{ $lcStep >= 1 ? ($lcStep == 1 ? 'active' : 'done') : '' }}">
            <span class="lc-icon">✏️</span>
            <div class="lc-title">1. Draft</div>
            <div class="lc-sub">Quiz created</div>
        </div>
        <div class="lc-step {{ $lcStep >= 2 ? ($lcStep == 2 ? 'active' : 'done') : '' }}">
            <span class="lc-icon">🚀</span>
            <div class="lc-title">2. Published</div>
            <div class="lc-sub">{{ $quiz->published_at?->format('d M H:i') ?? '—' }}</div>
        </div>
        <div class="lc-step {{ $lcStep >= 3 ? ($lcStep == 3 ? 'active' : 'done') : '' }}">
            <span class="lc-icon">🔔</span>
            <div class="lc-title">3. Reminder Sent</div>
            <div class="lc-sub">sendQuizReminder()</div>
        </div>
        <div class="lc-step {{ $lcStep >= 4 ? ($lcStep == 4 ? 'active' : 'done') : '' }}">
            <span class="lc-icon">📝</span>
            <div class="lc-title">4. Attempts</div>
            <div class="lc-sub">{{ $quiz->attempts->count() }} submitted</div>
        </div>
        <div class="lc-step {{ $lcStep >= 5 ? 'active' : '' }}">
            <span class="lc-icon">📊</span>
            <div class="lc-title">5. Results</div>
            <div class="lc-sub">assignMarks()</div>
        </div>
    </div>

    {{-- ── Quiz Header Card ──────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div>
                <h2>{{ $quiz->title }}</h2>
                <div style="margin-top:8px;display:flex;align-items:center;gap:10px">
                    <span class="badge badge-{{ $quiz->status }}">{{ strtoupper($quiz->status) }}</span>
                    <span style="font-size:13px;color:#9ca3af">Group: <strong style="color:#555">{{ $quiz->group->name }}</strong></span>
                </div>
            </div>
            <div class="actions">
                @if($quiz->status === 'draft')
                    <form action="{{ route('lecturer.quizzes.publish', $quiz) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success">🚀 Publish Quiz</button>
                    </form>
                @endif
                @if($quiz->status === 'published')
                    <form action="{{ route('lecturer.quizzes.remind', $quiz) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-warning">🔔 Send Reminder</button>
                    </form>
                @endif
                <a href="{{ route('lecturer.quizzes.results', $quiz) }}" class="btn btn-info">📊 Full Results</a>
            </div>
        </div>

        <div class="meta-grid">
            <div class="meta-item">
                <div class="val">{{ $quiz->questions->count() }}</div>
                <div class="lbl">Questions</div>
            </div>
            <div class="meta-item">
                <div class="val">{{ $quiz->totalMarks() }}</div>
                <div class="lbl">Total Marks</div>
            </div>
            <div class="meta-item">
                <div class="val">{{ $quiz->duration_minutes }} min</div>
                <div class="lbl">Duration</div>
            </div>
            <div class="meta-item">
                <div class="val">{{ $quiz->attempts->count() }}</div>
                <div class="lbl">Submissions</div>
            </div>
        </div>

        <table class="info-table">
            <tr>
                <th>Unlock Date</th>
                <td>{{ $quiz->unlock_date?->format('D d M Y, H:i') ?? '— (opens on publish)' }}</td>
            </tr>
            <tr>
                <th>Hard Deadline</th>
                <td>{{ $quiz->hard_deadline?->format('D d M Y, H:i') ?? '— (no deadline)' }}</td>
            </tr>
            <tr>
                <th>Auto-submit</th>
                <td>{{ $quiz->auto_submit ? '✅ Enabled — answers submitted automatically on timer expiry' : '❌ Disabled' }}</td>
            </tr>
            <tr>
                <th>Focused-window Isolation</th>
                <td>{{ $quiz->enforce_focus ? '🔒 Enabled — students warned on tab/window switch' : '❌ Disabled' }}</td>
            </tr>
            <tr>
                <th>Published At</th>
                <td>{{ $quiz->published_at?->format('D d M Y, H:i') ?? '— (not yet published)' }}</td>
            </tr>
            @if($quiz->description)
            <tr>
                <th>Description</th>
                <td>{{ $quiz->description }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- ── Questions Preview ─────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h2>❓ Questions</h2>
            <span style="font-size:13px;color:#9ca3af">{{ $quiz->questions->count() }} question(s) · {{ $quiz->totalMarks() }} total marks</span>
        </div>
        @forelse($quiz->questions as $i => $q)
            <div class="q-item">
                <div class="q-text">
                    {{ $i + 1 }}. {{ $q->question }}
                    <span class="q-marks-tag">{{ $q->marks }} mark{{ $q->marks > 1 ? 's' : '' }}</span>
                </div>
                <ul class="options-preview">
                    @foreach($q->options as $oi => $opt)
                        <li class="{{ $oi == $q->correct_option ? 'correct' : '' }}">
                            {{ $oi == $q->correct_option ? '✅' : '○' }} {{ $opt }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p style="color:#9ca3af;text-align:center;padding:20px">No questions added yet.</p>
        @endforelse
    </div>

    {{-- ── Submissions Summary ───────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h2>👥 Submissions</h2>
            <span style="font-size:13px;color:#9ca3af">{{ $quiz->attempts->count() }} of group members submitted</span>
        </div>
        @if($quiz->participationRecords->count())
            <table class="results">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Score / {{ $quiz->totalMarks() }}</th>
                        <th>Percentage</th>
                        <th>Grade</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quiz->participationRecords->sortByDesc('score') as $i => $rec)
                        <tr>
                            <td style="color:#9ca3af">{{ $i + 1 }}</td>
                            <td><strong>{{ $rec->user->name }}</strong></td>
                            <td>
                                {{ $rec->score }}
                                <span class="pct-bar"><span class="pct-fill" style="width:{{ $rec->percentage }}%"></span></span>
                            </td>
                            <td>{{ $rec->percentage }}%</td>
                            <td class="grade-{{ $rec->grade }}">{{ $rec->grade }}</td>
                            <td style="color:#9ca3af;font-size:13px">{{ $rec->completed_at?->format('d M Y H:i') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color:#9ca3af;text-align:center;padding:28px">No submissions yet.</p>
        @endif
    </div>

</div>
</body>
</html>
