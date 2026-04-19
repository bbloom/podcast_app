<?php

namespace MediaPlatform\Digest\Publishing\Notifications;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * StaticSiteDigestReadyNotification — confirms a static site digest was published.
 *
 * Sent after the StaticSiteDeliveryStrategy persists the digest data and fires
 * the deploy hooks. This is a confirmation email only — it does NOT contain the
 * actual digest content (unlike the email output type where the email IS the digest).
 *
 * The user knows where their static site lives; this email just confirms the
 * pipeline ran successfully and provides the date, item count, and slug.
 */
class StaticSiteDigestReadyNotification extends Notification
{
    use Queueable;

    /**
     * @param  ListModel  $list     The list that produced this digest.
     * @param  string     $slug     The slug/identifier for this digest run.
     * @param  string     $excerpt  Short summary e.g. "12 items from 3 sources".
     */
    public function __construct(
        public ListModel $list,
        public string    $slug,
        public string    $excerpt,
    ) {}

    /**
     * This notification is always delivered by email.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the notification email.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your digest is ready: {$this->list->name}")
            ->greeting('Your static site digest is ready!')
            ->line("The **{$this->list->name}** digest has been published and deploy hooks have been fired.")
            ->line($this->excerpt)
            ->line("Digest: **{$this->slug}**")
            ->line('Your static site should rebuild automatically. If it does not, you can trigger a manual build from the list page.')
            ->line('This notification was sent because you have email notifications enabled for this list.');
    }
}