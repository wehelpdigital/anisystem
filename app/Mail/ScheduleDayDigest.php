<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Here's what's on for today / tomorrow" digest emailed to a worker.
 * Plain data only (no models) so it survives queue serialization cleanly.
 */
class ScheduleDayDigest extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int,array{title:string,tags:string,description:?string}>  $activities
     */
    public function __construct(
        public string $scheduleTitle,
        public string $dateLabel,
        public string $workerName,
        public array $activities,
        public ?string $publicUrl = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->dateLabel . ' — ' . $this->scheduleTitle,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.schedule-day');
    }
}
