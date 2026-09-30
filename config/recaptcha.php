<?php

/*
|--------------------------------------------------------------------------
| reCAPTCHA Enterprise — the public forms' bot check
|--------------------------------------------------------------------------
|
| Try and Ask Anee (2026-10-01) asks Google for a score on every step a
| visitor sends. The site key is public: it rides in the page. It is read
| from RECAPTCHA_SITE_KEY, or from RECPATCHA, the name it was first given.
|
| Checking a token needs Google Cloud's reCAPTCHA Enterprise API: the
| project that owns the key (RECAPTCHA_PROJECT_ID) and an API key allowed to
| call that API (RECAPTCHA_API_KEY). Until both are set the page still runs
| the check in the browser and the server takes the token on trust, leaning
| on the rate limits and the hidden field instead (App\Support\Recaptcha).
|
*/

return [
    'site_key' => (string) (env('RECAPTCHA_SITE_KEY') ?: env('RECPATCHA', '')),
    'project_id' => (string) env('RECAPTCHA_PROJECT_ID', ''),
    'api_key' => (string) env('RECAPTCHA_API_KEY', ''),
    // Google's score runs 0 (a bot) to 1 (a person).
    'min_score' => (float) env('RECAPTCHA_MIN_SCORE', 0.5),
];
