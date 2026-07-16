<?php

namespace App\Events;

use App\Models\Post;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostCreated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Post $post) {}

    public function broadcastOn(): Channel
    {
        return new Channel('topic.' . $this->post->topic_id);
    }

    public function broadcastAs(): string
    {
        return 'post.created';
    }
}
