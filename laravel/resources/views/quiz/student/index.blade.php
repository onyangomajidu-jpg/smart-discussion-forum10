<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Quizzes — Smart Discussion Forum</title>
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

        .container{max-width:860px;margin:32px auto;padding:0 20px}
        .page-title{font-size:22px;font-weight:700;margin-bottom:6px}
        .page-sub{font-size:14px;color:#9ca3af;margin-bottom:24px}

        /* ── Quiz card ──────────────────────────────────────────────────── */
        .quiz-card{background:#fff;border-radius:14px;padding:22px 26px;
            box-shadow:0 2px 14px rgba(102,126,234,.08);margin-bottom:16px;
            display:flex;justify-content:space-between;align-items:center;gap:20px;
            border-left:5px solid #e8eaf0;transition:.2s}
        .quiz-card.open{border-left-color:#28a745}
        .quiz-card.upcoming{border-left-color:#ffc107}
        .quiz-card.closed{border-left-color:#dc3545}
        .quiz-card.done{border-left-color:#17a2b8}

        .quiz-info h3{font-size:16px;font-weight:700;margin-bottom:6px}
        .quiz-info .meta{font-size:13px;color:#9ca3af;margin-bottom:8px;line-height:1.6}
        .quiz-info .meta strong{color:#555}

        /* ── Badges ─────────────────────────────────────────────────────── */
        .badge{display:inline-block;padding:4px 12px;border-radius:10px;
            font-size:11px;font-weight:700;margin-right:6px}
        .badge-open{background:#d4edda;color:#155724}
        .badge-upcoming{background:#fff3cd;color:#856404}
        .badge-closed{background:#f8d7da;color:#721c24}
        .badge-done{background:#d1ecf1;color:#0c5460}

        /* ── Countdown ──────────────────────────────────────────────────── */
        .countdown-tag{font-size:12px;color:#856404;font-weight:600;
            background:#fff3cd;padding:3px 10px;border-radius:8px;display:inline-block;margin-top:4px}

        /* ── Buttons ────────────────────────────────────────────────────── */
        .btn{padding:10px 22px;border:none;border-radius:9px;font-size:14px;font-weight:600;
            cursor:pointer;text-decoration:none;display:inline-block;transition:all .2s;white-space:nowrap}
        .btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;
            box-shadow:0 4px 14px rgba(102,126,234,.35)}
        .btn-primary:hover{opacity:.9;transform:translateY(-1px)}
        .btn-secondary{background:#f0f2ff;color:#667eea;border:2px solid #e8eaf0}
        .btn-secondary:hover{background:#e8eaf0}
        .btn-disabled{background:#f0f2ff;color:#c4c9d4;border:2px solid #e8eaf0;cursor:not-allowed}

        /* ── Alerts ─────────────────────────────────────────────────────── */
        .alert{padding:13px 18px;border-radius:10px;margin-bottom:18px;font-size:14px}
        .alert-info{background:#d1ecf1;color:#0c5460;border-left:4px solid #17a2b8}

        /* ── Empty state ────────────────────────────────────────────────── */
        .empty{text-align:center;padding:60px 20px;color:#9ca3af}
        .empty .icon{font-size:48px;margin-bottom:14px}
        .empty p{font-size:15px}
    </style>
</head>
<body>

<nav class="navbar">
    <h1>🎓 Smart Discussion Forum</h1>
    <a href="{{ route('dashboard') }}">← Dashboard</a>
</nav>

<div class="container">

    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    <div class="page-title">📝 My Quizzes</div>
    <div class="page-sub">Quizzes available in your groups</div>

    @forelse($quizzes as $quiz)
        @php
            $done     = in_array($quiz->id, $attempted);
            $isOpen   = $quiz->isOpen();
            $upcoming = $quiz->isUpcoming();
            $closed   = $quiz->isPastDeadline() && !$done;
            $cardClass = $done ? 'done' : ($isOpen ? 'open' : ($upcoming ? 'upcoming' : 'closed'));
        @endphp

        <div class="quiz-card {{ $cardClass }}">
            <div class="quiz-info">
                <h3>{{ $quiz->title }}</h3>
                <div class="meta">
                    Group: <strong>{{ $quiz->group->name }}</strong> &nbsp;·&nbsp;
                    Duration: <strong>{{ $quiz->duration_minutes }} min</strong> &nbsp;·&nbsp;
                    Questions: <strong>{{ $quiz->questions_count ?? '—' }}</strong>
                    <br>
                    Opens: <strong>{{ $quiz->unlock_date?->format('d M Y, H:i') ?? 'Now' }}</strong>
                    &nbsp;·&nbsp;
                    Deadline: <strong>{{ $quiz->hard_deadline?->format('d M Y, H:i') ?? 'None' }}</strong>
                </div>
                <div>
                    @if($done)
                        <span class="badge badge-done">✅ Submitted</span>
                    @elseif($isOpen)
                        <span class="badge badge-open">🟢 Open Now</span>
                    @elseif($upcoming)
                        <span class="badge badge-upcoming">⏳ Upcoming</span>
                        @if($quiz->unlock_date)
                            <span class="countdown-tag" data-unlock="{{ $quiz->unlock_date->timestamp }}">
                                Opens in …
                            </span>
                        @endif
                    @else
                        <span class="badge badge-closed">🔴 Closed</span>
                    @endif
                </div>
            </div>

            <div style="flex-shrink:0">
                @if($done)
                    <a href="{{ route('quizzes.result', $quiz) }}" class="btn btn-secondary">View Result</a>
                @elseif($isOpen)
                    <a href="{{ route('quizzes.take', $quiz) }}" class="btn btn-primary">Start Quiz →</a>
                @else
                    <span class="btn btn-disabled">Unavailable</span>
                @endif
            </div>
        </div>
    @empty
        <div class="empty">
            <div class="icon">📭</div>
            <p>No quizzes available in your groups right now.</p>
        </div>
    @endforelse

</div>

<script>
// Live countdown for upcoming quizzes
document.querySelectorAll('[data-unlock]').forEach(function(el) {
    const unlockTs = parseInt(el.dataset.unlock) * 1000;
    function update() {
        const diff = unlockTs - Date.now();
        if (diff <= 0) { el.textContent = 'Opening…'; location.reload(); return; }
        const h = Math.floor(diff / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        el.textContent = 'Opens in ' +
            (h > 0 ? h + 'h ' : '') +
            (m > 0 ? m + 'm ' : '') +
            s + 's';
    }
    update();
    setInterval(update, 1000);
});
</script>
</body>
</html>
