<?php

namespace App\Notifications;

use App\Models\Reply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AnswerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Reply $reply) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'answer',
            'reply_id'=> $this->reply->id,
            'post_id' => $this->reply->post_id,
            'user'    => $this->reply->author->name,
            'excerpt' => str($this->reply->body)->limit(100),
        ];
    }
}
