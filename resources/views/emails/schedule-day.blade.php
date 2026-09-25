{{-- The day's work, when the mother app has no daily_digest layout to send
     instead. Same house frame as every templated email (App\Support\EmailSkin);
     $listHtml is the Mailable's own list of this person's work, already safe. --}}
{!! \App\Support\EmailSkin::wrap(
    '<p style="margin:0 0 16px;">Hi ' . e($workerName) . ', here is the plan for <strong>' . e($dateLabel) . '</strong> on ' . e($scheduleTitle) . '.</p>'
    . $listHtml
    . \App\Support\EmailSkin::note('Sent with anee.io. If anything looks off, check with the farm before you head out.'),
    e($dateLabel),
    [
        'face' => 'salute',
        'eyebrow' => e($scheduleTitle),
        'preheader' => 'The plan for ' . e($dateLabel) . '.',
        'why' => 'You are getting this because a schedule on anee.io sends you its daily plan.',
    ]
) !!}
