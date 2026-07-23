<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/forum-favicon.png') }}">
    <title>Quiz Result — {{ $quiz->title }}</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',sans-serif;background:#f0f2ff;color:#333}

        .navbar{background:linear-gradient(135deg,#667eea,#764ba2);padding:16px 28px;color:#fff;
            display:flex;justify-content:space-between;align-items:center;
            box-shadow:0 2px 12px rgba(0,0,0,.2)}
        .navbar h1{font-size:19px;font-weight:700;display:flex;align-items:center;gap:10px}
        .navbar h1 img{height:36px;width:auto}
        .navbar a{color:#fff;text-decoration:none;font-size:13px;opacity:.85;
            padding:7px 14px;border:1px solid rgba(255,255,255,.35);border-radius:6px;transition:.2s}
        .navbar a:hover{background:rgba(255,255,255,.15)}

        .container{max-width:620px;margin:40px auto;padding:0 20px}

        /* ── Result card ────────────────────────────────────────────────── */
        .result-card{background:#fff;border-radius:18px;padding:40px;
            box-shadow:0 4px 24px rgba(102,126,234,.15);text-align:center;margin-bottom:20px}

        /* ── Score circle ───────────────────────────────────────────────── */
        .score-ring{position:relative;width:160px;height:160px;margin:0 auto 24px}
        .score-ring svg{transform:rotate(-90deg)}
        .ring-bg{fill:none;stroke:#f0f2ff;stroke-width:12}
        .ring-fill{fill:none;stroke:url(#ringGrad);stroke-width:12;
            stroke-linecap:round;transition:stroke-dashoffset .8s ease}
        .score-inner{position:absolute;inset:0;display:flex;flex-direction:column;
            align-items:center;justify-content:center}
        .score-num{font-size:36px;font-weight:700;color:#333;line-height:1}
        .score-max{font-size:13px;color:#9ca3af;margin-top:3px}

        /* ── Grade badge ────────────────────────────────────────────────── */
        .grade-display{margin-bottom:6px}
        .grade-letter{font-size:56px;font-weight:900;line-height:1}
        .grade-A .grade-letter{color:#155724}
        .grade-B .grade-letter{color:#0c5460}
        .grade-C .grade-letter{color:#856404}
        .grade-D .grade-letter{color:#e67e22}
        .grade-F .grade-letter{color:#dc3545}

        .pct-display{font-size:26px;font-weight:700;color:#667eea;margin-bottom:4px}
        .quiz-name{font-size:18px;font-weight:700;margin-bottom:4px;color:#333}
        .group-name{font-size:13px;color:#9ca3af;margin-bottom:28px}

        /* ── Status banner ──────────────────────────────────────────────── */
        .status-banner{padding:12px 20px;border-radius:10px;font-size:15px;
            font-weight:700;margin-bottom:24px;display:inline-block}
        .status-pass{background:#d4edda;color:#155724}
        .status-fail{background:#f8d7da;color:#721c24}

        /* ── Detail rows ────────────────────────────────────────────────── */
        .details{text-align:left;border-top:2px solid #f0f2ff;padding-top:20px}
        .detail-row{display:flex;justify-content:space-between;align-items:center;
            padding:11px 0;border-bottom:1px solid #f0f2ff;font-size:14px}
        .detail-row:last-child{border-bottom:none}
        .detail-row .lbl{color:#9ca3af;font-weight:500}
        .detail-row .val{font-weight:600;color:#333}

        /* ── Grade scale ────────────────────────────────────────────────── */
        .grade-scale{background:#f8f9ff;border-radius:12px;padding:16px 20px;
            margin-top:16px;text-align:left}
        .grade-scale-title{font-size:11px;font-weight:700;text-transform:uppercase;
            letter-spacing:.5px;color:#9ca3af;margin-bottom:10px}
        .grade-scale-row{display:flex;gap:8px;flex-wrap:wrap}
        .gs-item{padding:4px 12px;border-radius:8px;font-size:12px;font-weight:700}
        .gs-A{background:#d4edda;color:#155724}
        .gs-B{background:#d1ecf1;color:#0c5460}
        .gs-C{background:#fff3cd;color:#856404}
        .gs-D{background:#ffe5d0;color:#e67e22}
        .gs-F{background:#f8d7da;color:#dc3545}

        /* ── Actions ────────────────────────────────────────────────────── */
        .actions{display:flex;gap:12px;justify-content:center;margin-top:24px}
        .btn{padding:12px 26px;border-radius:10px;font-size:14px;font-weight:600;
            text-decoration:none;display:inline-block;transition:all .2s;border:none;cursor:pointer}
        .btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;
            box-shadow:0 4px 14px rgba(102,126,234,.35)}
        .btn-primary:hover{opacity:.9;transform:translateY(-1px)}
        .btn-secondary{background:#f0f2ff;color:#667eea;border:2px solid #e8eaf0}
        .btn-secondary:hover{background:#e8eaf0}

        /* ── Alert ──────────────────────────────────────────────────────── */
        .alert{padding:13px 18px;border-radius:10px;margin-bottom:18px;font-size:14px}
        .alert-success{background:#d4edda;color:#155724;border-left:4px solid #28a745}
    </style>
</head>
<body>

<nav class="navbar">
    <h1><img src="{{ asset('images/forum.png') }}" alt="Forum Logo"> Discussion Hub</h1>
    <a href="{{ route('quizzes.index') }}">← All Quizzes</a>
</nav>

<div class="container">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @php
        $pct   = (float) $record['percentage'];
        $grade = $record['grade'];
        $score = $record['score'];
        $max   = $record['max_score'];
        $pass  = $pct >= 50;
        // SVG ring: circumference = 2π × r (r=66)
        $circ  = round(2 * M_PI * 66, 2);
        $dash  = round($circ * ($pct / 100), 2);
    @endphp

    <div class="result-card">

        {{-- ── Animated score ring ──────────────────────────────────────── --}}
        <div class="score-ring">
            <svg width="160" height="160" viewBox="0 0 160 160">
                <defs>
                    <linearGradient id="ringGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:#667eea"/>
                        <stop offset="100%" style="stop-color:#764ba2"/>
                    </linearGradient>
                </defs>
                <circle class="ring-bg" cx="80" cy="80" r="66"/>
                <circle class="ring-fill" cx="80" cy="80" r="66"
                        stroke-dasharray="{{ $circ }}"
                        stroke-dashoffset="{{ $circ - $dash }}"
                        id="ringFill"/>
            </svg>
            <div class="score-inner">
                <div class="score-num">{{ $score }}</div>
                <div class="score-max">/ {{ $max }}</div>
            </div>
        </div>

        {{-- ── Grade & percentage ────────────────────────────────────────── --}}
        <div class="grade-display grade-{{ $grade }}">
            <div class="grade-letter">{{ $grade }}</div>
        </div>
        <div class="pct-display">{{ $pct }}%</div>

        <div class="quiz-name">{{ $quiz->title }}</div>
        <div class="group-name">{{ $quiz->group->name }}</div>

        <div class="status-banner {{ $pass ? 'status-pass' : 'status-fail' }}">
            {{ $pass ? '✅ Passed' : '❌ Failed' }}
        </div>

        {{-- ── Participation record details (SDD §4.2.5) ───────────────── --}}
        <div class="details">
            <div class="detail-row">
                <span class="lbl">Score</span>
                <span class="val">{{ $score }} / {{ $max }}</span>
            </div>
            <div class="detail-row">
                <span class="lbl">Percentage</span>
                <span class="val">{{ $pct }}%</span>
            </div>
            <div class="detail-row">
                <span class="lbl">Grade</span>
                <span class="val grade-{{ $grade }}" style="font-size:16px">{{ $grade }}</span>
            </div>
            <div class="detail-row">
                <span class="lbl">Status</span>
                <span class="val">{{ $pass ? '✅ Pass' : '❌ Fail' }}</span>
            </div>
            <div class="detail-row">
                <span class="lbl">Submitted At</span>
                <span class="val">
                    {{ isset($record['completed_at'])
                        ? \Carbon\Carbon::parse($record['completed_at'])->format('D d M Y, H:i')
                        : '—' }}
                </span>
            </div>
            <div class="detail-row">
                <span class="lbl">Quiz Duration</span>
                <span class="val">{{ $quiz->duration_minutes }} minutes</span>
            </div>
        </div>

        {{-- ── Grade scale reference ─────────────────────────────────────── --}}
        <div class="grade-scale">
            <div class="grade-scale-title">Grade Scale</div>
            <div class="grade-scale-row">
                <span class="gs-item gs-A">A ≥ 80%</span>
                <span class="gs-item gs-B">B ≥ 65%</span>
                <span class="gs-item gs-C">C ≥ 50%</span>
                <span class="gs-item gs-D">D ≥ 40%</span>
                <span class="gs-item gs-F">F &lt; 40%</span>
            </div>
        </div>

        <div class="actions">
            <a href="{{ route('quizzes.index') }}" class="btn btn-primary">← Back to Quizzes</a>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Dashboard</a>
        </div>
    </div>

</div>

<script>
// Animate ring on load
window.addEventListener('load', function () {
    const ring  = document.getElementById('ringFill');
    const circ  = {{ $circ }};
    const dash  = {{ $dash }};
    ring.style.strokeDashoffset = circ;
    setTimeout(() => {
        ring.style.transition = 'stroke-dashoffset 1s ease';
        ring.style.strokeDashoffset = circ - dash;
    }, 100);
});
</script>
</body>
</html>
