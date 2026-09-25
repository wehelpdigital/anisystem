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
            : new Content(view: 'emails.schedule-day', with: ['listHtml' => $this->activitiesHtml()]);
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
            'app_name' => 'anee.io',
        ];
    }

    /**
     * The one part of the email the layout cannot hold: this person's own
     * work, and — when the schedule has a public page — the way to it.
     * Inline styles, because a stylesheet does not survive the trip.
     */
    private function activitiesHtml(): string
    {
        $link = $this->publicUrl
            ? \App\Support\EmailSkin::button('See the whole plan', $this->publicUrl, false)
            : '';

        if (! $this->activities) {
            return \App\Support\EmailSkin::panel('Nothing is scheduled. Enjoy the rest day.') . $link;
        }

        $rows = '';
        foreach ($this->activities as $a) {
            // "Today · Lot 2" — the day word becomes the badge, the rest the facts.
            $meta = trim((string) ($a['tags'] ?? ''));
            $when = '';
            if (preg_match('~^(Today|Tomorrow)\b\s*(?:·\s*)?(.*)$~u', $meta, $m)) {
                [$when, $meta] = [$m[1], trim($m[2])];
            }
            $rows .= \App\Support\EmailSkin::taskRow(
                e($a['title'] ?? ''),
                e($meta),
                filled($a['description'] ?? null)
                    ? e(\Illuminate\Support\Str::limit(strip_tags((string) $a['description']), 220)) : '',
                e($when),
            );
        }

        return \App\Support\EmailSkin::taskList($rows) . $link;
    }
}
