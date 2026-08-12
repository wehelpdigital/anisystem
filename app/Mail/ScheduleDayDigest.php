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
            subject: \App\Support\MailTemplate::subject(
                \App\Models\AsEmailTemplate::KEY_DAILY_DIGEST,
                $this->mergeValues(),
                $this->dateLabel . ' — ' . $this->scheduleTitle,
            ),
        );
    }

    public function content(): Content
    {
        // A layout built in the mother app wins; without one, the view that
        // has always been here. Either way the same data goes in.
        $html = \App\Support\MailTemplate::render(
            \App\Models\AsEmailTemplate::KEY_DAILY_DIGEST,
            $this->mergeValues() + ['activities_list' => $this->activitiesHtml()],
        );

        return $html !== null
            ? new Content(htmlString: $html)
            : new Content(view: 'emails.schedule-day');
    }

    /** @return array<string, string> */
    private function mergeValues(): array
    {
        $today = collect($this->activities)->filter(fn ($a) => str_starts_with((string) ($a['tags'] ?? ''), 'Today'))->count();
        $tomorrow = collect($this->activities)->filter(fn ($a) => str_starts_with((string) ($a['tags'] ?? ''), 'Tomorrow'))->count();

        return [
            'recipient_name' => $this->workerName,
            'schedule_title' => $this->scheduleTitle,
            'today_date' => $this->dateLabel,
            'tomorrow_date' => now('Asia/Manila')->addDay()->format('l, M j'),
            'today_count' => (string) $today,
            'tomorrow_count' => (string) $tomorrow,
            'app_name' => 'AniSystem',
        ];
    }

    /**
     * The one part of the email the layout cannot hold: this person's own
     * work. Inline styles, because a stylesheet does not survive the trip.
     */
    private function activitiesHtml(): string
    {
        if (! $this->activities) {
            return '<p style="margin:0 0 14px;font-family:Helvetica,Arial,sans-serif;font-size:15px;color:#6b7280;">'
                . 'Nothing scheduled.</p>';
        }

        $rows = '';
        foreach ($this->activities as $a) {
            $rows .= '<tr><td style="padding:10px 0;border-bottom:1px solid #eef1f4;'
                . 'font-family:Helvetica,Arial,sans-serif;">'
                . '<div style="font-size:15px;font-weight:bold;color:#111827;">' . e($a['title'] ?? '') . '</div>'
                . (filled($a['tags'] ?? null)
                    ? '<div style="font-size:12px;color:#6b7280;margin-top:2px;">' . e($a['tags']) . '</div>' : '')
                . (filled($a['description'] ?? null)
                    ? '<div style="font-size:13px;color:#4b5563;margin-top:4px;">'
                        . e(\Illuminate\Support\Str::limit((string) $a['description'], 220)) . '</div>' : '')
                . '</td></tr>';
        }

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">'
            . $rows . '</table>';
    }
}
