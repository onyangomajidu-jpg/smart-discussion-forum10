<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $quiz->title }} — Quiz in Progress</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',sans-serif;background:#f0f2ff;color:#333;
            user-select:none;-webkit-user-select:none}

        /* ── Lockdown sticky header (SDD Fig 6.6) ───────────────────────── */
        .quiz-header{
            background:linear-gradient(135deg,#667eea,#764ba2);
            color:#fff;padding:14px 28px;
            display:flex;justify-content:space-between;align-items:center;
            position:sticky;top:0;z-index:200;
            box-shadow:0 3px 16px rgba(0,0,0,.25)}
        .header-left .quiz-title{font-size:17px;font-weight:700;margin-bottom:3px}
        .header-left .quiz-meta{font-size:12px;opacity:.8}

        /* ── Timer (SDD Fig 6.6 — 15-min countdown) ─────────────────────── */
        .timer-box{background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.4);
            border-radius:12px;padding:10px 20px;text-align:center;min-width:120px}
        .timer-box .time{font-size:26px;font-weight:700;letter-spacing:3px;font-variant-numeric:tabular-nums}
        .timer-box .lbl{font-size:10px;opacity:.75;text-transform:uppercase;letter-spacing:.5px;margin-top:2px}
        .timer-warning .time{color:#ffc107}
        .timer-danger{animation:timerPulse .8s infinite}
        .timer-danger .time{color:#ff4444}
        @keyframes timerPulse{0%,100%{background:rgba(255,255,255,.15)}50%{background:rgba(220,53,69,.35)}}

        /* ── Dual progress bars ──────────────────────────────────────────── */
        .progress-section{background:#fff;padding:10px 28px;
            border-bottom:1px solid #f0f2ff;display:flex;flex-direction:column;gap:5px}
        .progress-row{display:flex;align-items:center;gap:10px}
        .progress-label{font-size:11px;color:#9ca3af;width:110px;flex-shrink:0;font-weight:600;text-transform:uppercase;letter-spacing:.4px}
        .progress-track{flex:1;background:#f0f2ff;border-radius:4px;height:7px;overflow:hidden}
        .progress-fill{height:100%;border-radius:4px;transition:width .35s}
        .fill-answers{background:linear-gradient(90deg,#667eea,#764ba2)}
        .fill-time{background:linear-gradient(90deg,#28a745,#20c997)}
        .fill-time.warn{background:linear-gradient(90deg,#ffc107,#fd7e14)}
        .fill-time.danger{background:linear-gradient(90deg,#dc3545,#c82333)}
        .progress-val{font-size:11px;color:#9ca3af;width:50px;text-align:right;flex-shrink:0}

        /* ── Content ─────────────────────────────────────────────────────── */
        .container{max-width:800px;margin:28px auto;padding:0 20px}

        /* ── Question card ───────────────────────────────────────────────── */
        .question-card{background:#fff;border-radius:14px;padding:28px;
            box-shadow:0 2px 12px rgba(102,126,234,.08);margin-bottom:20px;
            border-left:5px solid #e8eaf0;transition:.2s}
        .question-card.answered{border-left-color:#667eea}
        .q-number{font-size:11px;color:#667eea;font-weight:700;
            text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;
            display:flex;justify-content:space-between;align-items:center}
        .q-answered-tag{background:#eef0ff;color:#667eea;padding:2px 8px;
            border-radius:6px;font-size:10px}
        .q-text{font-size:16px;font-weight:600;margin-bottom:6px;line-height:1.55;color:#222}
        .q-marks{font-size:12px;color:#9ca3af;margin-bottom:16px}

        /* ── Options ─────────────────────────────────────────────────────── */
        .options{display:flex;flex-direction:column;gap:10px}
        .option-label{display:flex;align-items:center;gap:14px;
            padding:13px 18px;border:2px solid #e8eaf0;border-radius:10px;
            cursor:pointer;transition:all .2s;font-size:14px;color:#444}
        .option-label:hover{border-color:#667eea;background:#f8f9ff}
        .option-label.selected{border-color:#667eea;background:#eef0ff;color:#333;font-weight:500}
        .option-label input[type=radio]{accent-color:#667eea;width:17px;height:17px;flex-shrink:0}

        /* ── Submit bar ──────────────────────────────────────────────────── */
        .submit-bar{background:#fff;border-radius:14px;padding:20px 28px;
            box-shadow:0 2px 12px rgba(102,126,234,.08);
            display:flex;justify-content:space-between;align-items:center;
            margin-bottom:32px}
        .submit-info{font-size:14px;color:#555}
        .submit-info strong{color:#667eea}
        .btn-submit{background:linear-gradient(135deg,#667eea,#764ba2);
            color:#fff;border:none;border-radius:10px;
            padding:13px 32px;font-size:15px;font-weight:700;cursor:pointer;
            transition:all .2s;box-shadow:0 4px 14px rgba(102,126,234,.35)}
        .btn-submit:hover{opacity:.9;transform:translateY(-1px)}

        /* ── Focus warning overlay (SDD Fig 6.6 — interface lockdown) ────── */
        #focusOverlay{display:none;position:fixed;inset:0;
            background:rgba(220,53,69,.95);z-index:9999;
            flex-direction:column;align-items:center;justify-content:center;
            color:#fff;text-align:center;padding:40px}
        #focusOverlay .warn-icon{font-size:64px;margin-bottom:16px}
        #focusOverlay h2{font-size:30px;font-weight:700;margin-bottom:12px}
        #focusOverlay p{font-size:16px;opacity:.9;margin-bottom:8px;max-width:480px;line-height:1.6}
        #focusOverlay .warn-count{font-size:13px;opacity:.7;margin-bottom:24px}
        #focusOverlay button{background:#fff;color:#dc3545;border:none;
            padding:13px 32px;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;
            transition:.2s}
        #focusOverlay button:hover{background:#f8d7da}

        /* ── Auto-submit modal ───────────────────────────────────────────── */
        #autoSubmitModal{display:none;position:fixed;inset:0;
            background:rgba(0,0,0,.65);z-index:9998;
            align-items:center;justify-content:center}
        .modal-box{background:#fff;border-radius:16px;padding:40px;
            text-align:center;max-width:400px;width:90%;
            box-shadow:0 20px 60px rgba(0,0,0,.3)}
        .modal-box .modal-icon{font-size:52px;margin-bottom:14px}
        .modal-box h3{font-size:22px;font-weight:700;margin-bottom:10px;color:#333}
        .modal-box p{font-size:14px;color:#9ca3af;margin-bottom:20px;line-height:1.5}
        .modal-box .countdown{font-size:52px;font-weight:700;color:#dc3545;
            margin-bottom:8px;font-variant-numeric:tabular-nums}
        .modal-box .countdown-label{font-size:12px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px}
    </style>
</head>
<body>

{{-- ── Focus warning overlay (SDD Fig 6.6 — interface lockdown mode) ──────── --}}
<div id="focusOverlay">
    <div class="warn-icon">⚠️</div>
    <h2>Focus Warning!</h2>
    <p>You have left the quiz window. This violation has been recorded.<br>
       Please remain on this page for the entire duration of the quiz.</p>
    <p class="warn-count" id="warnCount">Warning #1</p>
    <button onclick="dismissFocusWarning()">↩ Return to Quiz</button>
</div>

{{-- ── Auto-submit countdown modal ─────────────────────────────────────────── --}}
<div id="autoSubmitModal">
    <div class="modal-box">
        <div class="modal-icon">⏰</div>
        <h3>Time's Up!</h3>
        <p>Your quiz is being submitted automatically as per the quiz settings.</p>
        <div class="countdown" id="autoCountdown">3</div>
        <div class="countdown-label">Submitting in…</div>
    </div>
</div>

{{-- ── Sticky lockdown header ────────────────────────────────────────────────── --}}
<div class="quiz-header">
    <div class="header-left">
        <div class="quiz-title">{{ $quiz->title }}</div>
        <div class="quiz-meta">
            {{ $quiz->group->name }}
            &nbsp;·&nbsp; {{ $quiz->questions->count() }} questions
            &nbsp;·&nbsp; {{ $quiz->totalMarks() }} marks
            @if($quiz->enforce_focus)
                &nbsp;·&nbsp; 🔒 Lockdown Mode
            @endif
        </div>
    </div>
    <div class="timer-box" id="timerBox">
        <div class="time" id="timerDisplay">--:--</div>
        <div class="lbl">Time Left</div>
    </div>
</div>

{{-- ── Dual progress tracker (SDD Fig 6.6 — progress tracker) ─────────────── --}}
<div class="progress-section">
    <div class="progress-row">
        <span class="progress-label">Answered</span>
        <div class="progress-track">
            <div class="progress-fill fill-answers" id="answerBar" style="width:0%"></div>
        </div>
        <span class="progress-val" id="answerVal">0 / {{ $quiz->questions->count() }}</span>
    </div>
    <div class="progress-row">
        <span class="progress-label">Time</span>
        <div class="progress-track">
            <div class="progress-fill fill-time" id="timeBar" style="width:100%"></div>
        </div>
        <span class="progress-val" id="timeVal">{{ $quiz->duration_minutes }}m</span>
    </div>
</div>

{{-- ── Quiz form ─────────────────────────────────────────────────────────────── --}}
<div class="container">
    <form action="{{ route('quizzes.submit', $quiz) }}" method="POST" id="quizForm">
        @csrf

        @foreach($quiz->questions as $i => $question)
            <div class="question-card" id="qcard_{{ $question->id }}">
                <div class="q-number">
                    <span>Question {{ $i + 1 }} of {{ $quiz->questions->count() }}</span>
                    <span class="q-answered-tag" id="qtag_{{ $question->id }}" style="display:none">✓ Answered</span>
                </div>
                <div class="q-text">{{ $question->question }}</div>
                <div class="q-marks">{{ $question->marks }} mark{{ $question->marks > 1 ? 's' : '' }}</div>

                <div class="options">
                    @foreach($question->options as $oi => $opt)
                        <label class="option-label" id="opt_{{ $question->id }}_{{ $oi }}">
                            <input type="radio"
                                   name="answers[{{ $question->id }}]"
                                   value="{{ $oi }}"
                                   onchange="onAnswer({{ $question->id }}, {{ $oi }})">
                            {{ $opt }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- ── Submit bar ──────────────────────────────────────────────────── --}}
        <div class="submit-bar">
            <div class="submit-info">
                <strong id="answeredCount">0</strong> of {{ $quiz->questions->count() }} questions answered
            </div>
            <button type="submit" class="btn-submit" id="submitBtn">
                Submit Quiz ✓
            </button>
        </div>

    </form>
</div>

<script>
// ── Configuration (passed from QuizController) ────────────────────────────
const TIMER_SECONDS    = {{ $timerSeconds }};
const AUTO_SUBMIT      = {{ $quiz->auto_submit ? 'true' : 'false' }};
const ENFORCE_FOCUS    = {{ $enforceFocus ? 'true' : 'false' }};
const TOTAL_QUESTIONS  = {{ $quiz->questions->count() }};
const DEADLINE_MS      = {{ $deadlineEpoch ?? 'null' }};

// ── State ─────────────────────────────────────────────────────────────────
let secondsLeft    = TIMER_SECONDS;
const totalSeconds = TIMER_SECONDS;
let answered       = {};
let focusWarnings  = 0;
let submitted      = false;
let timerInterval;

// ── Timer ─────────────────────────────────────────────────────────────────
function formatTime(s) {
    const m   = Math.floor(s / 60).toString().padStart(2, '0');
    const sec = (s % 60).toString().padStart(2, '0');
    return m + ':' + sec;
}

function tick() {
    // Sync with hard deadline if set
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

    // Timer colour states
    if (secondsLeft <= 60) {
        box.className = 'timer-box timer-danger';
        timeBar.className = 'progress-fill fill-time danger';
    } else if (secondsLeft <= 180) {
        box.className = 'timer-box timer-warning';
        timeBar.className = 'progress-fill fill-time warn';
    }

    // Time progress bar
    const timePct = totalSeconds > 0 ? (secondsLeft / totalSeconds) * 100 : 0;
    timeBar.style.width = timePct + '%';
    timeVal.textContent = secondsLeft < 60
        ? secondsLeft + 's'
        : Math.ceil(secondsLeft / 60) + 'm';

    if (secondsLeft <= 0) {
        clearInterval(timerInterval);
        if (AUTO_SUBMIT && !submitted) triggerAutoSubmit();
    }
}

timerInterval = setInterval(tick, 1000);
document.getElementById('timerDisplay').textContent = formatTime(secondsLeft);

// ── Auto-submit (SDD Fig 6.6 — auto-submit on expiry) ────────────────────
function triggerAutoSubmit() {
    submitted = true;
    window.onbeforeunload = null;
    document.getElementById('autoSubmitModal').style.display = 'flex';
    let c = 3;
    const cd = document.getElementById('autoCountdown');
    const interval = setInterval(() => {
        c--;
        cd.textContent = c;
        if (c <= 0) {
            clearInterval(interval);
            document.getElementById('quizForm').submit();
        }
    }, 1000);
}

// ── Answer tracking + progress tracker (SDD Fig 6.6) ─────────────────────
function onAnswer(qId, optIdx) {
    const wasAnswered = answered.hasOwnProperty(qId);
    answered[qId] = optIdx;

    // Highlight selected option, clear others for this question
    document.querySelectorAll(`[id^="opt_${qId}_"]`).forEach(el => el.classList.remove('selected'));
    const sel = document.getElementById(`opt_${qId}_${optIdx}`);
    if (sel) sel.classList.add('selected');

    // Mark question card as answered
    const card = document.getElementById(`qcard_${qId}`);
    if (card) card.classList.add('answered');

    // Show answered tag
    const tag = document.getElementById(`qtag_${qId}`);
    if (tag) tag.style.display = 'inline-block';

    // Update answer progress bar
    const count = Object.keys(answered).length;
    document.getElementById('answeredCount').textContent = count;
    const pct = (count / TOTAL_QUESTIONS) * 100;
    document.getElementById('answerBar').style.width = pct + '%';
    document.getElementById('answerVal').textContent = count + ' / ' + TOTAL_QUESTIONS;
}

// ── Focused-window isolation (SDD Fig 6.6 — interface lockdown mode) ──────
if (ENFORCE_FOCUS) {
    document.addEventListener('visibilitychange', function () {
        if (document.hidden && !submitted) {
            focusWarnings++;
            showFocusWarning();
        }
    });

    window.addEventListener('blur', function () {
        if (!submitted) {
            focusWarnings++;
            showFocusWarning();
        }
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

// ── Security: block dev tools, right-click, text selection ───────────────
document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('keydown', function (e) {
    if (
        e.key === 'F12' ||
        (e.ctrlKey && e.shiftKey && ['I','J','C'].includes(e.key)) ||
        (e.ctrlKey && e.key === 'u') ||
        (e.ctrlKey && e.key === 's')
    ) {
        e.preventDefault();
    }
});

// ── Warn before leaving mid-quiz ──────────────────────────────────────────
window.addEventListener('beforeunload', function (e) {
    if (secondsLeft > 0 && !submitted) {
        e.preventDefault();
        e.returnValue = 'Your quiz is in progress. Leaving will not submit your answers.';
    }
});

// ── Clear guards on intentional submit ───────────────────────────────────
document.getElementById('quizForm').addEventListener('submit', function () {
    submitted = true;
    window.onbeforeunload = null;
    clearInterval(timerInterval);
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').textContent = 'Submitting…';
});
</script>
</body>
</html>
