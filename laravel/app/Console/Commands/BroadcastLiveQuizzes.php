<?php

namespace App\Console\Commands;

use App\Events\QuizLive;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Console\Command;

class BroadcastLiveQuizzes extends Command
{
    protected $signature   = 'quizzes:broadcast-live';
    protected $description = 'Broadcast QuizLive event for quizzes that just opened';

    public function handle(): void
    {
        Quiz::published()
            ->with('group.members')
            ->get()
            ->filter(fn($q) => $q->isOpen())
            ->each(function ($quiz) {
                $quiz->group->members->each(function ($user) use ($quiz) {
                    $alreadyAttempted = QuizAttempt::where('quiz_id', $quiz->id)
                        ->where('user_id', $user->id)
                        ->exists();
                    if (!$alreadyAttempted) {
                        broadcast(new QuizLive($quiz, $user->id));
                    }
                });
            });
    }
}
