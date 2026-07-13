<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PostNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Post $post) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'     => 'new_post',
            'post_id'  => $this->post->id,
            'topic_id' => $this->post->topic_id,
            'user'     => $this->post->author->name,
            'excerpt'  => str($this->post->body)->limit(100),
        ];
    }
}
