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
            'group_id'         => 'required|exists:groups,id',
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'unlock_date'      => 'nullable|date',
            'hard_deadline'    => 'nullable|date|after_or_equal:unlock_date',
            'duration_minutes' => 'required|integer|min:1|max:180',
            'auto_submit'      => 'boolean',
            'enforce_focus'    => 'boolean',
            'questions'        => 'required|array|min:1',
            'questions.*.question'       => 'required|string',
            'questions.*.options'        => 'required|array|min:2',
            'questions.*.correct_option' => 'required|integer|min:0',
            'questions.*.marks'          => 'required|integer|min:1',
        ]);

        $data['auto_submit']   = $request->boolean('auto_submit');
        $data['enforce_focus'] = $request->boolean('enforce_focus');

        // Parse dates in app local timezone so they're stored correctly
        if (!empty($data['unlock_date'])) {
            $data['unlock_date'] = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $data['unlock_date'], config('app.timezone'))->utc();
        }
        if (!empty($data['hard_deadline'])) {
            $data['hard_deadline'] = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $data['hard_deadline'], config('app.timezone'))->utc();
        }

        $quiz = $this->assessment->createQuiz($data, auth()->id());

        return redirect()
            ->route('lecturer.quizzes.show', $quiz)
            ->with('success', 'Quiz draft created successfully.');
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
        $count = $this->assessment->sendQuizReminder($quiz->id);

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
            return back()->withErrors($e->errors());
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
