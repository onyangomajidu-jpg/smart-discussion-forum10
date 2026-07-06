<?php

namespace App\Notifications;

use App\Models\Quiz;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to every group member before a quiz opens (SDD §4.2.3 — sendQuizReminder).
 * Delivered via database channel (in-app) and optionally mail.
 */
class QuizReminderNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Quiz $quiz) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $start = $this->quiz->unlock_date
            ? $this->quiz->unlock_date->format('D d M Y, H:i')
            : 'soon';

        return (new MailMessage)
            ->subject('📝 Quiz Reminder: ' . $this->quiz->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A quiz is scheduled in your group **' . $this->quiz->group->name . '**.')
            ->line('**Quiz:** ' . $this->quiz->title)
            ->line('**Opens:** ' . $start)
            ->line('**Duration:** ' . $this->quiz->duration_minutes . ' minutes')
            ->line('**Deadline:** ' . ($this->quiz->hard_deadline?->format('D d M Y, H:i') ?? 'N/A'))
            ->action('Go to Quiz', url('/quizzes/' . $this->quiz->id))
            ->line('Good luck!');
    }

    public function toArray(object $notifiable): array
    {
        $opensAt = $this->quiz->unlock_date
            ? $this->quiz->unlock_date->format('D d M Y, H:i')
            : 'soon';

        return [
            'type'       => 'quiz_reminder',
            'quiz_id'    => $this->quiz->id,
            'quiz_title' => $this->quiz->title,
            'group_id'   => $this->quiz->group_id,
            'group_name' => $this->quiz->group->name,
            'opens_at'   => $this->quiz->unlock_date?->toIso8601String(),
            'deadline'   => $this->quiz->hard_deadline?->toIso8601String(),
            'duration'   => $this->quiz->duration_minutes,
            'message'    => 'Quiz "' . $this->quiz->title . '" starts at ' . $opensAt,
        ];
    }
}
