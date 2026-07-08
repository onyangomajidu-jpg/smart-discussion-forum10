<?php

namespace App\Contracts;

use App\Models\Quiz;
use App\Models\QuizAttempt;

/**
 * IAssessment — Assessment subsystem contract (SDD §4.2 / Fig 3.12).
 *
 * Implemented by AssessmentService.
 * Covers the full quiz lifecycle:
 *   createQuiz → publishQuiz → sendQuizReminder
 *   → submitQuiz → calculateMarks → assignMarks → participationRecord
 */
interface IAssessment
{
    /**
     * Create a new quiz draft for a group (SDD Fig 3.12 — step 1).
     *
     * @param  array $data  title, description, group_id, unlock_date,
     *                      hard_deadline, duration_minutes, auto_submit,
     *                      questions[]
     * @param  int   $lecturerId
     * @return Quiz
     */
    public function createQuiz(array $data, int $lecturerId): Quiz;

    /**
     * Publish a draft quiz so students can see it (SDD Fig 3.12 — step 2).
     * Sets status to 'published' and records published_at timestamp.
     *
     * @param  int $quizId
     * @param  int $lecturerId  must be the quiz creator
     * @return Quiz
     */
    public function publishQuiz(int $quizId, int $lecturerId): Quiz;

    /**
     * Send reminder notifications to all group members before quiz start
     * (SDD §4.2.3 — sendQuizReminder).
     *
     * @param  int $quizId
     * @return int  number of notifications dispatched
     */
    public function sendQuizReminder(int $quizId): int;

    /**
     * Submit a student's answers (SDD Fig 3.12 — step 4).
     * Enforces hard_deadline and one-attempt-per-user rule.
     *
     * @param  int   $quizId
     * @param  int   $userId
     * @param  array $answers  {question_id: chosen_option_index}
     * @return QuizAttempt
     */
    public function submitQuiz(int $quizId, int $userId, array $answers): QuizAttempt;

    /**
     * Calculate the raw score for a set of answers (SDD §4.2.4).
     * Pure function — does not persist anything.
     *
     * @param  Quiz  $quiz
     * @param  array $answers
     * @return int   total marks earned
     */
    public function calculateMarks(Quiz $quiz, array $answers): int;

    /**
     * Persist the final score on the attempt and update the member's
     * participation record (SDD §4.2.5 — assignMarks).
     *
     * @param  QuizAttempt $attempt
     * @param  int         $score
     * @return QuizAttempt  updated attempt with score
     */
    public function assignMarks(QuizAttempt $attempt, int $score): QuizAttempt;

    /**
     * Return the participation record for a user in a quiz
     * (SDD §4.2.5 — participationRecord).
     *
     * @param  int $quizId
     * @param  int $userId
     * @return array|null  attempt data or null if not attempted
     */
    public function participationRecord(int $quizId, int $userId): ?array;
}
