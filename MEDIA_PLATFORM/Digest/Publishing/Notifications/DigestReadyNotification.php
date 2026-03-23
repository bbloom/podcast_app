<?php

namespace MediaPlatform\Digest\Publishing\Notifications;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * DigestReadyNotification — "your digest is ready" nudge email.
 *
 * Sent after a successful SFTP upload when the list has notify_by_email = true.
 * The digest itself lives at the SFTP destination; this is just a short email
 * with a clickable link to it.
 *
 * NOT used for email output type — that type sends the full digest directly.
 * NOT used for WordPress — the post is publicly accessible without notification.
 */
class DigestReadyNotification extends Notification
{
    use Queueable;

    /**
     * @param  ListModel          $list      The list that produced this digest.
     * @param  OutputDestination  $dest      The SFTP destination it was published to.
     * @param  string             $filename  The uploaded filename (no extension — .html is appended internally).
     * @param  string             $excerpt   Short summary e.g. "12 items from 3 sources".
     */
    public function __construct(
        public ListModel         $list,
        public OutputDestination $dest,
        public string            $filename,
        public string            $excerpt,
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
     * Constructs the public URL as base_url/filename (base_url has no trailing slash).
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Construct the public URL where the digest was published.
        // base_url is configured on the OutputDestination, e.g. https://mysite.com/digests
        $url = rtrim($this->dest->base_url ?? '', '/') . '/' . $this->filename . '.html';

        return (new MailMessage)
            ->subject("Your digest is ready: {$this->list->name}")
            ->greeting('Your digest is ready!')
            ->line("The **{$this->list->name}** digest has been published.")
            ->line($this->excerpt)
            ->action('View Digest', $url)
            ->line('This notification was sent because you have email notifications enabled for this list.');
    }
}