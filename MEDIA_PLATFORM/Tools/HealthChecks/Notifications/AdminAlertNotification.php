<?php

namespace MediaPlatform\Tools\HealthChecks\Notifications;

use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public AdminAlert $alert
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tierLabel = match ($this->alert->tier) {
            2 => 'Degraded Mode',
            3 => 'Human Intervention Required',
            default => 'Alert',
        };

        $mail = (new MailMessage)
            ->subject("[Tier {$this->alert->tier}] {$this->alert->title}")
            ->greeting("Health Check Alert — {$tierLabel}")
            ->line("**Category:** {$this->alert->category}")
            ->line("**Issue:** {$this->alert->title}")
            ->line($this->alert->message);

        if ($this->alert->tier === 3) {
            $mail->line('**This alert requires manual resolution.** Processing for the affected subsystem is blocked until you resolve this alert.')
                ->action('View Health Checks', url('/admin/health-checks'));
        } else {
            $mail->line('This alert will auto-resolve when the issue clears.');
        }

        $mail->line('Detected at: ' . $this->alert->created_at->format('Y-m-d H:i:s T'));

        return $mail;
    }
}
