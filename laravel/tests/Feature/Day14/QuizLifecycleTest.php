<?php

namespace Tests\Feature\Day14;

use App\Models\Group;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Day 14 — Quiz lifecycle test:
 *   lecturer creates → student attempts → auto-submit on timer expiry.
 */
class QuizLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private AssessmentService $service;
    private User  $lecturer;
    private User  $student;
    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service  = app(AssessmentService::class);
        $this->lecturer = User::factory()->create(['role' => 'lecturer']);
        $this->student  = User::factory()->create(['role' => 'member']);
        $this->group    = Group::factory()->create(['created_by' => $this->lecturer->id]);
        $this->group->members()->attach($this->student->id);
    }

    // ── Step 1: Lecturer creates a quiz draft ─────────────────────────────

    public function test_lecturer_creates_quiz_draft(): void
    {
        $quiz = $this->service->createQuiz($this->quizData(), $this->lecturer->id);

        $this->assertDatabaseHas('quizzes', [
            'id'     => $quiz->id,
            'status' => 'draft',
            'title'  => 'Day 14 Quiz',
        ]);
        $this->assertCount(2, $quiz->questions);
    }

    // ── Step 2: Lecturer publishes the quiz ───────────────────────────────

    public function test_lecturer_publishes_quiz(): void
    {
        $quiz = $this->service->createQuiz($this->quizData(), $this->lecturer->id);
        $quiz = $this->service->publishQuiz($quiz->id, $this->lecturer->id);

        $this->assertEquals('published', $quiz->status);
        $this->assertNotNull($quiz->published_at);
    }

    // ── Step 3: Student attempts the quiz ────────────────────────────────

    public function test_student_submits_quiz_and_receives_score(): void
    {
        $quiz = $this->service->createQuiz($this->quizData(), $this->lecturer->id);
        $this->service->publishQuiz($quiz->id, $this->lecturer->id);
        $quiz->refresh()->load('questions');

        // Build answers: answer every question correctly
        $answers = $quiz->questions->mapWithKeys(
            fn ($q) => [$q->id => $q->correct_option]
        )->toArray();

        $attempt = $this->service->submitQuiz($quiz->id, $this->student->id, $answers);

        $this->assertNotNull($attempt->submitted_at);
        $this->assertEquals($quiz->totalMarks(), $attempt->score);

        $this->assertDatabaseHas('participation_records', [
            'quiz_id'   => $quiz->id,
            'user_id'   => $this->student->id,
            'completed' => true,
        ]);
    }

    // ── Step 4: Auto-submit on timer expiry ───────────────────────────────

    /**
     * When hard_deadline has passed the quiz is no longer open.
     * The server must reject a late submission (simulating what the JS timer
     * triggers: a POST /quizzes/{id}/submit after the deadline).
     */
    public function test_submission_rejected_after_hard_deadline(): void
    {
        $data               = $this->quizData();
        $data['hard_deadline'] = now()->subMinute(); // already expired

        $quiz = $this->service->createQuiz($data, $this->lecturer->id);
        $this->service->publishQuiz($quiz->id, $this->lecturer->id);
        $quiz->refresh()->load('questions');

        $answers = $quiz->questions->mapWithKeys(
            fn ($q) => [$q->id => $q->correct_option]
        )->toArray();

        $this->expectException(ValidationException::class);
        $this->service->submitQuiz($quiz->id, $this->student->id, $answers);
    }

    /**
     * Auto-submit path: when the timer fires before the hard_deadline the
     * submission must still be accepted (quiz is still open).
     */
    public function test_auto_submit_before_deadline_is_accepted(): void
    {
        $data                  = $this->quizData();
        $data['hard_deadline'] = now()->addMinutes(10); // still open

        $quiz = $this->service->createQuiz($data, $this->lecturer->id);
        $this->service->publishQuiz($quiz->id, $this->lecturer->id);
        $quiz->refresh()->load('questions');

        $answers = $quiz->questions->mapWithKeys(
            fn ($q) => [$q->id => 0] // all wrong — simulates partial auto-submit
        )->toArray();

        $attempt = $this->service->submitQuiz($quiz->id, $this->student->id, $answers);

        $this->assertNotNull($attempt->submitted_at);
        $this->assertDatabaseHas('quiz_attempts', ['id' => $attempt->id]);
    }

    // ── Step 5: Second attempt is blocked ────────────────────────────────

    public function test_student_cannot_submit_twice(): void
    {
        $quiz = $this->service->createQuiz($this->quizData(), $this->lecturer->id);
        $this->service->publishQuiz($quiz->id, $this->lecturer->id);
        $quiz->refresh()->load('questions');

        $answers = $quiz->questions->mapWithKeys(
            fn ($q) => [$q->id => $q->correct_option]
        )->toArray();

        $this->service->submitQuiz($quiz->id, $this->student->id, $answers);

        $this->expectException(ValidationException::class);
        $this->service->submitQuiz($quiz->id, $this->student->id, $answers);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function quizData(): array
    {
        return [
            'group_id'         => $this->group->id,
            'title'            => 'Day 14 Quiz',
            'description'      => 'Integration review quiz',
            'unlock_date'      => now()->subMinute(),
            'hard_deadline'    => now()->addHour(),
            'duration_minutes' => 15,
            'auto_submit'      => true,
            'enforce_focus'    => false,
            'questions'        => [
                [
                    'question'       => 'What is 2 + 2?',
                    'options'        => ['3', '4', '5', '6'],
                    'correct_option' => 1,
                    'marks'          => 2,
                ],
                [
                    'question'       => 'What is the capital of France?',
                    'options'        => ['Berlin', 'Madrid', 'Paris', 'Rome'],
                    'correct_option' => 2,
                    'marks'          => 3,
                ],
            ],
        ];
    }
}
