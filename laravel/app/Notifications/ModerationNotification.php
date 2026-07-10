<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ModerationNotification extends Notification
{
    public function __construct(
        private string $type,
        private string $reason,
        private int $days = 0
    ) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toArray(object $notifiable): array
    {
        return match ($this->type) {
            'warning'   => ['type' => 'warning',   'message' => "You received a warning: {$this->reason}"],
            'blacklist' => ['type' => 'blacklist',  'message' => "You have been suspended for {$this->days} days: {$this->reason}"],
            default     => ['type' => $this->type,  'message' => $this->reason],
        };
    }
}
