<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/forum-favicon.png') }}">
    <title>{{ $quiz->title }} — Quiz in Progress</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            user-select: none;
            -webkit-user-select: none;
        }

        /* ── Lockdown Header ──────────────────────────────────────────── */
        .quiz-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #312e81 100%);
            color: #fff;
            padding: 0 32px;
            height: 68px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky; top: 0; z-index: 200;
            box-shadow: 0 4px 24px rgba(15,23,42,.5);
        }
        .header-brand { display: flex; align-items: center; gap: 12px; }
        .header-brand .brand-icon {
            width: 38px; height: 38px; border-radius: 10px;
            background: rgba(255,255,255,.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; border: 1.5px solid rgba(255,255,255,.25);
        }
        .quiz-title-text { font-size: 15px; font-weight: 800; }
        .quiz-meta-text { font-size: 11px; opacity: .65; margin-top: 2px; display: flex; align-items: center; gap: 10px; }
        .lockdown-badge {
            background: rgba(239,68,68,.25);
            border: 1px solid rgba(239,68,68,.4);
            color: #fca5a5;
            padding: 3px 10px; border-radius: 20px;
            font-size: 10px; font-weight: 700;
            display: flex; align-items: center; gap: 4px;
        }

        /* ── Timer ────────────────────────────────────────────────────── */
        .timer-box {
            background: rgba(255,255,255,.1);
            border: 2px solid rgba(255,255,255,.25);
            border-radius: 14px;
            padding: 10px 22px;
            text-align: center;
            min-width: 130px;
            backdrop-filter: blur(4px);
            transition: all .3s;
        }
        .timer-time { font-size: 28px; font-weight: 900; letter-spacing: 3px; font-variant-numeric: tabular-nums; line-height: 1; }
        .timer-label { font-size: 9px; opacity: .6; text-transform: uppercase; letter-spacing: 1px; margin-top: 3px; }
        .timer-warning { border-color: rgba(245,158,11,.6); background: rgba(245,158,11,.15); }
        .timer-warning .timer-time { color: #fbbf24; }
        .timer-danger { border-color: rgba(239,68,68,.6); background: rgba(239,68,68,.15); animation: pulse .8s infinite; }
        .timer-danger .timer-time { color: #f87171; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.7} }

        /* ── Progress Bar Strip ───────────────────────────────────────── */
        .progress-strip {
            background: #fff;
            padding: 12px 32px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 24px;
        }
        .prog-row { display: flex; align-items: center; gap: 10px; flex: 1; }
        .prog-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; width: 90px; flex-shrink: 0; display: flex; align-items: center; gap: 5px; }
        .prog-track { flex: 1; background: #f1f5f9; border-radius: 6px; height: 8px; overflow: hidden; }
        .prog-fill { height: 100%; border-radius: 6px; transition: width .4s ease; }
        .fill-answers { background: linear-gradient(90deg, #6366f1, #8b5cf6); }
        .fill-time    { background: linear-gradient(90deg, #10b981, #059669); }
        .fill-time.warn   { background: linear-gradient(90deg, #f59e0b, #d97706); }
        .fill-time.danger { background: linear-gradient(90deg, #ef4444, #dc2626); }
        .prog-val { font-size: 11px; font-weight: 700; color: #64748b; width: 55px; text-align: right; flex-shrink: 0; }

        /* ── Content ──────────────────────────────────────────────────── */
        .container { max-width: 820px; margin: 32px auto; padding: 0 20px; }

        /* ── Question Navigator ───────────────────────────────────────── */
        .q-nav {
            background: #fff;
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
        }
        .q-nav-title { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
        .q-nav-dots { display: flex; gap: 8px; flex-wrap: wrap; }
        .q-dot {
            width: 34px; height: 34px; border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700;
            border: 2px solid #e2e8f0;
            background: #f8fafc; color: #64748b;
            cursor: pointer; transition: all .2s;
        }
        .q-dot:hover { border-color: #6366f1; color: #6366f1; }
        .q-dot.answered { background: linear-gradient(135deg,#6366f1,#8b5cf6); color: #fff; border-color: transparent; box-shadow: 0 3px 10px rgba(99,102,241,.35); }

        /* ── Question Card ────────────────────────────────────────────── */
        .question-card {
            background: #fff;
            border-radius: 16px;
            padding: 28px 32px;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            margin-bottom: 20px;
            border: 2px solid #e2e8f0;
            transition: border-color .2s;
        }
        .question-card.answered { border-color: #c7d2fe; }
        .q-number {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 14px;
        }
        .q-num-badge {
            background: linear-gradient(135deg,#6366f1,#8b5cf6);
            color: #fff; font-size: 11px; font-weight: 700;
            padding: 4px 12px; border-radius: 20px;
            display: flex; align-items: center; gap: 5px;
        }
        .q-answered-tag {
            background: #d1fae5; color: #065f46;
            font-size: 11px; font-weight: 700;
            padding: 4px 10px; border-radius: 20px;
            display: none; align-items: center; gap: 4px;
        }
        .q-text { font-size: 17px; font-weight: 700; line-height: 1.55; color: #0f172a; margin-bottom: 6px; }
        .q-marks { font-size: 12px; color: #94a3b8; margin-bottom: 20px; display: flex; align-items: center; gap: 5px; }

        /* ── Options ──────────────────────────────────────────────────── */
        .options { display: flex; flex-direction: column; gap: 10px; }
        .option-label {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all .2s;
            font-size: 14px; color: #374151;
            position: relative;
        }
        .option-label:hover { border-color: #a5b4fc; background: #f8f9ff; transform: translateX(3px); }
        .option-label.selected { border-color: #6366f1; background: linear-gradient(135deg,rgba(99,102,241,.06),rgba(139,92,246,.04)); color: #0f172a; font-weight: 600; }
        .option-label.selected::after { content: '\f058'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; right: 16px; color: #6366f1; font-size: 16px; }
        .option-label input[type=radio] { accent-color: #6366f1; width: 18px; height: 18px; flex-shrink: 0; }
        .opt-letter {
            width: 32px; height: 32px; border-radius: 9px;
            background: #f1f5f9; color: #64748b;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 800; flex-shrink: 0;
            transition: all .2s;
        }
        .option-label.selected .opt-letter { background: #6366f1; color: #fff; }

        /* ── Submit Bar ───────────────────────────────────────────────── */
        .submit-bar {
            background: #fff;
            border-radius: 16px;
            padding: 22px 28px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            border: 1px solid #e2e8f0;
        }
        .submit-info { font-size: 14px; color: #64748b; }
        .submit-info strong { color: #6366f1; font-size: 18px; font-weight: 900; }
        .btn-submit {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff; border: none; border-radius: 12px;
            padding: 14px 36px; font-size: 15px; font-weight: 800;
            cursor: pointer; transition: all .2s;
            box-shadow: 0 6px 20px rgba(99,102,241,.4);
            display: flex; align-items: center; gap: 8px;
            font-family: inherit;
        }
        .btn-submit:hover { opacity: .9; transform: translateY(-2px); box-shadow: 0 10px 28px rgba(99,102,241,.5); }

        /* ── Focus Warning Overlay ────────────────────────────────────── */
        #focusOverlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(15,23,42,.97);
            z-index: 9999;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-align: center;
            padding: 40px;
        }
        .focus-warn-box {
            background: rgba(239,68,68,.15);
            border: 2px solid rgba(239,68,68,.4);
            border-radius: 24px;
            padding: 48px 56px;
            max-width: 500px;
        }
        .focus-warn-icon { font-size: 72px; color: #f87171; margin-bottom: 20px; animation: shake .5s ease; }
        @keyframes shake { 0%,100%{transform:rotate(0)} 25%{transform:rotate(-8deg)} 75%{transform:rotate(8deg)} }
        .focus-warn-title { font-size: 28px; font-weight: 900; margin-bottom: 12px; }
        .focus-warn-text { font-size: 15px; opacity: .8; line-height: 1.6; margin-bottom: 8px; }
        .focus-warn-count { font-size: 12px; opacity: .5; margin-bottom: 28px; }
        .btn-return {
            background: linear-gradient(135deg,#6366f1,#8b5cf6);
            color: #fff; border: none; border-radius: 12px;
            padding: 14px 36px; font-size: 15px; font-weight: 700;
            cursor: pointer; transition: all .2s; font-family: inherit;
            display: flex; align-items: center; gap: 8px; margin: 0 auto;
        }
        .btn-return:hover { opacity: .9; transform: translateY(-1px); }

        /* ── Responsive ────────────────────────────────────────────── */
        @media (max-width: 768px) {
            .quiz-header { padding: 0 14px; height: auto; min-height: 60px; flex-wrap: wrap; gap: 8px; padding-top: 10px; padding-bottom: 10px; }
            .quiz-title-text { font-size: 13px; }
            .quiz-meta-text { font-size: 10px; gap: 6px; flex-wrap: wrap; }
            .timer-box { min-width: 90px; padding: 7px 12px; }
            .timer-time { font-size: 20px; letter-spacing: 2px; }
            .progress-strip { padding: 10px 14px; gap: 10px; flex-direction: column; }
            .container { padding: 0 12px; margin: 16px auto; }
            .question-card { padding: 18px 16px; }
            .q-text { font-size: 15px; }
            .option-label { padding: 11px 12px; font-size: 13px; gap: 10px; }
            .opt-letter { width: 26px; height: 26px; font-size: 11px; }
            .submit-bar { flex-direction: column; gap: 14px; padding: 16px; text-align: center; }
            .btn-submit { width: 100%; justify-content: center; }
            .focus-warn-box { padding: 28px 20px; }
            .focus-warn-title { font-size: 20px; }
            .modal-box { padding: 28px 20px; }
        }
            display: none;
            position: fixed; inset: 0;
            background: rgba(15,23,42,.85);
            z-index: 9998;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }
        .modal-box {
            background: #fff;
            border-radius: 20px;
            padding: 48px 40px;
            text-align: center;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 24px 80px rgba(0,0,0,.4);
        }
        .modal-icon { font-size: 56px; margin-bottom: 16px; }
        .modal-title { font-size: 24px; font-weight: 900; margin-bottom: 10px; color: #0f172a; }
        .modal-text { font-size: 14px; color: #64748b; margin-bottom: 24px; line-height: 1.6; }
        .modal-countdown { font-size: 64px; font-weight: 900; color: #ef4444; line-height: 1; margin-bottom: 6px; font-variant-numeric: tabular-nums; }
        .modal-countdown-label { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>

{{-- Focus Warning Overlay --}}
<div id="focusOverlay">
    <div class="focus-warn-box">
        <div class="focus-warn-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="focus-warn-title">Focus Violation!</div>
        <div class="focus-warn-text">You left the quiz window. This violation has been recorded.<br>Stay on this page for the entire duration of the quiz.</div>
        <div class="focus-warn-count" id="warnCount">Warning #1</div>
        <button class="btn-return" onclick="dismissFocusWarning()">
            <i class="fa-solid fa-arrow-left"></i> Return to Quiz
        </button>
    </div>
</div>

{{-- Auto-submit Modal --}}
<div id="autoSubmitModal">
    <div class="modal-box">
        <div class="modal-icon">⏰</div>
        <div class="modal-title">Time's Up!</div>
        <div class="modal-text">Your quiz is being automatically submitted as per the quiz settings.</div>
        <div class="modal-countdown" id="autoCountdown">3</div>
        <div class="modal-countdown-label">Submitting in…</div>
    </div>
</div>

{{-- Header --}}
<div class="quiz-header">
    <div class="header-brand">
        <div class="brand-icon"><i class="fa-solid fa-graduation-cap"></i></div>
        <div>
            <div class="quiz-title-text">{{ $quiz->title }}</div>
            <div class="quiz-meta-text">
                <span><i class="fa-solid fa-users"></i> {{ $quiz->group->name }}</span>
                <span><i class="fa-solid fa-circle-question"></i> {{ $quiz->questions->count() }} questions</span>
                <span><i class="fa-solid fa-star"></i> {{ $quiz->totalMarks() }} marks</span>
                @if($quiz->enforce_focus)
                <span class="lockdown-badge"><i class="fa-solid fa-lock"></i> Lockdown Mode</span>
                @endif
            </div>
        </div>
    </div>
    <div class="timer-box" id="timerBox">
        <div class="timer-time" id="timerDisplay">--:--</div>
        <div class="timer-label"><i class="fa-solid fa-stopwatch"></i> Time Left</div>
    </div>
</div>

{{-- Progress Strip --}}
<div class="progress-strip">
    <div class="prog-row">
        <span class="prog-label"><i class="fa-solid fa-circle-check" style="color:#6366f1"></i> Answered</span>
        <div class="prog-track"><div class="prog-fill fill-answers" id="answerBar" style="width:0%"></div></div>
        <span class="prog-val" id="answerVal">0 / {{ $quiz->questions->count() }}</span>
    </div>
    <div class="prog-row">
        <span class="prog-label"><i class="fa-solid fa-clock" style="color:#10b981"></i> Time</span>
        <div class="prog-track"><div class="prog-fill fill-time" id="timeBar" style="width:100%"></div></div>
        <span class="prog-val" id="timeVal">{{ $quiz->duration_minutes }}m</span>
    </div>
</div>

{{-- Main Content --}}
<div class="container">

    {{-- Question Navigator --}}
    <div class="q-nav">
        <div class="q-nav-title"><i class="fa-solid fa-map"></i> Question Navigator</div>
        <div class="q-nav-dots">
            @foreach($quiz->questions as $i => $q)
            <div class="q-dot" id="dot_{{ $q->id }}" onclick="scrollToQ({{ $q->id }})">{{ $i + 1 }}</div>
            @endforeach
        </div>
    </div>

    <form action="{{ route('quizzes.submit', $quiz) }}" method="POST" id="quizForm">
        @csrf

        @foreach($quiz->questions as $i => $question)
        <div class="question-card" id="qcard_{{ $question->id }}">
            <div class="q-number">
                <span class="q-num-badge"><i class="fa-solid fa-circle-question"></i> Question {{ $i + 1 }} of {{ $quiz->questions->count() }}</span>
                <span class="q-answered-tag" id="qtag_{{ $question->id }}"><i class="fa-solid fa-check"></i> Answered</span>
            </div>
            <div class="q-text">{{ $question->question }}</div>
            <div class="q-marks"><i class="fa-solid fa-star" style="color:#f59e0b"></i> {{ $question->marks }} mark{{ $question->marks > 1 ? 's' : '' }}</div>

            <div class="options">
                @foreach($question->options as $oi => $opt)
                <label class="option-label" id="opt_{{ $question->id }}_{{ $oi }}">
                    <input type="radio"
                           name="answers[{{ $question->id }}]"
                           value="{{ $oi }}"
                           onchange="onAnswer({{ $question->id }}, {{ $oi }})">
                    <div class="opt-letter">{{ chr(65+$oi) }}</div>
                    {{ $opt }}
                </label>
                @endforeach
            </div>
        </div>
        @endforeach

        <div class="submit-bar">
            <div class="submit-info">
                <strong id="answeredCount">0</strong> of {{ $quiz->questions->count() }} questions answered
            </div>
            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="fa-solid fa-paper-plane"></i> Submit Quiz
            </button>
        </div>
    </form>
</div>

<script>
const TIMER_SECONDS   = {{ $timerSeconds }};
const AUTO_SUBMIT     = {{ $quiz->auto_submit ? 'true' : 'false' }};
const ENFORCE_FOCUS   = {{ $enforceFocus ? 'true' : 'false' }};
const TOTAL_QUESTIONS = {{ $quiz->questions->count() }};
const DEADLINE_MS     = {{ $deadlineEpoch ?? 'null' }};

let secondsLeft   = TIMER_SECONDS;
const totalSeconds = TIMER_SECONDS;
let answered      = {};
let focusWarnings = 0;
let submitted     = false;
let timerInterval;

function formatTime(s) {
    return Math.floor(s/60).toString().padStart(2,'0') + ':' + (s%60).toString().padStart(2,'0');
}

function tick() {
    if (DEADLINE_MS) {
        secondsLeft = Math.max(0, Math.floor((DEADLINE_MS - Date.now()) / 1000));
    } else {
        secondsLeft = Math.max(0, secondsLeft - 1);
    }

    const display = document.getElementById('timerDisplay');
    const box     = document.getElementById('timerBox');
    const timeBar = document.getElementById('timeBar');
    const timeVal = document.getElementById('timeVal');

    display.textContent = formatTime(secondsLeft);

    if (secondsLeft <= 60) {
        box.className = 'timer-box timer-danger';
        timeBar.className = 'prog-fill fill-time danger';
    } else if (secondsLeft <= 180) {
        box.className = 'timer-box timer-warning';
        timeBar.className = 'prog-fill fill-time warn';
    }

    const timePct = totalSeconds > 0 ? (secondsLeft / totalSeconds) * 100 : 0;
    timeBar.style.width = timePct + '%';
    timeVal.textContent = secondsLeft < 60 ? secondsLeft + 's' : Math.ceil(secondsLeft / 60) + 'm';

    if (secondsLeft <= 0) {
        clearInterval(timerInterval);
        if (AUTO_SUBMIT && !submitted) triggerAutoSubmit();
    }
}

timerInterval = setInterval(tick, 1000);
document.getElementById('timerDisplay').textContent = formatTime(secondsLeft);

function triggerAutoSubmit() {
    submitted = true;
    window.onbeforeunload = null;
    document.getElementById('focusOverlay').style.display = 'none';
    document.getElementById('autoSubmitModal').style.display = 'flex';
    let c = 3;
    const cd = document.getElementById('autoCountdown');
    const interval = setInterval(() => {
        c--;
        cd.textContent = c;
        if (c <= 0) { clearInterval(interval); document.getElementById('quizForm').submit(); }
    }, 1000);
}

function onAnswer(qId, optIdx) {
    answered[qId] = optIdx;

    document.querySelectorAll(`[id^="opt_${qId}_"]`).forEach(el => el.classList.remove('selected'));
    document.getElementById(`opt_${qId}_${optIdx}`)?.classList.add('selected');
    document.getElementById(`qcard_${qId}`)?.classList.add('answered');

    const tag = document.getElementById(`qtag_${qId}`);
    if (tag) tag.style.display = 'inline-flex';

    const dot = document.getElementById(`dot_${qId}`);
    if (dot) dot.classList.add('answered');

    const count = Object.keys(answered).length;
    document.getElementById('answeredCount').textContent = count;
    document.getElementById('answerBar').style.width = (count / TOTAL_QUESTIONS * 100) + '%';
    document.getElementById('answerVal').textContent = count + ' / ' + TOTAL_QUESTIONS;
}

function scrollToQ(qId) {
    document.getElementById(`qcard_${qId}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

if (ENFORCE_FOCUS) {
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && !submitted) { focusWarnings++; showFocusWarning(); }
    });
    window.addEventListener('blur', () => {
        if (!submitted) { focusWarnings++; showFocusWarning(); }
    });
}

function showFocusWarning() {
    document.getElementById('warnCount').textContent = 'Warning #' + focusWarnings;
    document.getElementById('focusOverlay').style.display = 'flex';
}

function dismissFocusWarning() {
    document.getElementById('focusOverlay').style.display = 'none';
    window.focus();
}

document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('keydown', e => {
    if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && ['I','J','C'].includes(e.key)) || (e.ctrlKey && ['u','s'].includes(e.key))) {
        e.preventDefault();
    }
});

window.addEventListener('beforeunload', e => {
    if (secondsLeft > 0 && !submitted) {
        e.preventDefault();
        e.returnValue = 'Your quiz is in progress. Leaving will not submit your answers.';
    }
});

document.getElementById('quizForm').addEventListener('submit', () => {
    submitted = true;
    window.onbeforeunload = null;
    clearInterval(timerInterval);
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting…';
});
</script>
</body>
</html>
