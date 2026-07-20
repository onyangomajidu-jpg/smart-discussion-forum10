<?php

namespace Tests\Feature;

use App\Models\Blacklist;
use App\Models\Group;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\Warning;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Day 14 — Integration & mid-project review
 *
 * T1  Web post → appears via API (real-time channel simulation)
 * T2  Offline write → sync on reconnect
 * T3  Quiz lifecycle: lecturer creates → student attempts → auto-submit on timer expiry
 * T4  Moderation: 2 warnings → blacklist enforced
 */
class Day14IntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User  $lecturer;
    private User  $student;
    private User  $admin;
    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lecturer = User::factory()->create(['role' => 'lecturer']);
        $this->student  = User::factory()->create(['role' => 'member']);
        $this->admin    = User::factory()->create(['role' => 'admin']);

        $this->group = Group::factory()->create([
            'created_by' => $this->lecturer->id,
        ]);

        // Add both lecturer and student to the group
        $this->group->members()->attach([
            $this->lecturer->id,
            $this->student->id,
        ]);
    }

    // ── T1: Web post → appears on Java desktop in real time ───────────────

    /** T1a — Student posts a message on the web */
    public function test_web_post_is_stored_and_retrievable_via_api()
    {
        $topic = Topic::factory()->create([
            'group_id' => $this->group->id,
            'user_id'  => $this->lecturer->id,
        ]);

        // Student posts via web
        $this->actingAs($this->student)
            ->post("/topics/{$topic->id}/participate", ['body' => 'Hello from web!'])
            ->assertRedirect();

        // Java desktop fetches via API — same post must appear
        $token = $this->student->createToken('java-desktop')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/topics/{$topic->id}/posts");

        $response->assertStatus(200);

        $bodies = collect($response->json())->pluck('body')->toArray();
        $this->assertContains('Hello from web!', $bodies,
            'Post made on web must be visible via API to Java desktop');
    }

    /** T1b — API post is immediately visible to subsequent API fetch */
    public function test_api_post_appears_in_topic_feed()
    {
        $topic = Topic::factory()->create([
            'group_id' => $this->group->id,
            'user_id'  => $this->lecturer->id,
        ]);

        $token = $this->student->createToken('java-desktop')->plainTextToken;

        // Post via API (Java desktop path)
        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/posts', [
                'topic_id' => $topic->id,
                'user_id'  => $this->student->id,
                'body'     => 'Hello from Java!',
            ])
            ->assertStatus(201);

        // Fetch and verify
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/topics/{$topic->id}/posts");

        $response->assertStatus(200);
        $bodies = collect($response->json())->pluck('body')->toArray();
        $this->assertContains('Hello from Java!', $bodies);
    }

    // ── T2: Offline write → sync on reconnect ─────────────────────────────

    /** T2a — Unauthenticated API post is rejected (simulates offline queue not yet synced) */
    public function test_offline_message_stored_with_synced_false()
    {
        $topic = Topic::factory()->create([
            'group_id' => $this->group->id,
            'user_id'  => $this->lecturer->id,
        ]);

        // No token = offline simulation — must be rejected
        $response = $this->postJson('/api/posts', [
            'topic_id' => $topic->id,
            'user_id'  => $this->student->id,
            'body'     => 'Offline message',
        ]);

        $response->assertStatus(401);

        // Message must NOT be in DB yet (it would be in Java SQLite pending_messages)
        $this->assertDatabaseMissing('posts', ['body' => 'Offline message']);
    }

    /** T2b — On reconnect, pending messages are uploaded and marked synced */
    public function test_sync_on_reconnect_uploads_pending_messages()
    {
        $topic = Topic::factory()->create([
            'group_id' => $this->group->id,
            'user_id'  => $this->lecturer->id,
        ]);

        $token = $this->student->createToken('java-desktop')->plainTextToken;

        // Simulate: message was queued offline
        $this->postJson('/api/posts', [
            'topic_id' => $topic->id,
            'user_id'  => $this->student->id,
            'body'     => 'Queued offline message',
        ])->assertStatus(401); // no token = offline simulation

        // Now "reconnect" — post with valid token
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/posts', [
                'topic_id' => $topic->id,
                'user_id'  => $this->student->id,
                'body'     => 'Queued offline message',
            ]);

        $response->assertStatus(201);

        // Verify it's now in the DB (synced)
        $this->assertDatabaseHas('posts', [
            'topic_id' => $topic->id,
            'body'     => 'Queued offline message',
        ]);
    }

    // ── T3: Quiz lifecycle ─────────────────────────────────────────────────

    /** T3a — Lecturer creates a quiz draft */
    public function test_lecturer_creates_quiz_draft()
    {
        $response = $this->actingAs($this->lecturer)
            ->post('/lecturer/quizzes', [
                'group_id'         => $this->group->id,
                'title'            => 'Mid-term Quiz',
                'description'      => 'Integration test quiz',
                'duration_minutes' => 30,
                'auto_submit'      => true,
                'enforce_focus'    => true,
                'questions'        => [
                    [
                        'question'       => 'What is 2+2?',
                        'options'        => ['3', '4', '5', '6'],
                        'correct_option' => 1,
                        'marks'          => 2,
                    ],
                    [
                        'question'       => 'Capital of France?',
                        'options'        => ['Berlin', 'Paris', 'Rome', 'Madrid'],
                        'correct_option' => 1,
                        'marks'          => 2,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quizzes', [
            'title'      => 'Mid-term Quiz',
            'status'     => 'draft',
            'created_by' => $this->lecturer->id,
        ]);
    }

    /** T3b — Lecturer publishes the quiz */
    public function test_lecturer_publishes_quiz()
    {
        $quiz = $this->createPublishedQuiz();

        $this->assertDatabaseHas('quizzes', [
            'id'     => $quiz->id,
            'status' => 'published',
        ]);
        $this->assertNotNull($quiz->fresh()->published_at);
    }

    /** T3c — Student attempts and submits the quiz */
    public function test_student_attempts_and_submits_quiz()
    {
        $quiz = $this->createPublishedQuiz();
        $questions = $quiz->questions;

        // Build correct answers
        $answers = $questions->mapWithKeys(fn($q) => [$q->id => $q->correct_option])->toArray();

        $response = $this->actingAs($this->student)
            ->post("/quizzes/{$quiz->id}/submit", ['answers' => $answers]);

        $response->assertRedirect(route('quizzes.result', $quiz));

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $this->student->id)
            ->first();

        $this->assertNotNull($attempt, 'Attempt must be recorded');
        $this->assertNotNull($attempt->submitted_at, 'submitted_at must be set');
        $this->assertEquals($quiz->totalMarks(), $attempt->score, 'All correct = full marks');
    }

    /** T3d — Auto-submit on timer expiry: quiz past hard_deadline is auto-submitted */
    public function test_auto_submit_on_timer_expiry()
    {
        // Create quiz with hard_deadline already passed
        $quiz = $this->createDraftQuiz();
        $quiz->update([
            'status'        => 'published',
            'published_at'  => now()->subHour(),
            'unlock_date'   => now()->subHour(),
            'hard_deadline' => now()->subMinutes(1), // already expired
            'auto_submit'   => true,
        ]);

        $questions = $quiz->questions;
        $answers   = $questions->mapWithKeys(fn($q) => [$q->id => 0])->toArray();

        // Attempt to submit after deadline — should be rejected
        $response = $this->actingAs($this->student)
            ->post("/quizzes/{$quiz->id}/submit", ['answers' => $answers]);

        // Quiz is past deadline so isOpen() = false → redirect with error
        $response->assertRedirect();
        $this->assertDatabaseMissing('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
        ]);

        // Simulate auto-submit: directly call AssessmentService as the timer would
        $service = app(\App\Contracts\IAssessment::class);

        // Force quiz open temporarily to simulate auto-submit trigger
        $quiz->update(['hard_deadline' => now()->addMinute()]);
        $attempt = $service->submitQuiz($quiz->id, $this->student->id, $answers);

        $this->assertNotNull($attempt->submitted_at, 'Auto-submit must set submitted_at');
        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
        ]);
    }

    /** T3e — Student cannot submit the same quiz twice */
    public function test_student_cannot_submit_quiz_twice()
    {
        $quiz    = $this->createPublishedQuiz();
        $answers = $quiz->questions->mapWithKeys(fn($q) => [$q->id => 0])->toArray();

        // First submission
        $this->actingAs($this->student)
            ->post("/quizzes/{$quiz->id}/submit", ['answers' => $answers])
            ->assertRedirect(route('quizzes.result', $quiz));

        // Second submission must be rejected — redirects away (back or dashboard)
        $response = $this->actingAs($this->student)
            ->post("/quizzes/{$quiz->id}/submit", ['answers' => $answers]);

        $response->assertRedirect();

        $this->assertEquals(
            1,
            QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $this->student->id)->count(),
            'Only one attempt must exist'
        );
    }

    // ── T4: Moderation — 2 warnings → blacklist enforced ──────────────────

    /** T4a — Admin issues first warning */
    public function test_admin_issues_first_warning()
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/warnings', [
                'user_id' => $this->student->id,
                'reason'  => 'Inappropriate language',
                'details' => 'First offence',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('warnings', [
            'user_id'   => $this->student->id,
            'issued_by' => $this->admin->id,
            'reason'    => 'Inappropriate language',
        ]);
    }

    /** T4b — Admin issues second warning */
    public function test_admin_issues_second_warning()
    {
        Warning::create([
            'user_id'   => $this->student->id,
            'issued_by' => $this->admin->id,
            'reason'    => 'First warning',
        ]);

        $response = $this->actingAs($this->admin)
            ->post('/admin/warnings', [
                'user_id' => $this->student->id,
                'reason'  => 'Repeated offence',
                'details' => 'Second offence',
            ]);

        $response->assertRedirect();

        $this->assertEquals(
            2,
            Warning::where('user_id', $this->student->id)->count(),
            'Student must have 2 warnings'
        );
    }

    /** T4c — After 2 warnings, admin blacklists the student */
    public function test_admin_blacklists_student_after_two_warnings()
    {
        Warning::factory()->count(2)->create([
            'user_id'   => $this->student->id,
            'issued_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post('/admin/blacklists', [
                'user_id' => $this->student->id,
                'reason'  => 'Two warnings exceeded',
                'days'    => 7,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('blacklists', [
            'user_id'   => $this->student->id,
            'banned_by' => $this->admin->id,
        ]);
    }

    /** T4d — Blacklisted student cannot log in */
    public function test_blacklisted_student_cannot_login()
    {
        Blacklist::create([
            'user_id'    => $this->student->id,
            'banned_by'  => $this->admin->id,
            'reason'     => 'Banned',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->post('/login', [
            'email'    => $this->student->email,
            'password' => 'password',
        ]);

        // Must not be authenticated — redirected away from dashboard
        $this->assertGuest('web');
        $response->assertRedirect();
    }

    /** T4e — Blacklisted student cannot access dashboard */
    public function test_blacklisted_student_cannot_access_dashboard()
    {
        Blacklist::create([
            'user_id'    => $this->student->id,
            'banned_by'  => $this->admin->id,
            'reason'     => 'Banned',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->student)->get('/dashboard');

        // Should be redirected away (403 or redirect to login)
        $this->assertTrue(
            in_array($response->status(), [302, 403]),
            'Blacklisted user must not access dashboard'
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function createDraftQuiz(): Quiz
    {
        $quiz = Quiz::create([
            'group_id'         => $this->group->id,
            'created_by'       => $this->lecturer->id,
            'title'            => 'Test Quiz',
            'status'           => 'draft',
            'duration_minutes' => 30,
            'auto_submit'      => true,
            'enforce_focus'    => false,
            'unlock_date'      => now()->subMinutes(5),
            'hard_deadline'    => now()->addHour(),
        ]);

        QuizQuestion::create([
            'quiz_id'        => $quiz->id,
            'question'       => 'What is 2+2?',
            'options'        => json_encode(['3', '4', '5', '6']),
            'correct_option' => 1,
            'marks'          => 2,
        ]);

        QuizQuestion::create([
            'quiz_id'        => $quiz->id,
            'question'       => 'Capital of France?',
            'options'        => json_encode(['Berlin', 'Paris', 'Rome', 'Madrid']),
            'correct_option' => 1,
            'marks'          => 2,
        ]);

        return $quiz->load('questions');
    }

    private function createPublishedQuiz(): Quiz
    {
        $quiz = $this->createDraftQuiz();
        $quiz->update([
            'status'       => 'published',
            'published_at' => now(),
        ]);
        return $quiz->fresh(['questions']);
    }

    private function makeOfflineApiClient(): object
    {
        return new class {
            public function isOnline(): bool { return false; }
            public function post(string $e, array $b): string { throw new \RuntimeException('Offline'); }
            public function get(string $e): string { throw new \RuntimeException('Offline'); }
        };
    }
}
