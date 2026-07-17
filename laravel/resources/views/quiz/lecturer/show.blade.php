@extends('layouts.app')

@section('title', $quiz->title . ' — Manage Quiz')

@push('styles')
<style>
.quiz-hero {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 28px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 8px 28px rgba(99,102,241,.3);
    position: relative;
    overflow: hidden;
}
.quiz-hero::before {
    content: '';
    position: absolute; top: -50px; right: -50px;
    width: 180px; height: 180px;
    background: rgba(255,255,255,.07);
    border-radius: 50%;
}
.quiz-hero-title { font-size: 22px; font-weight: 900; margin-bottom: 6px; }
.quiz-hero-meta { font-size: 13px; opacity: .8; display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.quiz-hero-meta span { display: flex; align-items: center; gap: 5px; }

.lifecycle-step {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}
.lifecycle-step:last-child { border-bottom: none; }
.step-icon {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; flex-shrink: 0;
}
.step-done  { background: #d1fae5; color: #065f46; }
.step-active{ background: linear-gradient(135deg,#6366f1,#8b5cf6); color: #fff; box-shadow: 0 4px 12px rgba(99,102,241,.4); }
.step-pending{ background: #f1f5f9; color: #94a3b8; }
.step-info { flex: 1; }
.step-label { font-size: 13px; font-weight: 600; }
.step-label.done { color: #065f46; }
.step-label.active { color: #6366f1; }
.step-label.pending { color: #94a3b8; }
.step-time { font-size: 11px; color: #94a3b8; margin-top: 2px; }

.q-preview {
    padding: 20px 0;
    border-bottom: 1px solid #f1f5f9;
}
.q-preview:last-child { border-bottom: none; }
.q-preview-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.q-preview-text { font-size: 14px; font-weight: 600; color: #0f172a; flex: 1; line-height: 1.5; }
.q-options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.q-option {
    padding: 9px 14px; border-radius: 9px; font-size: 13px;
    display: flex; align-items: center; gap: 8px;
    border: 1.5px solid #e2e8f0;
}
.q-option.correct {
    background: #d1fae5; color: #065f46;
    border-color: #6ee7b7; font-weight: 600;
}
.q-option.wrong { background: #f8fafc; color: #64748b; }
.q-option-letter {
    width: 22px; height: 22px; border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; flex-shrink: 0;
}
.correct .q-option-letter { background: #10b981; color: #fff; }
.wrong .q-option-letter { background: #e2e8f0; color: #64748b; }

.action-btn-group { display: flex; flex-direction: column; gap: 10px; }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('lecturer.dashboard') }}"><i class="fa-solid fa-house"></i> Dashboard</a>
    <span class="sep"><i class="fa-solid fa-chevron-right" style="font-size:9px"></i></span>
    <span>{{ $quiz->title }}</span>
</div>

<div class="quiz-hero">
    <div>
        <div class="quiz-hero-title">{{ $quiz->title }}</div>
        <div class="quiz-hero-meta">
            <span><i class="fa-solid fa-users"></i> {{ $quiz->group->name }}</span>
            <span><i class="fa-solid fa-circle-question"></i> {{ $quiz->questions->count() }} questions</span>
            <span><i class="fa-solid fa-star"></i> {{ $quiz->totalMarks() }} marks</span>
            <span><i class="fa-solid fa-stopwatch"></i> {{ $quiz->duration_minutes }} min</span>
        </div>
    </div>
    <span class="badge badge-{{ $quiz->status }}" style="font-size:13px;padding:8px 18px">
        @if($quiz->status === 'published') <i class="fa-solid fa-circle-check"></i>
        @elseif($quiz->status === 'draft')  <i class="fa-solid fa-pencil"></i>
        @else                               <i class="fa-solid fa-lock"></i>
        @endif
        {{ strtoupper($quiz->status) }}
    </span>
</div>

{{-- Stats --}}
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-circle-question" style="color:#6366f1"></i></div>
        <div class="val">{{ $quiz->questions->count() }}</div>
        <div class="lbl">Questions</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-star" style="color:#f59e0b"></i></div>
        <div class="val">{{ $quiz->totalMarks() }}</div>
        <div class="lbl">Total Marks</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-stopwatch" style="color:#10b981"></i></div>
        <div class="val">{{ $quiz->duration_minutes }}<span style="font-size:14px;font-weight:500">m</span></div>
        <div class="lbl">Duration</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-users" style="color:#8b5cf6"></i></div>
        <div class="val">{{ $quiz->attempts->count() }}</div>
        <div class="lbl">Submissions</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:22px;align-items:start">

    {{-- LEFT --}}
    <div>
        {{-- Quiz Info --}}
        <div class="card" style="margin-bottom:22px">
            <div class="card-header">
                <h2><i class="fa-solid fa-circle-info"></i> Quiz Information</h2>
            </div>
            <div class="card-body">
                @if($quiz->description)
                    <p style="font-size:13px;color:#64748b;margin-bottom:20px;line-height:1.6;padding:14px;background:#f8fafc;border-radius:10px;border-left:3px solid #6366f1">
                        <i class="fa-solid fa-quote-left" style="color:#6366f1;margin-right:6px"></i>{{ $quiz->description }}
                    </p>
                @endif
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div style="padding:14px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0">
                        <div style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px"><i class="fa-solid fa-unlock" style="color:#10b981"></i> Unlock Date</div>
                        <div style="font-size:13px;font-weight:600">{{ $quiz->unlock_date?->format('D d M Y, h:i A') ?? 'Immediate on publish' }}</div>
                    </div>
                    <div style="padding:14px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0">
                        <div style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px"><i class="fa-solid fa-flag-checkered" style="color:#ef4444"></i> Hard Deadline</div>
                        <div style="font-size:13px;font-weight:600">{{ $quiz->hard_deadline?->format('D d M Y, h:i A') ?? 'No deadline set' }}</div>
                    </div>
                    <div style="padding:14px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0">
                        <div style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px"><i class="fa-solid fa-robot" style="color:#6366f1"></i> Auto-Submit</div>
                        <div style="font-size:13px;font-weight:600">
                            @if($quiz->auto_submit)
                                <span style="color:#065f46"><i class="fa-solid fa-circle-check"></i> Enabled</span>
                            @else
                                <span style="color:#991b1b"><i class="fa-solid fa-circle-xmark"></i> Disabled</span>
                            @endif
                        </div>
                    </div>
                    <div style="padding:14px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0">
                        <div style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px"><i class="fa-solid fa-lock" style="color:#8b5cf6"></i> Focus Lock</div>
                        <div style="font-size:13px;font-weight:600">
                            @if($quiz->enforce_focus)
                                <span style="color:#5b21b6"><i class="fa-solid fa-lock"></i> Enforced</span>
                            @else
                                <span style="color:#64748b"><i class="fa-solid fa-lock-open"></i> Disabled</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Questions Preview --}}
        <div class="card" style="margin-bottom:22px">
            <div class="card-header">
                <h2><i class="fa-solid fa-circle-question"></i> Questions Preview</h2>
                <span style="font-size:12px;color:#64748b;font-weight:600">{{ $quiz->questions->count() }} total</span>
            </div>
            <div class="card-body">
                @forelse($quiz->questions as $i => $q)
                <div class="q-preview">
                    <div class="q-preview-header">
                        <div class="q-preview-text">
                            <span style="color:#6366f1;font-weight:800;margin-right:6px">Q{{ $i+1 }}.</span>{{ $q->question }}
                        </div>
                        <span class="badge badge-published" style="margin-left:12px;flex-shrink:0">
                            <i class="fa-solid fa-star"></i> {{ $q->marks }} mark{{ $q->marks > 1 ? 's' : '' }}
                        </span>
                    </div>
                    <div class="q-options-grid">
                        @foreach($q->options as $oi => $opt)
                        <div class="q-option {{ $oi == $q->correct_option ? 'correct' : 'wrong' }}">
                            <div class="q-option-letter">{{ chr(65+$oi) }}</div>
                            {{ $opt }}
                            @if($oi == $q->correct_option) <i class="fa-solid fa-check" style="margin-left:auto"></i> @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @empty
                <div style="text-align:center;padding:40px;color:#94a3b8">
                    <i class="fa-solid fa-circle-question" style="font-size:40px;margin-bottom:12px;display:block"></i>
                    No questions added yet.
                </div>
                @endforelse
            </div>
        </div>

        {{-- Submissions --}}
        @if($quiz->participationRecords->count())
        <div class="card">
            <div class="card-header">
                <h2><i class="fa-solid fa-users"></i> Recent Submissions</h2>
                <a href="{{ route('lecturer.quizzes.results', $quiz) }}" class="btn btn-outline btn-sm">
                    <i class="fa-solid fa-chart-bar"></i> Full Results
                </a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fa-solid fa-user"></i> Student</th>
                            <th><i class="fa-solid fa-star"></i> Score</th>
                            <th><i class="fa-solid fa-percent"></i> Percentage</th>
                            <th><i class="fa-solid fa-trophy"></i> Grade</th>
                            <th><i class="fa-solid fa-clock"></i> Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quiz->participationRecords->sortByDesc('score')->take(5) as $rec)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">
                                        {{ strtoupper(substr($rec->user->name, 0, 1)) }}
                                    </div>
                                    <span style="font-weight:600">{{ $rec->user->name }}</span>
                                </div>
                            </td>
                            <td style="font-weight:700">{{ $rec->score }} <span style="color:#94a3b8;font-weight:400">/ {{ $rec->max_score }}</span></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <div class="progress" style="width:70px"><div class="progress-bar" style="width:{{ $rec->percentage }}%"></div></div>
                                    <span style="font-weight:600">{{ $rec->percentage }}%</span>
                                </div>
                            </td>
                            <td><span class="badge badge-{{ $rec->grade }}">{{ $rec->grade }}</span></td>
                            <td style="color:#64748b;font-size:12px">{{ $rec->completed_at?->format('d M H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- RIGHT --}}
    <div>
        {{-- Actions --}}
        <div class="card" style="margin-bottom:18px">
            <div class="card-header"><h2><i class="fa-solid fa-bolt"></i> Actions</h2></div>
            <div class="card-body action-btn-group">
                @if($quiz->status === 'draft')
                <a href="{{ route('lecturer.quizzes.edit', $quiz) }}" class="btn btn-outline" style="width:100%;justify-content:center;padding:13px">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Draft
                </a>
                <form action="{{ route('lecturer.quizzes.publish', $quiz) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;padding:13px">
                        <i class="fa-solid fa-rocket"></i> Publish Quiz
                    </button>
                </form>
                <p style="font-size:11px;color:#64748b;text-align:center">Publishing makes this quiz visible to students.</p>
                @endif

                @if($quiz->status === 'published')
                <form action="{{ route('lecturer.quizzes.remind', $quiz) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-warning" style="width:100%;justify-content:center;padding:13px">
                        <i class="fa-solid fa-bell"></i> Send Reminder
                    </button>
                </form>
                <p style="font-size:11px;color:#64748b;text-align:center">Notifies all group members about this quiz.</p>
                <a href="{{ route('lecturer.quizzes.results', $quiz) }}" class="btn btn-outline" style="width:100%;justify-content:center">
                    <i class="fa-solid fa-chart-bar"></i> View Full Results
                </a>
                @endif
            </div>
        </div>

        {{-- Lifecycle --}}
        <div class="card">
            <div class="card-header"><h2><i class="fa-solid fa-timeline"></i> Lifecycle</h2></div>
            <div class="card-body">
                @php
                    $steps = [
                        ['Draft Created',     true,                       'fa-pencil',        'done'],
                        ['Published',         $quiz->status !== 'draft',  'fa-rocket',        $quiz->status !== 'draft' ? 'done' : 'pending'],
                        ['Reminder Sent',     $quiz->reminder_sent_at,    'fa-bell',          $quiz->reminder_sent_at ? 'done' : 'pending'],
                        ['Accepting Answers', $quiz->isOpen(),            'fa-circle-play',   $quiz->isOpen() ? 'active' : ($quiz->isPastDeadline() ? 'done' : 'pending')],
                        ['Closed',            $quiz->isPastDeadline(),    'fa-lock',          $quiz->isPastDeadline() ? 'done' : 'pending'],
                    ];
                @endphp
                @foreach($steps as [$label, $done, $icon, $state])
                <div class="lifecycle-step">
                    <div class="step-icon step-{{ $state }}">
                        <i class="fa-solid fa-{{ $icon }}"></i>
                    </div>
                    <div class="step-info">
                        <div class="step-label {{ $state }}">{{ $label }}</div>
                    </div>
                    @if($state === 'done') <i class="fa-solid fa-check" style="color:#10b981;font-size:12px"></i> @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection
