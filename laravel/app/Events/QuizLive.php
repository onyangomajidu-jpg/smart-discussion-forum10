<?php

namespace App\Events;

use App\Models\Quiz;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizLive implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $quiz;

    public function __construct(Quiz $quiz, int $userId)
    {
        $this->quiz = [
            'id'            => $quiz->id,
            'title'         => $quiz->title,
            'group'         => $quiz->group->name,
            'duration'      => $quiz->duration_minutes,
            'hard_deadline' => $quiz->hard_deadline?->format('d M, H:i'),
            'url'           => route('quizzes.take', $quiz),
        ];
        $this->dontBroadcastToCurrentUser();
    }

    public function broadcastOn(): Channel
    {
        return new Channel('quiz-alerts');
    }

    public function broadcastAs(): string
    {
        return 'quiz.live';
    }
}
