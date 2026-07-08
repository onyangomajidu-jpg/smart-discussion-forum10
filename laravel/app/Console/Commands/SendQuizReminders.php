<?php

namespace App\Console\Commands;

use App\Contracts\IAssessment;
use App\Models\Quiz;
use Illuminate\Console\Command;

/**
 * Dispatches sendQuizReminder() for every published quiz whose unlock_date
 * is within the next 30 minutes and has not yet been reminded.
 *
 * Schedule: every minute (see routes/console.php)
 * SDD §4.2.3 — sendQuizReminder() / Fig 3.12 step 3
 */
class SendQuizReminders extends Command
{
    protected $signature   = 'quiz:send-reminders';
    protected $description = 'Send reminder notifications for quizzes opening within 30 minutes';

    public function handle(IAssessment $assessment): int
    {
        $quizzes = Quiz::published()
            ->whereNull('reminder_sent_at')
            ->whereBetween('unlock_date', [now(), now()->addMinutes(30)])
            ->get();

        foreach ($quizzes as $quiz) {
            $count = $assessment->sendQuizReminder($quiz->id);
            $quiz->update(['reminder_sent_at' => now()]);
            $this->info("Quiz #{$quiz->id} \"{$quiz->title}\": {$count} reminder(s) sent.");
        }

        return self::SUCCESS;
    }
}
