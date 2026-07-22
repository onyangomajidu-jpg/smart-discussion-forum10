<?php

namespace App\Notifications;

use App\Models\PrivateMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PrivateMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PrivateMessage $message) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'private_message',
            'from_id' => $this->message->sender_id,
            'user'    => $this->message->sender->name,
            'excerpt' => $this->message->body
                ? (string) str($this->message->body)->limit(100)
                : '🎤 Voice message',
        ];
    }
}
