<?php

namespace App\Http\Controllers\Quiz;

use App\Contracts\IAssessment;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * QuizController — routes for the full quiz lifecycle (SDD §4.2).
 *
 * Lecturer routes:  create, store, publish, remind, results
 * Student routes:   index (available quizzes), show (take quiz), submit
 * Shared:           participationRecord
 */
class QuizController extends Controller
{
    public function __construct(private readonly IAssessment $assessment) {}

    // ── LECTURER ──────────────────────────────────────────────────────────

    /** GET /lecturer/quizzes — Lecturer quiz dashboard */
    public function lecturerIndex()
    {
        $quizzes = Quiz::where('created_by', auth()->id())
            ->with('group')
            ->withCount('questions', 'attempts')
            ->orderByDesc('created_at')
            ->get();
        return view('quiz.lecturer.index', compact('quizzes'));
    }

    /** GET /lecturer/quizzes/create — Lecturer quiz creation screen (SDD Fig 6.4) */
    public function create()
    {
        $groups = Group::orderBy('name')->get();
        return view('quiz.lecturer.create', compact('groups'));
    }

    /** POST /lecturer/quizzes — Store new quiz draft */
    public function store(Request $request)
    {
        $data = $request->validate([
            'group_id'              => 'required|exists:groups,id',
            'title'                 => 'required|string|max:255',
            'description'           => 'nullable|string',
            'unlock_date_date'      => 'nullable|date_format:Y-m-d',
            'unlock_date_hour'      => 'nullable|integer|min:1|max:12',
            'unlock_date_min'       => 'nullable|integer|min:0|max:59',
            'unlock_date_ampm'      => 'nullable|in:AM,PM',
            'hard_deadline_date'    => 'nullable|date_format:Y-m-d',
            'hard_deadline_hour'    => 'nullable|integer|min:1|max:12',
            'hard_deadline_min'     => 'nullable|integer|min:0|max:59',
            'hard_deadline_ampm'    => 'nullable|in:AM,PM',
            'duration_minutes'      => 'required|integer|min:1|max:180',
            'auto_submit'           => 'boolean',
            'enforce_focus'         => 'boolean',
            'questions'             => 'required|array|min:1',
            'questions.*.question'       => 'required|string',
            'questions.*.options'        => 'required|array|min:2',
            'questions.*.correct_option' => 'required|integer|min:0',
            'questions.*.marks'          => 'required|integer|min:1',
        ]);

        $data['auto_submit']   = $request->boolean('auto_submit');
        $data['enforce_focus'] = $request->boolean('enforce_focus');

        // Assemble 12hr fields into Carbon UTC datetimes
        $assembleDateTime = function (string $prefix) use ($request): ?\Carbon\Carbon {
            $date = $request->input($prefix . '_date');
            $hour = $request->input($prefix . '_hour');
            if (!$date || !$hour) return null;
            $min  = str_pad((int) $request->input($prefix . '_min', 0), 2, '0', STR_PAD_LEFT);
            $ampm = $request->input($prefix . '_ampm', 'AM');
            return \Carbon\Carbon::createFromFormat(
                'Y-m-d h:i A',
                "$date " . str_pad($hour, 2, '0', STR_PAD_LEFT) . ":$min $ampm",
                config('app.timezone')
            );
        };

        $data['unlock_date']   = $assembleDateTime('unlock_date');
        $data['hard_deadline'] = $assembleDateTime('hard_deadline');

        if ($data['hard_deadline'] && $data['hard_deadline']->isPast()) {
            return back()->withErrors(['hard_deadline_date' => 'The deadline must be a future time.'])->withInput();
        }

        $quiz = $this->assessment->createQuiz($data, auth()->id());

        return redirect()
            ->route('lecturer.quizzes.show', $quiz)
            ->with('success', 'Quiz draft created successfully.');
    }

    /** GET /lecturer/quizzes/{quiz}/edit — Edit full draft quiz */
    public function edit(Quiz $quiz)
    {
        $this->authoriseLecturer($quiz);
        if ($quiz->status !== 'draft') {
            return back()->with('error', 'Only draft quizzes can be edited.');
        }
        $quiz->load('questions');
        $groups        = \App\Models\Group::orderBy('name')->get();
        $unlockLocal   = $quiz->unlock_date;
        $deadlineLocal = $quiz->hard_deadline;
        return view('quiz.lecturer.edit', compact('quiz', 'groups', 'unlockLocal', 'deadlineLocal'));
    }

    /** POST /lecturer/quizzes/{quiz}/update — Save all draft quiz changes */
    public function update(Request $request, Quiz $quiz)
    {
        $this->authoriseLecturer($quiz);
        if ($quiz->status !== 'draft') {
            return back()->with('error', 'Only draft quizzes can be edited.');
        }

        $request->validate([
            'group_id'                   => 'required|exists:groups,id',
            'title'                      => 'required|string|max:255',
            'description'                => 'nullable|string',
            'duration_minutes'           => 'required|integer|min:1|max:180',
            'auto_submit'                => 'boolean',
            'enforce_focus'              => 'boolean',
            'unlock_date_date'           => 'nullable|date_format:Y-m-d',
            'unlock_date_hour'           => 'nullable|integer|min:1|max:12',
            'unlock_date_min'            => 'nullable|integer|min:0|max:59',
            'unlock_date_ampm'           => 'nullable|in:AM,PM',
            'hard_deadline_date'         => 'nullable|date_format:Y-m-d',
            'hard_deadline_hour'         => 'nullable|integer|min:1|max:12',
            'hard_deadline_min'          => 'nullable|integer|min:0|max:59',
            'hard_deadline_ampm'         => 'nullable|in:AM,PM',
            'questions'                  => 'required|array|min:1',
            'questions.*.question'       => 'required|string',
            'questions.*.options'        => 'required|array|min:2',
            'questions.*.correct_option' => 'required|integer|min:0',
            'questions.*.marks'          => 'required|integer|min:1',
        ]);

        $assembleDateTime = function (string $prefix) use ($request): ?\Carbon\Carbon {
            $date = $request->input($prefix . '_date');
            $hour = $request->input($prefix . '_hour');
            if (!$date || !$hour) return null;
            $min  = str_pad((int) $request->input($prefix . '_min', 0), 2, '0', STR_PAD_LEFT);
            $ampm = $request->input($prefix . '_ampm', 'AM');
            return \Carbon\Carbon::createFromFormat(
                'Y-m-d h:i A',
                "$date " . str_pad($hour, 2, '0', STR_PAD_LEFT) . ":$min $ampm",
                config('app.timezone')
            );
        };

        $deadline = $assembleDateTime('hard_deadline');
        if ($deadline && $deadline->isPast()) {
            return back()->withErrors(['hard_deadline_date' => 'The deadline must be a future time.'])->withInput();
        }

        $quiz->update([
            'group_id'         => $request->group_id,
            'title'            => $request->title,
            'description'      => $request->description,
            'duration_minutes' => $request->duration_minutes,
            'auto_submit'      => $request->boolean('auto_submit'),
            'enforce_focus'    => $request->boolean('enforce_focus'),
            'unlock_date'      => $assembleDateTime('unlock_date'),
            'hard_deadline'    => $deadline,
        ]);

        // Replace all questions
        $quiz->questions()->delete();
        foreach ($request->input('questions') as $q) {
            \App\Models\QuizQuestion::create([
                'quiz_id'        => $quiz->id,
                'question'       => $q['question'],
                'options'        => $q['options'],
                'correct_option' => $q['correct_option'],
                'marks'          => $q['marks'] ?? 1,
            ]);
        }

        return redirect()->route('lecturer.quizzes.show', $quiz)
            ->with('success', 'Quiz updated successfully.');
    }

    /** GET /lecturer/quizzes/{quiz} — View quiz draft / results */
    public function show(Quiz $quiz)
    {
        $this->authoriseLecturer($quiz);
        $quiz->load('questions', 'attempts.user', 'participationRecords.user');
        return view('quiz.lecturer.show', compact('quiz'));
    }

    /** POST /lecturer/quizzes/{quiz}/publish — Publish quiz (SDD Fig 3.12 step 2) */
    public function publish(Quiz $quiz)
    {
        $this->authoriseLecturer($quiz);
        $this->assessment->publishQuiz($quiz->id, auth()->id());

        return back()->with('success', 'Quiz published successfully.');
    }

    /** POST /lecturer/quizzes/{quiz}/remind — Send reminder notifications */
    public function remind(Quiz $quiz)
    {
        $this->authoriseLecturer($quiz);
        try {
            $count = $this->assessment->sendQuizReminder($quiz->id);
        } catch (\Exception $e) {
            return back()->with('error', 'Reminder failed: ' . $e->getMessage());
        }

        return back()->with('success', "Reminder sent to {$count} student(s).");
    }

    /** GET /lecturer/quizzes/{quiz}/results — View all scores */
    public function results(Quiz $quiz)
    {
        $this->authoriseLecturer($quiz);
        $records = $quiz->participationRecords()->with('user')->orderByDesc('score')->get();
        return view('quiz.lecturer.results', compact('quiz', 'records'));
    }

    // ── STUDENT ───────────────────────────────────────────────────────────

    /** GET /quizzes — List available quizzes for the student */
    public function index()
    {
        $user   = auth()->user();
        $quizzes = Quiz::published()
            ->whereHas('group.members', fn ($q) => $q->where('users.id', $user->id))
            ->with('group')
            ->withCount('questions')
            ->orderBy('unlock_date')
            ->get();

        $attempted = QuizAttempt::where('user_id', $user->id)
            ->pluck('quiz_id')
            ->toArray();

        return view('quiz.student.index', compact('quizzes', 'attempted'));
    }

    /** GET /quizzes/{quiz} — Student quiz screen with timer (SDD Fig 6.6) */
    public function take(Quiz $quiz)
    {
        $user = auth()->user();

        if (!$quiz->isOpen()) {
            return back()->with('error', 'This quiz is not currently open.');
        }

        if (QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $user->id)->exists()) {
            return redirect()->route('quizzes.result', $quiz)
                ->with('info', 'You have already submitted this quiz.');
        }

        $quiz->load('questions');

        // Seconds remaining until hard_deadline (for JS countdown)
        $secondsLeft = $quiz->hard_deadline
            ? max(0, now()->diffInSeconds($quiz->hard_deadline, false))
            : $quiz->duration_minutes * 60;

        $timerSeconds   = min($quiz->duration_minutes * 60, $secondsLeft);
        $enforceFocus   = (bool) $quiz->enforce_focus;
        $deadlineEpoch  = $quiz->hard_deadline ? $quiz->hard_deadline->timestamp * 1000 : null;

        return view('quiz.student.take', compact('quiz', 'timerSeconds', 'enforceFocus', 'deadlineEpoch'));
    }

    /** POST /quizzes/{quiz}/submit — Submit answers (SDD Fig 3.12 step 4) */
    public function submit(Request $request, Quiz $quiz)
    {
        $request->validate([
            'answers'   => 'required|array',
            'answers.*' => 'integer|min:0',
        ]);

        try {
            $attempt = $this->assessment->submitQuiz(
                $quiz->id,
                auth()->id(),
                $request->input('answers')
            );
        } catch (ValidationException $e) {
            $messages = $e->errors();
            // If already submitted, redirect to result instead of back()
            if (isset($messages['quiz']) && str_contains($messages['quiz'][0], 'already submitted')) {
                return redirect()->route('quizzes.result', $quiz);
            }
            return back()->withErrors($messages);
        }

        return redirect()
            ->route('quizzes.result', $quiz)
            ->with('success', 'Quiz submitted! Your score: ' . $attempt->score);
    }

    /** GET /quizzes/{quiz}/result — Show student result */
    public function result(Quiz $quiz)
    {
        $record = $this->assessment->participationRecord($quiz->id, auth()->id());

        if (!$record) {
            return redirect()->route('quizzes.index')
                ->with('info', 'No submission found for this quiz.');
        }

        return view('quiz.student.result', compact('quiz', 'record'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function authoriseLecturer(Quiz $quiz): void
    {
        if ($quiz->created_by !== auth()->id()) {
            abort(403, 'You are not authorised to manage this quiz.');
        }
    }
}
