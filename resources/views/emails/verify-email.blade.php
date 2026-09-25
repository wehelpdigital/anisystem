{{-- The sign-up confirmation, for a database that has no email_verification
     template yet (the editable one lives in the mother app's email editor).
     Same house frame as every other email: App\Support\EmailSkin. --}}
{!! \App\Support\EmailSkin::wrap(
    '<p>Hi ' . e($firstName) . ',</p>'
    . '<p>' . e(\App\Support\Region::t('thanks')) . ' for signing up! One tap and your free anee.io account opens — your cropping '
    . 'schedules, the activities board, and Anee, your AI farm technician, are waiting on the other side.</p>'
    . \App\Support\EmailSkin::button('Confirm my email', $link)
    . \App\Support\EmailSkin::note('The link works for 3 days. If you did not sign up for anee.io, you can ignore this email and nothing will happen.'),
    'Confirm your email',
    [
        'face' => 'happy',
        'eyebrow' => 'One last step',
        'preheader' => 'One tap and your anee.io account is open.',
        'why' => 'You are getting this because this address was used to sign up at anee.io.',
    ]
) !!}
