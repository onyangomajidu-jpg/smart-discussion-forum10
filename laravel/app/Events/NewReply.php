<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewReply implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $postId,
        public int $userId,
        public string $body
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('post.' . $this->postId);
    }

    public function broadcastAs(): string
    {
        return 'new.reply';
    }
}
