@extends('layouts.app')

@section('title', 'My Quizzes — SmartForum')

@push('styles')
<style>
.hero-banner {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 60%, #a78bfa 100%);
    border-radius: 18px;
    padding: 28px 32px;
    margin-bottom: 32px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(99,102,241,.35);
}
.hero-banner::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 220px; height: 220px;
    background: rgba(255,255,255,.08);
    border-radius: 50%;
}
.hero-banner::after {
    content: '';
    position: absolute;
    bottom: -40px; right: 120px;
    width: 140px; height: 140px;
    background: rgba(255,255,255,.05);
    border-radius: 50%;
}
.hero-title { font-size: 26px; font-weight: 900; margin-bottom: 6px; }
.hero-sub { font-size: 14px; opacity: .8; }
.hero-icon { font-size: 72px; opacity: .25; position: absolute; right: 40px; top: 50%; transform: translateY(-50%); }

.quiz-card {
    background: #fff;
    border-radius: 16px;
    border: 1.5px solid #e2e8f0;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
    transition: all .25s;
    overflow: hidden;
    margin-bottom: 16px;
}
.quiz-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 36px rgba(99,102,241,.15);
    border-color: #c7d2fe;
}
.quiz-card-inner { display: flex; align-items: stretch; }
.quiz-card-accent {
    width: 6px;
    flex-shrink: 0;
}
.accent-open     { background: linear-gradient(180deg, #10b981, #059669); }
.accent-upcoming { background: linear-gradient(180deg, #f59e0b, #d97706); }
.accent-closed   { background: linear-gradient(180deg, #ef4444, #dc2626); }
.accent-done     { background: linear-gradient(180deg, #6366f1, #8b5cf6); }

.quiz-card-body { flex: 1; padding: 18px 20px; display: flex; align-items: center; gap: 16px; }
.quiz-icon-wrap {
    width: 56px; height: 56px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; flex-shrink: 0;
}
.icon-open     { background: linear-gradient(135deg,#d1fae5,#a7f3d0); color: #065f46; }
.icon-upcoming { background: linear-gradient(135deg,#fef3c7,#fde68a); color: #92400e; }
.icon-closed   { background: linear-gradient(135deg,#fee2e2,#fecaca); color: #991b1b; }
.icon-done     { background: linear-gradient(135deg,#ede9fe,#ddd6fe); color: #5b21b6; }

.quiz-info { flex: 1; min-width: 0; }
.quiz-title { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 6px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.quiz-meta { display: flex; gap: 18px; flex-wrap: wrap; }
.quiz-meta-item { display: flex; align-items: center; gap: 5px; font-size: 12px; color: #64748b; font-weight: 500; }
.quiz-meta-item i { color: #6366f1; font-size: 11px; }
.quiz-countdown { margin-top: 8px; font-size: 12px; font-weight: 700; color: #f59e0b; display: flex; align-items: center; gap: 5px; }

.quiz-action { padding: 16px 20px; display: flex; align-items: center; flex-shrink: 0; }

.empty-state {
    text-align: center;
    padding: 60px 24px;
    background: #fff;
    border-radius: 18px;
    border: 2px dashed #e2e8f0;
}
@media(max-width:640px) {
    .hero-banner { padding: 20px 18px; }
    .hero-banner::before, .hero-banner::after { display:none; }
    .hero-title { font-size: 20px; }
    .hero-icon { display: none; }
    .quiz-card-inner { flex-direction: column; }
    .quiz-card-accent { width: 100%; height: 5px; }
    .quiz-card-body { padding: 14px 14px 8px; }
    .quiz-action { padding: 0 14px 14px; width: 100%; }
    .quiz-action .btn, .quiz-action button { width: 100%; justify-content: center; }
    .quiz-icon-wrap { width: 44px; height: 44px; font-size: 20px; }
    .filter-bar { gap: 6px; }
    .filter-btn { padding: 6px 12px; font-size: 11px; }
}

.filter-bar { display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap; }
.filter-btn { padding: 7px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; border: 1.5px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; transition: all .2s; display: flex; align-items: center; gap: 5px; }
.filter-btn:hover, .filter-btn.active { background: var(--grad); color: #fff; border-color: transparent; box-shadow: 0 4px 12px rgba(99,102,241,.3); }
</style>
@endpush

@section('content')

<div class="hero-banner">
    <div>
        <div class="hero-title"><i class="fa-solid fa-file-pen" style="margin-right:10px"></i>My Quizzes</div>
        <div class="hero-sub">Track your assessments, deadlines, and results all in one place</div>
    </div>
    <i class="fa-solid fa-brain hero-icon"></i>
</div>

@if($quizzes->isEmpty())
<div class="empty-state">
    <div class="empty-icon"><i class="fa-solid fa-inbox"></i></div>
    <h3>No Quizzes Available</h3>
    <p>There are no published quizzes in your groups right now.<br>Check back later or contact your lecturer.</p>
</div>
@else

<div class="filter-bar">
    <button class="filter-btn active" onclick="filterQuizzes('all', this)"><i class="fa-solid fa-border-all"></i> All</button>
    <button class="filter-btn" onclick="filterQuizzes('open', this)"><i class="fa-solid fa-circle-play"></i> Open</button>
    <button class="filter-btn" onclick="filterQuizzes('upcoming', this)"><i class="fa-solid fa-clock"></i> Upcoming</button>
    <button class="filter-btn" onclick="filterQuizzes('done', this)"><i class="fa-solid fa-circle-check"></i> Completed</button>
    <button class="filter-btn" onclick="filterQuizzes('closed', this)"><i class="fa-solid fa-lock"></i> Closed</button>
</div>

<div id="quizList">
@foreach($quizzes as $quiz)
@php
    $done     = in_array($quiz->id, $attempted);
    $isOpen   = $quiz->isOpen();
    $upcoming = $quiz->isUpcoming();
    $closed   = $quiz->isPastDeadline();
    $state    = $done ? 'done' : ($closed ? 'closed' : ($isOpen ? 'open' : ($upcoming ? 'upcoming' : 'closed')));
@endphp

<div class="quiz-card" data-state="{{ $state }}" data-quiz-id="{{ $quiz->id }}" @if($upcoming && $quiz->unlock_date) data-unlock="{{ $quiz->unlock_date->timestamp }}" @endif>
    <div class="quiz-card-inner">
        <div class="quiz-card-accent accent-{{ $state }}"></div>
        <div class="quiz-card-body">
            <div class="quiz-icon-wrap icon-{{ $state }}">
                @if($done)        <i class="fa-solid fa-circle-check"></i>
                @elseif($isOpen)  <i class="fa-solid fa-play"></i>
                @elseif($upcoming)<i class="fa-solid fa-hourglass-half"></i>
                @else             <i class="fa-solid fa-lock"></i>
                @endif
            </div>
            <div class="quiz-info">
                <div class="quiz-title">
                    {{ $quiz->title }}
                    @if($done)
                        <span class="badge badge-done"><i class="fa-solid fa-check"></i> Submitted</span>
                    @elseif($isOpen)
                        <span class="badge badge-open"><i class="fa-solid fa-circle" style="font-size:7px"></i> Live Now</span>
                    @elseif($upcoming)
                        <span class="badge badge-upcoming"><i class="fa-solid fa-clock"></i> Upcoming</span>
                    @else
                        <span class="badge badge-closed"><i class="fa-solid fa-lock"></i> Closed</span>
                    @endif
                </div>
                <div class="quiz-meta">
                    <span class="quiz-meta-item"><i class="fa-solid fa-users"></i> {{ $quiz->group->name }}</span>
                    <span class="quiz-meta-item"><i class="fa-solid fa-stopwatch"></i> {{ $quiz->duration_minutes }} min</span>
                    <span class="quiz-meta-item"><i class="fa-solid fa-circle-question"></i> {{ $quiz->questions_count }} questions</span>
                    @if($quiz->unlock_date)
                    <span class="quiz-meta-item"><i class="fa-solid fa-unlock"></i> Opens {{ $quiz->unlock_date->format('d M Y, H:i') }}</span>
                    @endif
                    @if($quiz->hard_deadline)
                    <span class="quiz-meta-item"><i class="fa-solid fa-flag-checkered"></i> Due {{ $quiz->hard_deadline->format('d M Y, H:i') }}</span>
                    @endif
                </div>
                @if($upcoming && $quiz->unlock_date)
                <div class="quiz-countdown" data-countdown="{{ $quiz->unlock_date->utc()->timestamp }}" id="cd_{{ $quiz->id }}">
                    <i class="fa-solid fa-timer"></i> Calculating…
                </div>
                @endif
            </div>
        </div>
        <div class="quiz-action">
            @if($done)
                <a href="{{ route('quizzes.result', $quiz) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-chart-bar"></i> View Result</a>
            @elseif($isOpen)
                <a href="{{ route('quizzes.take', $quiz) }}" class="btn btn-primary"><i class="fa-solid fa-play"></i> Start Quiz</a>
            @else
                <button class="btn btn-secondary btn-sm" disabled><i class="fa-solid fa-ban"></i> Unavailable</button>
            @endif
        </div>
    </div>
</div>
@endforeach
</div>

@endif

@endsection

@push('scripts')
<script>
function filterQuizzes(state, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.quiz-card').forEach(card => {
        card.style.display = (state === 'all' || card.dataset.state === state) ? '' : 'none';
    });
}

document.querySelectorAll('[data-countdown]').forEach(el => {
    const target = parseInt(el.dataset.countdown) * 1000;
    const card   = el.closest('.quiz-card');

    function openQuiz() {
        // Update card state visually
        card.dataset.state = 'open';
        card.querySelector('.quiz-card-accent').className = 'quiz-card-accent accent-open';
        card.querySelector('.quiz-icon-wrap').className   = 'quiz-icon-wrap icon-open';
        card.querySelector('.quiz-icon-wrap').innerHTML   = '<i class="fa-solid fa-play"></i>';

        // Swap badge
        const titleEl = card.querySelector('.quiz-title');
        const badge   = titleEl.querySelector('.badge');
        if (badge) badge.outerHTML = '<span class="badge badge-open"><i class="fa-solid fa-circle" style="font-size:7px"></i> Live Now</span>';

        // Swap action button
        const actionEl  = card.querySelector('.quiz-action');
        const quizId    = card.dataset.quizId;
        const startUrl  = `{{ url('/quizzes') }}/${quizId}`;
        actionEl.innerHTML = `<a href="${startUrl}" class="btn btn-primary"><i class="fa-solid fa-play"></i> Start Quiz</a>`;

        // Remove countdown
        el.remove();
    }

    function update() {
        const diff = target - Date.now();
        if (diff <= 0) { openQuiz(); return; }
        const h = Math.floor(diff / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        el.innerHTML = `<i class="fa-solid fa-timer"></i> Opens in: ${h}h ${m}m ${s}s`;
        setTimeout(update, 1000);
    }
    update();
});
</script>
@endpush
