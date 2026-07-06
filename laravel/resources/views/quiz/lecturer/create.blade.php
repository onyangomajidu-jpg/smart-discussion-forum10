<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz — Smart Discussion Forum</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',sans-serif;background:#f0f2ff;color:#333}

        /* ── Navbar ─────────────────────────────────────────────────────── */
        .navbar{background:linear-gradient(135deg,#667eea,#764ba2);padding:16px 28px;color:#fff;
            display:flex;justify-content:space-between;align-items:center;
            box-shadow:0 2px 12px rgba(0,0,0,.2)}
        .navbar h1{font-size:19px;font-weight:700}
        .navbar a{color:#fff;text-decoration:none;font-size:13px;opacity:.85;
            padding:7px 14px;border:1px solid rgba(255,255,255,.35);border-radius:6px;transition:.2s}
        .navbar a:hover{background:rgba(255,255,255,.15)}

        /* ── Layout ─────────────────────────────────────────────────────── */
        .container{max-width:920px;margin:32px auto;padding:0 20px}

        /* ── Section label ──────────────────────────────────────────────── */
        .section-label{font-size:11px;font-weight:700;text-transform:uppercase;
            letter-spacing:1px;color:#667eea;margin-bottom:14px}

        /* ── Card ───────────────────────────────────────────────────────── */
        .card{background:#fff;border-radius:14px;padding:28px 32px;
            box-shadow:0 2px 14px rgba(102,126,234,.1);margin-bottom:22px}
        .card-header{display:flex;justify-content:space-between;align-items:center;
            margin-bottom:22px;padding-bottom:14px;border-bottom:2px solid #f0f2ff}
        .card-header h2{font-size:17px;color:#333;font-weight:700}
        .badge-sdd{background:#eef0ff;color:#667eea;padding:3px 10px;
            border-radius:10px;font-size:11px;font-weight:700}

        /* ── Form elements ──────────────────────────────────────────────── */
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px}
        .form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;margin-bottom:18px}
        .form-group{margin-bottom:18px}
        .form-group label{display:block;font-weight:600;font-size:12px;
            text-transform:uppercase;letter-spacing:.5px;margin-bottom:7px;color:#555}
        .form-group input,.form-group select,.form-group textarea{
            width:100%;padding:11px 14px;border:2px solid #e8eaf0;border-radius:9px;
            font-size:14px;color:#333;transition:border-color .2s,box-shadow .2s;background:#fafbff}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{
            outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.12);background:#fff}
        .form-group textarea{resize:vertical;min-height:80px}
        .hint{font-size:11px;color:#9ca3af;margin-top:5px;line-height:1.4}

        /* ── Toggle switches ────────────────────────────────────────────── */
        .toggle-group{display:flex;flex-direction:column;gap:12px;margin-bottom:18px}
        .toggle-item{display:flex;align-items:flex-start;gap:14px;
            padding:14px 16px;background:#fafbff;border:2px solid #e8eaf0;
            border-radius:10px;cursor:pointer;transition:.2s}
        .toggle-item:hover{border-color:#667eea;background:#f0f2ff}
        .toggle-item input[type=checkbox]{width:18px;height:18px;margin-top:2px;
            accent-color:#667eea;cursor:pointer;flex-shrink:0}
        .toggle-item .toggle-text strong{display:block;font-size:14px;color:#333;margin-bottom:3px}
        .toggle-item .toggle-text span{font-size:12px;color:#6c757d}

        /* ── Lifecycle info bar ─────────────────────────────────────────── */
        .lifecycle-bar{display:flex;align-items:center;gap:0;margin-bottom:22px;
            background:#fafbff;border:2px solid #e8eaf0;border-radius:10px;overflow:hidden}
        .lc-step{flex:1;padding:12px 10px;text-align:center;font-size:11px;
            font-weight:700;color:#9ca3af;border-right:1px solid #e8eaf0;transition:.2s}
        .lc-step:last-child{border-right:none}
        .lc-step.active{background:#667eea;color:#fff}
        .lc-step .lc-icon{font-size:16px;display:block;margin-bottom:3px}

        /* ── Question block ─────────────────────────────────────────────── */
        .question-block{background:#fafbff;border:2px solid #e8eaf0;border-radius:12px;
            padding:22px;margin-bottom:16px;position:relative;transition:.2s}
        .question-block:hover{border-color:#c7d0f8}
        .q-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
        .q-num{font-size:12px;font-weight:700;color:#667eea;text-transform:uppercase;letter-spacing:.5px}
        .options-list{display:flex;flex-direction:column;gap:9px;margin-bottom:14px}
        .option-row{display:flex;align-items:center;gap:10px}
        .option-row input[type=text]{flex:1;padding:9px 12px;border:1.5px solid #e8eaf0;
            border-radius:7px;font-size:13px;background:#fff;transition:.2s}
        .option-row input[type=text]:focus{outline:none;border-color:#667eea}
        .option-row input[type=radio]{accent-color:#667eea;width:17px;height:17px;cursor:pointer;flex-shrink:0}
        .correct-label{font-size:11px;color:#667eea;font-weight:600;white-space:nowrap}

        /* ── Buttons ────────────────────────────────────────────────────── */
        .btn{padding:11px 24px;border:none;border-radius:9px;font-size:14px;
            font-weight:600;cursor:pointer;transition:all .2s;text-decoration:none;display:inline-block}
        .btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;
            box-shadow:0 4px 14px rgba(102,126,234,.35)}
        .btn-primary:hover{opacity:.9;transform:translateY(-1px)}
        .btn-secondary{background:#f0f2ff;color:#667eea;border:2px solid #e8eaf0}
        .btn-secondary:hover{background:#e8eaf0}
        .btn-danger{background:#fff0f0;color:#dc3545;border:1.5px solid #f5c6cb;
            padding:6px 13px;font-size:12px;border-radius:7px}
        .btn-danger:hover{background:#f8d7da}
        .btn-add{background:#f0f2ff;color:#667eea;border:2px dashed #c7d0f8;
            width:100%;padding:12px;border-radius:10px;font-size:14px;font-weight:600;
            cursor:pointer;transition:.2s}
        .btn-add:hover{background:#e8eaf0;border-color:#667eea}
        .form-actions{display:flex;gap:14px;margin-top:8px;align-items:center}

        /* ── Alerts ─────────────────────────────────────────────────────── */
        .alert-error{background:#fff0f0;color:#721c24;padding:14px 18px;
            border-radius:10px;margin-bottom:20px;font-size:14px;
            border-left:4px solid #dc3545}
        .alert-error ul{margin:6px 0 0 16px}
    </style>
</head>
<body>

<nav class="navbar">
    <h1>🎓 Smart Discussion Forum</h1>
    <a href="{{ route('lecturer.dashboard') }}">← Dashboard</a>
</nav>

<div class="container">

    @if($errors->any())
        <div class="alert-error">
            <strong>Please fix the following errors:</strong>
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- ── Quiz Lifecycle Indicator (SDD Fig 3.12) ──────────────────────── --}}
    <div class="lifecycle-bar">
        <div class="lc-step active"><span class="lc-icon">✏️</span>1. Create Draft</div>
        <div class="lc-step"><span class="lc-icon">🚀</span>2. Publish</div>
        <div class="lc-step"><span class="lc-icon">🔔</span>3. Remind</div>
        <div class="lc-step"><span class="lc-icon">📝</span>4. Students Attempt</div>
        <div class="lc-step"><span class="lc-icon">📊</span>5. Results</div>
    </div>

    <form action="{{ route('lecturer.quizzes.store') }}" method="POST" id="quizForm">
        @csrf

        {{-- ── Quiz Details (SDD Fig 6.4) ──────────────────────────────── --}}
        <div class="card">
            <div class="card-header">
                <h2>📝 Quiz Details</h2>
                <span class="badge-sdd">SDD Fig 6.4</span>
            </div>

            <div class="form-group">
                <label>Group</label>
                <select name="group_id" required>
                    <option value="">— Select a group —</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Quiz Title</label>
                <input type="text" name="title" value="{{ old('title') }}"
                       required placeholder="e.g. Week 5 — Data Structures Assessment">
            </div>

            <div class="form-group">
                <label>Description / Instructions</label>
                <textarea name="description" placeholder="Instructions shown to students before they begin…">{{ old('description') }}</textarea>
            </div>

            {{-- ── Scheduling ──────────────────────────────────────────── --}}
            <div class="section-label">⏰ Scheduling (SDD Fig 3.12)</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Unlock Date</label>
                    <input type="datetime-local" name="unlock_date" value="{{ old('unlock_date') }}">
                    <p class="hint">Students can see and start the quiz from this date/time. Leave blank to open immediately on publish.</p>
                </div>
                <div class="form-group">
                    <label>Hard Deadline</label>
                    <input type="datetime-local" name="hard_deadline" value="{{ old('hard_deadline') }}">
                    <p class="hint">Absolute cut-off. Auto-submit fires at this time regardless of student progress.</p>
                </div>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label>Duration (minutes)</label>
                    <input type="number" name="duration_minutes"
                           value="{{ old('duration_minutes', 15) }}"
                           min="1" max="180" required>
                    <p class="hint">Default: 15 min per SDD specification.</p>
                </div>
            </div>

            {{-- ── Behaviour Toggles ────────────────────────────────────── --}}
            <div class="section-label">⚙️ Quiz Behaviour</div>
            <div class="toggle-group">
                <label class="toggle-item">
                    <input type="checkbox" name="auto_submit" value="1"
                           {{ old('auto_submit', '1') ? 'checked' : '' }}>
                    <div class="toggle-text">
                        <strong>⏱ Auto-submit on timer expiry</strong>
                        <span>Student's answers are submitted automatically when the countdown reaches zero, even if they haven't clicked Submit.</span>
                    </div>
                </label>
                <label class="toggle-item">
                    <input type="checkbox" name="enforce_focus" value="1"
                           {{ old('enforce_focus', '1') ? 'checked' : '' }}>
                    <div class="toggle-text">
                        <strong>🔒 Enforce focused-window isolation</strong>
                        <span>A full-screen warning overlay appears if the student switches tabs, minimises the window, or loses focus during the quiz.</span>
                    </div>
                </label>
            </div>
        </div>

        {{-- ── Questions ────────────────────────────────────────────────── --}}
        <div class="card">
            <div class="card-header">
                <h2>❓ Questions</h2>
                <span class="badge-sdd">MCQ — select correct answer</span>
            </div>

            <div id="questionsContainer">
                @if(old('questions'))
                    @foreach(old('questions') as $qi => $q)
                        <div class="question-block" id="q_{{ $qi }}">
                            <div class="q-header">
                                <span class="q-num">Question {{ $qi + 1 }}</span>
                                <button type="button" class="btn-danger" onclick="removeQuestion({{ $qi }})">✕ Remove</button>
                            </div>
                            <div class="form-group">
                                <label>Question Text</label>
                                <textarea name="questions[{{ $qi }}][question]" required
                                          placeholder="Enter your question…">{{ $q['question'] ?? '' }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>Options &nbsp;<span style="font-weight:400;color:#9ca3af">(click the radio button to mark the correct answer)</span></label>
                                <div class="options-list" id="opts_{{ $qi }}">
                                    @foreach($q['options'] ?? ['','','',''] as $oi => $opt)
                                        <div class="option-row">
                                            <input type="radio"
                                                   name="questions[{{ $qi }}][correct_option]"
                                                   value="{{ $oi }}"
                                                   title="Mark as correct"
                                                   {{ ($q['correct_option'] ?? -1) == $oi ? 'checked' : '' }} required>
                                            <input type="text"
                                                   name="questions[{{ $qi }}][options][]"
                                                   value="{{ $opt }}"
                                                   placeholder="Option {{ $oi + 1 }}" required>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="form-group" style="max-width:160px">
                                <label>Marks</label>
                                <input type="number" name="questions[{{ $qi }}][marks]"
                                       value="{{ $q['marks'] ?? 1 }}" min="1" max="100" required>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <button type="button" class="btn-add" onclick="addQuestion()">+ Add Question</button>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Save Draft</button>
            <a href="{{ route('lecturer.dashboard') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
let qIndex = {{ old('questions') ? count(old('questions')) : 0 }};

function addQuestion() {
    const i = qIndex++;
    const num = document.querySelectorAll('.question-block').length + 1;
    const html = `
    <div class="question-block" id="q_${i}">
        <div class="q-header">
            <span class="q-num">Question ${num}</span>
            <button type="button" class="btn-danger" onclick="removeQuestion(${i})">✕ Remove</button>
        </div>
        <div class="form-group">
            <label>Question Text</label>
            <textarea name="questions[${i}][question]" required placeholder="Enter your question…"></textarea>
        </div>
        <div class="form-group">
            <label>Options &nbsp;<span style="font-weight:400;color:#9ca3af">(click the radio button to mark the correct answer)</span></label>
            <div class="options-list" id="opts_${i}">
                ${[0,1,2,3].map(j => `
                <div class="option-row">
                    <input type="radio" name="questions[${i}][correct_option]" value="${j}" title="Mark as correct" required>
                    <input type="text" name="questions[${i}][options][]" placeholder="Option ${j+1}" required>
                </div>`).join('')}
            </div>
        </div>
        <div class="form-group" style="max-width:160px">
            <label>Marks</label>
            <input type="number" name="questions[${i}][marks]" value="1" min="1" max="100" required>
        </div>
    </div>`;
    document.getElementById('questionsContainer').insertAdjacentHTML('beforeend', html);
}

function removeQuestion(i) {
    const el = document.getElementById('q_' + i);
    if (el) el.remove();
}
</script>
</body>
</html>
