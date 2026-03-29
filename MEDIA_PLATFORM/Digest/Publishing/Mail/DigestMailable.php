<?php

namespace MediaPlatform\Digest\Publishing\Mail;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * DigestMailable — sends the full digest as the email body.
 *
 * Used when a list's output_type is OutputType::Email.
 * The digest HTML is the complete email body — there is no separate "notification"
 * for this type, because the email IS the digest.
 *
 * VIEW: views/digests/digest-email.blade.php
 */
class DigestMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  ListModel  $list        The list that produced this digest.
     * @param  array      $digestData  Structured digest from DigestBuilderService::build().
     */
    public function __construct(
        public ListModel $list,
        public array     $digestData,
    ) {}

    /**
     * Define the email envelope (subject line and recipients are set by the caller
     * via ->to() on the Mailable instance — this just sets the subject).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->list->name . ' — ' . now()->format('D, M j Y'),
        );
    }

    /**
     * Render the digest HTML email shell.
     * The $digestData array is passed directly to the view as $digestData.
     */
    public function content(): Content
    {
        return new Content(
            view: 'media_platform.digest.digest-email',
            with: [
                'digestData' => $this->digestData,
                'list'       => $this->list,
            ],
        );
    }
}