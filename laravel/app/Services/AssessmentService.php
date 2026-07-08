<?php

namespace App\Services;

use App\Contracts\IAssessment;
use App\Models\ParticipationRecord;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Notifications\QuizReminderNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * AssessmentService — implements IAssessment (SDD §4.2 / Fig 3.12).
 *
 * Quiz lifecycle:
 *   createQuiz() → publishQuiz() → sendQuizReminder()
 *   → submitQuiz() → calculateMarks() → assignMarks() → participationRecord()
 */
class AssessmentService implements IAssessment
{
    // ── createQuiz() ──────────────────────────────────────────────────────

    /**
     * Create a quiz draft with questions (SDD Fig 3.12 — step 1).
     *
     * Expected $data keys:
     *   title, description, group_id, unlock_date, hard_deadline,
     *   duration_minutes (default 15), auto_submit (default true),
     *   questions[] → each: question, options[], correct_option, marks
     */
    public function createQuiz(array $data, int $lecturerId): Quiz
    {
        return DB::transaction(function () use ($data, $lecturerId) {
            $quiz = Quiz::create([
                'group_id'         => $data['group_id'],
                'created_by'       => $lecturerId,
                'title'            => $data['title'],
                'description'      => $data['description'] ?? null,
                'status'           => 'draft',
                'unlock_date'      => $data['unlock_date'] ?? null,
                'hard_deadline'    => $data['hard_deadline'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? 15,
                'auto_submit'      => $data['auto_submit'] ?? true,
                'enforce_focus'    => $data['enforce_focus'] ?? true,
            ]);

            foreach ($data['questions'] ?? [] as $q) {
                QuizQuestion::create([
                    'quiz_id'        => $quiz->id,
                    'question'       => $q['question'],
                    'options'        => $q['options'],
                    'correct_option' => $q['correct_option'],
                    'marks'          => $q['marks'] ?? 1,
                ]);
            }

            return $quiz->load('questions');
        });
    }

    // ── publishQuiz() ─────────────────────────────────────────────────────

    /**
     * Publish a draft quiz (SDD Fig 3.12 — step 2).
     * Only the creator lecturer may publish.
     */
    public function publishQuiz(int $quizId, int $lecturerId): Quiz
    {
        $quiz = Quiz::findOrFail($quizId);

        if ($quiz->created_by !== $lecturerId) {
            throw ValidationException::withMessages([
                'quiz' => 'Only the quiz creator can publish this quiz.',
            ]);
        }

        if ($quiz->status !== 'draft') {
            throw ValidationException::withMessages([
                'quiz' => 'Only draft quizzes can be published.',
            ]);
        }

        if ($quiz->questions()->count() === 0) {
            throw ValidationException::withMessages([
                'quiz' => 'Cannot publish a quiz with no questions.',
            ]);
        }

        $quiz->update([
            'status'       => 'published',
            'published_at' => now(),
        ]);

        return $quiz->fresh();
    }

    // ── sendQuizReminder() ────────────────────────────────────────────────

    /**
     * Dispatch reminder notifications to all group members (SDD §4.2.3).
     * Returns the count of notifications sent.
     */
    public function sendQuizReminder(int $quizId): int
    {
        $quiz = Quiz::with('group.members')->findOrFail($quizId);

        $members = $quiz->group->members;

        Notification::send($members, new QuizReminderNotification($quiz));

        return $members->count();
    }

    // ── submitQuiz() ──────────────────────────────────────────────────────

    /**
     * Submit a student's answers (SDD Fig 3.12 — step 4).
     *
     * Rules enforced:
     *  - Quiz must be published and open (within unlock_date → hard_deadline)
     *  - One attempt per user
     *  - Calculates and assigns marks immediately
     */
    public function submitQuiz(int $quizId, int $userId, array $answers): QuizAttempt
    {
        $quiz = Quiz::with('questions')->findOrFail($quizId);

        // Enforce quiz window
        if (!$quiz->isOpen()) {
            throw ValidationException::withMessages([
                'quiz' => 'This quiz is not currently open for submissions.',
            ]);
        }

        // One attempt per user
        if (QuizAttempt::where('quiz_id', $quizId)->where('user_id', $userId)->exists()) {
            throw ValidationException::withMessages([
                'quiz' => 'You have already submitted this quiz.',
            ]);
        }

        return DB::transaction(function () use ($quiz, $userId, $answers) {
            $attempt = QuizAttempt::create([
                'quiz_id'      => $quiz->id,
                'user_id'      => $userId,
                'answers'      => $answers,
                'score'        => 0,
                'submitted_at' => now(),
            ]);

            $score = $this->calculateMarks($quiz, $answers);
            $this->assignMarks($attempt, $score);

            return $attempt->fresh();
        });
    }

    // ── calculateMarks() ─────────────────────────────────────────────────

    /**
     * Pure score calculation — no DB writes (SDD §4.2.4).
     *
     * @param  Quiz  $quiz     must have questions loaded
     * @param  array $answers  {question_id: chosen_option_index}
     */
    public function calculateMarks(Quiz $quiz, array $answers): int
    {
        $score = 0;

        foreach ($quiz->questions as $question) {
            $chosen = $answers[$question->id] ?? null;
            if ($chosen !== null && (int) $chosen === (int) $question->correct_option) {
                $score += $question->marks;
            }
        }

        return $score;
    }

    // ── assignMarks() ─────────────────────────────────────────────────────

    /**
     * Persist score on the attempt and upsert the participation record
     * (SDD §4.2.5 — assignMarks).
     */
    public function assignMarks(QuizAttempt $attempt, int $score): QuizAttempt
    {
        $attempt->update(['score' => $score]);

        $quiz     = $attempt->quiz()->with('questions')->first();
        $maxScore = $quiz->totalMarks();
        $pct      = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0.00;
        $grade    = ParticipationRecord::gradeFromPercentage($pct);

        ParticipationRecord::updateOrCreate(
            ['quiz_id' => $attempt->quiz_id, 'user_id' => $attempt->user_id],
            [
                'quiz_attempt_id' => $attempt->id,
                'score'           => $score,
                'max_score'       => $maxScore,
                'percentage'      => $pct,
                'grade'           => $grade,
                'completed'       => true,
                'completed_at'    => now(),
            ]
        );

        return $attempt->fresh();
    }

    // ── participationRecord() ─────────────────────────────────────────────

    /**
     * Return the participation record for a user in a quiz (SDD §4.2.5).
     */
    public function participationRecord(int $quizId, int $userId): ?array
    {
        $record = ParticipationRecord::with('attempt')
            ->where('quiz_id', $quizId)
            ->where('user_id', $userId)
            ->first();

        return $record?->toArray();
    }
}
