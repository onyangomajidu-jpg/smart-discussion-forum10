<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewPost implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $topicId,
        public int $userId,
        public string $body,
        public string $type = 'post'
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('topic.' . $this->topicId);
    }

    public function broadcastAs(): string
    {
        return 'new.post';
    }
}
