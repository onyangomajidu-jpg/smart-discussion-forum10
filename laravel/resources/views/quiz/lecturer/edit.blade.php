@extends('layouts.app')

@section('title', 'Edit Quiz — ' . $quiz->title)

@push('styles')
<style>
.create-hero {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 28px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 18px;
    box-shadow: 0 8px 28px rgba(245,158,11,.3);
    position: relative;
    overflow: hidden;
}
.create-hero::after {
    content: '\f044';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    right: 32px;
    font-size: 80px;
    opacity: .1;
}
.create-hero .hero-icon-box {
    width: 56px; height: 56px; border-radius: 14px;
    background: rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 26px; flex-shrink: 0;
    border: 1.5px solid rgba(255,255,255,.3);
}
.question-block {
    background: #fafbff;
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 16px;
    position: relative;
    transition: all .2s;
}
.question-block:hover { border-color: #c7d2fe; box-shadow: 0 4px 16px rgba(99,102,241,.08); }
.q-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.q-num-badge {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff; font-size: 11px; font-weight: 700;
    padding: 4px 12px; border-radius: 20px;
    display: flex; align-items: center; gap: 5px;
}
.remove-q {
    background: #fee2e2; color: #ef4444; border: none;
    border-radius: 8px; padding: 6px 12px; font-size: 12px;
    cursor: pointer; font-weight: 600; transition: all .2s;
    display: flex; align-items: center; gap: 5px;
}
.remove-q:hover { background: #ef4444; color: #fff; }
.option-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.option-letter {
    width: 30px; height: 30px; border-radius: 8px;
    background: #e2e8f0; color: #64748b;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; flex-shrink: 0;
    transition: all .2s;
}
.option-row input[type=radio] { accent-color: #6366f1; width: 17px; height: 17px; flex-shrink: 0; cursor: pointer; }
.option-row input[type=radio]:checked + .option-letter { background: #6366f1; color: #fff; }
.option-row input[type=text] {
    flex: 1; padding: 9px 13px;
    border: 2px solid #e2e8f0; border-radius: 9px;
    font-size: 13px; font-family: inherit; transition: all .2s;
}
.option-row input[type=text]:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.correct-hint { font-size: 11px; color: #64748b; margin-bottom: 10px; display: flex; align-items: center; gap: 5px; }
.add-q-btn {
    border: 2px dashed #c7d2fe;
    background: linear-gradient(135deg, rgba(99,102,241,.03), rgba(139,92,246,.03));
    color: #6366f1; padding: 14px 20px;
    border-radius: 12px; font-size: 13px; font-weight: 700;
    cursor: pointer; width: 100%; transition: all .2s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    font-family: inherit;
}
.add-q-btn:hover { background: rgba(99,102,241,.08); border-color: #6366f1; transform: translateY(-1px); }
.toggle-card {
    background: #fafbff;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 18px;
    margin-bottom: 12px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    cursor: pointer;
    transition: all .2s;
}
.toggle-card:hover { border-color: #c7d2fe; }
.toggle-card:has(input:checked) { border-color: #6366f1; background: rgba(99,102,241,.04); }
.toggle-card input[type=checkbox] { width: 18px; height: 18px; accent-color: #6366f1; margin-top: 2px; flex-shrink: 0; cursor: pointer; }
.toggle-icon { font-size: 22px; flex-shrink: 0; }
.toggle-info h4 { font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 3px; }
.toggle-info p  { font-size: 12px; color: #64748b; line-height: 1.5; }
.summary-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
.summary-row:last-child { border-bottom: none; }
.summary-row .s-label { font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 7px; }
.summary-row .s-val { font-size: 15px; font-weight: 800; color: #f59e0b; }
.marks-input { width: 90px !important; }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('lecturer.dashboard') }}"><i class="fa-solid fa-house"></i> Dashboard</a>
    <span class="sep"><i class="fa-solid fa-chevron-right" style="font-size:9px"></i></span>
    <a href="{{ route('lecturer.quizzes.show', $quiz) }}">{{ $quiz->title }}</a>
    <span class="sep"><i class="fa-solid fa-chevron-right" style="font-size:9px"></i></span>
    <span>Edit Quiz</span>
</div>

<div class="create-hero">
    <div class="hero-icon-box"><i class="fa-solid fa-pen-to-square"></i></div>
    <div>
        <div style="font-size:22px;font-weight:900;margin-bottom:4px">Edit Draft Quiz</div>
        <div style="font-size:13px;opacity:.8">Update questions, settings, and deadlines before publishing</div>
    </div>
</div>

<form action="{{ route('lecturer.quizzes.update', $quiz) }}" method="POST" id="quizForm">
@csrf

<div style="display:grid;grid-template-columns:1fr 380px;gap:22px;align-items:start">

    {{-- LEFT --}}
    <div>
        {{-- Quiz Details --}}
        <div class="card" style="margin-bottom:22px">
            <div class="card-header">
                <h2><i class="fa-solid fa-circle-info"></i> Quiz Details</h2>
                <span class="badge badge-draft"><i class="fa-solid fa-pencil"></i> Draft</span>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-users" style="color:#6366f1;margin-right:5px"></i>Group <span style="color:#ef4444">*</span></label>
                    <select name="group_id" class="form-control {{ $errors->has('group_id') ? 'is-invalid' : '' }}" required>
                        <option value="">— Select a group —</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ old('group_id', $quiz->group_id) == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                    @error('group_id')<div class="invalid-feedback"><i class="fa-solid fa-circle-xmark"></i> {{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-heading" style="color:#6366f1;margin-right:5px"></i>Quiz Title <span style="color:#ef4444">*</span></label>
                    <input type="text" name="title" class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}"
                           value="{{ old('title', $quiz->title) }}" placeholder="e.g. Week 5 — OOP Fundamentals" required>
                    @error('title')<div class="invalid-feedback"><i class="fa-solid fa-circle-xmark"></i> {{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-align-left" style="color:#6366f1;margin-right:5px"></i>Description / Instructions</label>
                    <textarea name="description" class="form-control" rows="3"
                              placeholder="Provide instructions or context for students…">{{ old('description', $quiz->description) }}</textarea>
                </div>

                @php $nowLocal = now()->timezone(config('app.timezone')); @endphp
                <div style="background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:12px;color:#854d0e;display:flex;align-items:center;gap:8px">
                    <i class="fa-solid fa-clock"></i>
                    <span>Now: <strong>{{ $nowLocal->format('h:i A') }}</strong> &mdash; set deadlines <u>after</u> this time.</span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-unlock" style="color:#10b981;margin-right:5px"></i>Unlock Date</label>
                        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                            <input type="date" name="unlock_date_date" class="form-control" style="flex:1;min-width:130px"
                                   value="{{ old('unlock_date_date', $unlockLocal?->format('Y-m-d')) }}">
                            <input type="number" name="unlock_date_hour" class="form-control" style="width:64px" min="1" max="12" placeholder="hh"
                                   value="{{ old('unlock_date_hour', $unlockLocal?->format('g')) }}">
                            <span>:</span>
                            <input type="number" name="unlock_date_min" class="form-control" style="width:64px" min="0" max="59" placeholder="mm"
                                   value="{{ old('unlock_date_min', $unlockLocal?->format('i')) }}">
                            <select name="unlock_date_ampm" class="form-control" style="width:74px">
                                <option value="AM" {{ old('unlock_date_ampm', $unlockLocal?->format('A')) === 'AM' ? 'selected' : '' }}>AM</option>
                                <option value="PM" {{ old('unlock_date_ampm', $unlockLocal?->format('A')) === 'PM' ? 'selected' : '' }}>PM</option>
                            </select>
                        </div>
                        <p class="form-hint"><i class="fa-solid fa-circle-info"></i> Leave blank to open immediately on publish.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-flag-checkered" style="color:#ef4444;margin-right:5px"></i>Hard Deadline</label>
                        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                            <input type="date" name="hard_deadline_date" class="form-control" style="flex:1;min-width:130px"
                                   value="{{ old('hard_deadline_date', $deadlineLocal?->format('Y-m-d')) }}">
                            <input type="number" name="hard_deadline_hour" class="form-control" style="width:64px" min="1" max="12" placeholder="hh"
                                   value="{{ old('hard_deadline_hour', $deadlineLocal?->format('g')) }}">
                            <span>:</span>
                            <input type="number" name="hard_deadline_min" class="form-control" style="width:64px" min="0" max="59" placeholder="mm"
                                   value="{{ old('hard_deadline_min', $deadlineLocal?->format('i')) }}">
                            <select name="hard_deadline_ampm" class="form-control" style="width:74px">
                                <option value="AM" {{ old('hard_deadline_ampm', $deadlineLocal?->format('A')) === 'AM' ? 'selected' : '' }}>AM</option>
                                <option value="PM" {{ old('hard_deadline_ampm', $deadlineLocal?->format('A')) === 'PM' ? 'selected' : '' }}>PM</option>
                            </select>
                        </div>
                        @error('hard_deadline_date')
                            <div class="invalid-feedback" style="display:block"><i class="fa-solid fa-circle-xmark"></i> {{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label"><i class="fa-solid fa-stopwatch" style="color:#f59e0b;margin-right:5px"></i>Duration <span style="color:#ef4444">*</span></label>
                    <div style="display:flex;align-items:center;gap:12px">
                        <input type="number" name="duration_minutes" class="form-control"
                               style="width:110px" value="{{ old('duration_minutes', $quiz->duration_minutes) }}" min="1" max="180" required
                               oninput="document.getElementById('sumD').textContent=this.value+' min'">
                        <span style="font-size:13px;color:#64748b">minutes per attempt</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Questions --}}
        <div class="card">
            <div class="card-header">
                <h2><i class="fa-solid fa-circle-question"></i> Questions</h2>
                <span id="qCount" style="font-size:12px;color:#64748b;font-weight:600">0 questions</span>
            </div>
            <div class="card-body">
                <div id="questionsContainer"></div>
                <button type="button" class="add-q-btn" onclick="addQuestion()">
                    <i class="fa-solid fa-circle-plus"></i> Add New Question
                </button>
            </div>
        </div>
    </div>

    {{-- RIGHT --}}
    <div>
        <div class="card" style="margin-bottom:20px">
            <div class="card-header"><h2><i class="fa-solid fa-sliders"></i> Quiz Settings</h2></div>
            <div class="card-body">
                <label class="toggle-card">
                    <input type="checkbox" name="auto_submit" value="1" {{ old('auto_submit', $quiz->auto_submit) ? 'checked' : '' }}>
                    <div class="toggle-icon">⏱️</div>
                    <div class="toggle-info">
                        <h4>Auto-Submit on Expiry</h4>
                        <p>Answers are automatically submitted when the timer reaches zero.</p>
                    </div>
                </label>
                <label class="toggle-card">
                    <input type="checkbox" name="enforce_focus" value="1" {{ old('enforce_focus', $quiz->enforce_focus) ? 'checked' : '' }}>
                    <div class="toggle-icon">🔒</div>
                    <div class="toggle-info">
                        <h4>Focus Lock Mode</h4>
                        <p>Students receive a warning if they switch tabs or windows.</p>
                    </div>
                </label>
            </div>
        </div>

        <div class="card" style="margin-bottom:20px">
            <div class="card-header"><h2><i class="fa-solid fa-chart-pie"></i> Live Summary</h2></div>
            <div class="card-body">
                <div class="summary-row">
                    <span class="s-label"><i class="fa-solid fa-circle-question" style="color:#6366f1"></i> Questions</span>
                    <span class="s-val" id="sumQ">0</span>
                </div>
                <div class="summary-row">
                    <span class="s-label"><i class="fa-solid fa-star" style="color:#f59e0b"></i> Total Marks</span>
                    <span class="s-val" id="sumM">0</span>
                </div>
                <div class="summary-row">
                    <span class="s-label"><i class="fa-solid fa-stopwatch" style="color:#10b981"></i> Duration</span>
                    <span class="s-val" id="sumD">{{ $quiz->duration_minutes }} min</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:13px">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
                <a href="{{ route('lecturer.quizzes.show', $quiz) }}" class="btn btn-secondary" style="width:100%;justify-content:center">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</div>
</form>

@endsection

@push('scripts')
<script>
let qIdx = 0;

const existingQuestions = @json($quiz->questions);

function addQuestion(data = null) {
    const i = qIdx++;
    const letters = ['A','B','C','D'];
    const opts = letters.map((l, j) => {
        const val = data?.options?.[j] ?? '';
        const checked = data && parseInt(data.correct_option) === j ? 'checked' : '';
        return `
        <div class="option-row">
            <input type="radio" name="questions[${i}][correct_option]" value="${j}" ${checked} required>
            <div class="option-letter">${l}</div>
            <input type="text" name="questions[${i}][options][]" value="${val.replace(/"/g,'&quot;')}"
                   placeholder="Option ${l}" required>
        </div>`;
    }).join('');

    const html = `
    <div class="question-block" id="qb_${i}">
        <div class="q-header">
            <span class="q-num-badge"><i class="fa-solid fa-circle-question"></i> Question ${document.querySelectorAll('.question-block').length + 1}</span>
            <button type="button" class="remove-q" onclick="removeQ(${i})"><i class="fa-solid fa-trash"></i> Remove</button>
        </div>
        <div class="form-group">
            <label class="form-label">Question Text</label>
            <textarea name="questions[${i}][question]" class="form-control" rows="2"
                      placeholder="Type your question here…" required oninput="updateSummary()">${data?.question ?? ''}</textarea>
        </div>
        <div class="form-group">
            <div class="correct-hint"><i class="fa-solid fa-circle-dot" style="color:#6366f1"></i> Click the radio button to mark the correct answer</div>
            ${opts}
        </div>
        <div class="form-group" style="margin-bottom:0;display:flex;align-items:center;gap:12px">
            <label class="form-label" style="margin-bottom:0;white-space:nowrap"><i class="fa-solid fa-star" style="color:#f59e0b"></i> Marks:</label>
            <input type="number" name="questions[${i}][marks]" class="form-control marks-input"
                   value="${data?.marks ?? 1}" min="1" max="100" required oninput="updateSummary()">
        </div>
    </div>`;

    document.getElementById('questionsContainer').insertAdjacentHTML('beforeend', html);
    updateSummary();
}

function removeQ(i) {
    const el = document.getElementById('qb_' + i);
    if (el) { el.style.opacity = '0'; el.style.transform = 'scale(.95)'; setTimeout(() => { el.remove(); updateSummary(); }, 200); }
}

function updateSummary() {
    const blocks = document.querySelectorAll('.question-block');
    let totalMarks = 0;
    blocks.forEach(b => {
        const m = b.querySelector('input[type=number]');
        if (m) totalMarks += parseInt(m.value) || 0;
    });
    document.getElementById('qCount').textContent = blocks.length + ' question' + (blocks.length !== 1 ? 's' : '');
    document.getElementById('sumQ').textContent = blocks.length;
    document.getElementById('sumM').textContent = totalMarks;
}

// Load existing questions
existingQuestions.forEach(q => addQuestion(q));
</script>
@endpush
