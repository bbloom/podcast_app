<?php

namespace MediaPlatform\Digest\Publishing\Notifications;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * DigestEmptyNotification — informs the user that a digest run found nothing new.
 *
 * Sent when PublishDigest finds zero pending summaries for a list. No file is
 * uploaded, no email digest is sent, and no WordPress post is created — only
 * this notification email is dispatched.
 *
 * WHY SEND THIS?
 * ──────────────
 * If a list has been running for a while and suddenly produces nothing, it may
 * indicate that sources have gone quiet, a feed URL has changed, or something
 * was suspended. A silent skip would make that invisible to the user.
 */
class DigestEmptyNotification extends Notification
{
    use Queueable;

    /**
     * @param  ListModel  $list  The list that ran but produced nothing new.
     */
    public function __construct(
        public ListModel $list,
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
            ->subject("No new items: {$this->list->name}")
            ->greeting('Nothing new this time.')
            ->line("The **{$this->list->name}** digest ran on schedule but found no new relevant content.")
            ->line('This can happen if your sources have not published anything new, or if search mode filtered everything out.')
            ->line('No digest was published. Your sources will be checked again on the next scheduled run.');
    }
}