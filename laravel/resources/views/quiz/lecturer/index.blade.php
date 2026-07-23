@extends('layouts.app')

@section('title', 'My Quizzes — SmartForum')

@push('styles')
<style>
.quiz-row-card {
    background:#fff;border-radius:14px;border:1.5px solid #e2e8f0;
    padding:20px 24px;margin-bottom:14px;
    display:flex;align-items:flex-start;gap:16px;
    transition:all .2s;
    flex-wrap:wrap;
}
.quiz-row-card:hover { transform:translateY(-2px);box-shadow:0 8px 28px rgba(99,102,241,.12);border-color:#c7d2fe; }
.quiz-icon {
    width:50px;height:50px;border-radius:12px;
    display:flex;align-items:center;justify-content:center;
    font-size:22px;flex-shrink:0;
}
.icon-published { background:linear-gradient(135deg,#d1fae5,#a7f3d0);color:#065f46; }
.icon-draft     { background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e; }
.icon-closed    { background:linear-gradient(135deg,#fee2e2,#fecaca);color:#991b1b; }
.quiz-row-actions { display:flex;gap:8px;flex-shrink:0;flex-wrap:wrap; }
@media(max-width:640px) {
    .quiz-row-card { padding:14px; }
    .quiz-row-actions { width:100%;justify-content:flex-end; }
    .page-header-row { flex-direction:column;align-items:flex-start;gap:12px; }
}
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('lecturer.dashboard') }}"><i class="fa-solid fa-house"></i> Dashboard</a>
    <span class="sep"><i class="fa-solid fa-chevron-right" style="font-size:9px"></i></span>
    <span>My Quizzes</span>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px" class="page-header-row">
    <div class="page-header" style="margin-bottom:0">
        <h1><i class="fa-solid fa-clipboard-list" style="color:#6366f1"></i> My Quizzes</h1>
        <p>All quizzes you have created</p>
    </div>
    <a href="{{ route('lecturer.quizzes.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-circle-plus"></i> Create New Quiz
    </a>
</div>

@if($quizzes->isEmpty())
<div style="text-align:center;padding:80px 40px;background:#fff;border-radius:18px;border:2px dashed #e2e8f0">
    <i class="fa-solid fa-clipboard" style="font-size:56px;color:#c7d2fe;margin-bottom:20px;display:block"></i>
    <h3 style="font-size:20px;font-weight:700;margin-bottom:8px">No quizzes yet</h3>
    <p style="color:#64748b;margin-bottom:24px">Create your first quiz to get started.</p>
    <a href="{{ route('lecturer.quizzes.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-circle-plus"></i> Create Quiz
    </a>
</div>
@else

<div>
@foreach($quizzes as $quiz)
<div class="quiz-row-card">
    <div class="quiz-icon icon-{{ $quiz->status }}">
        @if($quiz->status === 'published') <i class="fa-solid fa-circle-play"></i>
        @elseif($quiz->status === 'draft')  <i class="fa-solid fa-pencil"></i>
        @else                               <i class="fa-solid fa-lock"></i>
        @endif
    </div>

    <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:5px;flex-wrap:wrap">
            <span style="font-size:15px;font-weight:700;color:#0f172a">{{ $quiz->title }}</span>
            <span class="badge badge-{{ $quiz->status }}">{{ strtoupper($quiz->status) }}</span>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
            <span style="font-size:12px;color:#64748b;display:flex;align-items:center;gap:4px">
                <i class="fa-solid fa-users" style="color:#6366f1"></i> {{ $quiz->group->name }}
            </span>
            <span style="font-size:12px;color:#64748b;display:flex;align-items:center;gap:4px">
                <i class="fa-solid fa-circle-question" style="color:#8b5cf6"></i> {{ $quiz->questions_count }} questions
            </span>
            <span style="font-size:12px;color:#64748b;display:flex;align-items:center;gap:4px">
                <i class="fa-solid fa-users-line" style="color:#10b981"></i> {{ $quiz->attempts_count }} submissions
            </span>
            <span style="font-size:12px;color:#64748b;display:flex;align-items:center;gap:4px">
                <i class="fa-solid fa-stopwatch" style="color:#f59e0b"></i> {{ $quiz->duration_minutes }} min
            </span>
            @if($quiz->hard_deadline)
            <span style="font-size:12px;color:#64748b;display:flex;align-items:center;gap:4px">
                <i class="fa-solid fa-flag-checkered" style="color:#ef4444"></i> {{ $quiz->hard_deadline->format('d M Y, H:i') }}
            </span>
            @endif
        </div>
    </div>

    <div class="quiz-row-actions">
        <a href="{{ route('lecturer.quizzes.show', $quiz) }}" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-eye"></i> View
        </a>
        @if($quiz->status === 'draft')
        <a href="{{ route('lecturer.quizzes.edit', $quiz) }}" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-pen-to-square"></i> Edit Draft
        </a>
        @else
        <a href="{{ route('lecturer.quizzes.results', $quiz) }}" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-chart-bar"></i> Results
        </a>
        @endif
        <form action="{{ route('lecturer.quizzes.destroy', $quiz) }}" method="POST"
              onsubmit="return confirm('Delete quiz &quot;{{ addslashes($quiz->title) }}&quot;? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm" style="background:#fee2e2;color:#ef4444;border:1.5px solid #fecaca">
                <i class="fa-solid fa-trash"></i> Delete
            </button>
        </form>
    </div>
</div>
@endforeach
</div>

@endif

@endsection
