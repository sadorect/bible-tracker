<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\Notifications\NotificationPreferenceResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AutomationAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $body,
        private readonly string $notificationKey,
        private readonly ?string $actionUrl = null,
        private readonly ?string $actionLabel = null,
        private readonly string $tone = 'slate',
        private readonly string $category = 'automation',
        private readonly array $extraData = [],
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable instanceof User) {
            $resolver = app(NotificationPreferenceResolver::class);

            if ($resolver->allowsDatabase($notifiable, $this->category)) {
                $channels[] = 'database';
            }

            if ($resolver->allowsMail($notifiable, $this->category) && filled($notifiable->email)) {
                $channels[] = 'mail';
            }
        }

        return array_unique($channels);
    }

    public function toArray(object $notifiable): array
    {
        return array_merge([
            'title' => $this->title,
            'body' => $this->body,
            'notification_key' => $this->notificationKey,
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
            'tone' => $this->tone,
            'category' => $this->category,
            'sent_at' => now()->toIso8601String(),
        ], $this->extraData);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->body);

        if ($this->actionUrl && $this->actionLabel) {
            $mail->action($this->actionLabel, $this->actionUrl);
        }

        return $mail->line('Stay steady and keep the journey moving.');
    }
}
