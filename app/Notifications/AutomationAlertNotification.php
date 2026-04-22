<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\Automation\AutomationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AutomationAlertNotification extends Notification
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
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        $settings = app(AutomationSettings::class);

        if ($settings->emailEnabled()
            && $notifiable instanceof User
            && filled($notifiable->email)
            && in_array(
                $notifiable->message_delivery_preference ?: User::MESSAGE_DELIVERY_BOTH,
                [User::MESSAGE_DELIVERY_BOTH, User::MESSAGE_DELIVERY_EMAIL],
                true
            )) {
            $channels[] = 'mail';
        }

        return array_unique($channels);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'notification_key' => $this->notificationKey,
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
            'tone' => $this->tone,
            'category' => $this->category,
            'sent_at' => now()->toIso8601String(),
        ];
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
