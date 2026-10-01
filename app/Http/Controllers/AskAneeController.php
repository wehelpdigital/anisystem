<?php

namespace App\Http\Controllers;

use App\Models\AsAskQuestion;
use App\Models\AsSitePage;
use App\Models\User;
use App\Services\AskAnee;
use App\Services\AskAneeLeads;
use App\Services\MailService;
use App\Support\AneeEmoji;
use App\Support\CropCatalog;
use App\Support\Recaptcha;
use App\Support\SitePages;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Try and Ask Anee (2026-10-01): one free farming question on the public site.
 *
 * Reordered the same day on the owner's word: the farm first, then the
 * question in one search bar.
 *
 *   1. farm     name, email, farm size, main crop, province and town; the
 *               visitor becomes a lead here (Acumbamail and the mother's CRM)
 *   2. ask      the question, in a search bar; Anee says whether she takes
 *               it (farming only), off the request's clock (state polls)
 *   3. (the page plays a short wait: the answer is "being prepared")
 *   4. send     the answer email goes out with its button, and the question
 *               joins the lead
 *   5. answer   the button: the answer is written then (a job the page
 *               waits on) as a page of its own at /question/{slug}, or an
 *               existing page that already answers it is opened
 *
 * Every step a visitor sends carries a reCAPTCHA token, a hidden field no
 * person fills, and a rate limit per address; the model is only asked at
 * step 2 and on the first opening of the answer.
 *
 * Who may ask (the same day, on the owner's word): people who are not
 * members yet, one question a week. Both are judged on the email in the
 * database, at every step that could spend the question (refusal()). The
 * browser keeps its own week as well (a cookie set when the answer is sent),
 * so one person cannot spend a new address on every question.
 */
class AskAneeController extends Controller
{
    /** One free question in this many days, for an email and for a browser. */
    private const WEEK_DAYS = 7;

    /** How many answers one browser may be sent in that week. */
    private const BROWSER_MAX = 1;

    private const BROWSER_COOKIE = 'anee_ask';

    public function __construct(private AskAnee $anee) {}

    public function page()
    {
        return view('public.ask.index', [
            'siteKey' => Recaptcha::siteKey(),
            'crops' => $this->crops(),
            'countries' => \App\Support\Region::countries(),
        ]);
    }

    /** Step 1: who is asking, and about what farm. A lead from here on. */
    public function farm(Request $request)
    {
        if ($refused = $this->guard($request, 'ask_farm')) {
            return $refused;
        }
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|string|max:190|email:rfc,dns',
            'farmSize' => 'required|numeric|min:0.01|max:1000000',
            'farmUnit' => 'required|in:ha,sqm',
            'crop' => 'required|string|max:40',
            'cropOther' => 'nullable|string|max:80',
            'country' => 'nullable|string|size:2',
            'province' => 'required|string|max:80',
            'town' => 'required|string|max:120',
            'token' => 'nullable|string|size:40',
        ], [
            'name.required' => 'What is your name?',
            'email.required' => 'Please type your email address.',
            'email.email' => 'That email address does not look right. Please check it.',
            'farmSize.required' => 'How big is the farm?',
            'crop.required' => 'Which crop do you grow?',
            'province.required' => 'Which province is the farm in?',
            'town.required' => 'Which town is the farm in?',
        ]);
        $crop = isset(CropCatalog::CROPS[$data['crop']]) ? $data['crop'] : null;
        $label = $crop ? self::plain(CropCatalog::label($crop)) : trim((string) ($data['cropOther'] ?? ''));
        if ($label === '') {
            return $this->json(false, 'Which crop do you grow?', ['errors' => ['crop' => ['Which crop do you grow?']]], 422);
        }
        $email = mb_strtolower(trim($data['email']));
        $ip = (string) $request->ip();
        foreach ([['ask-f-h:' . $ip, 10, 3600], ['ask-e:' . sha1($email), 6, 86400]] as [$key, $max, $decay]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return $this->json(false, 'That is a lot for one day. Please try again tomorrow.', ['limited' => true], 429);
            }
            RateLimiter::hit($key, $decay);
        }
        $editing = ! empty($data['token']) ? $this->byToken($data['token']) : null;
        if ($refused = $this->refusal($request, $email, $editing?->id)) {
            // Everyone who fills in the form joins the anee.io subscribers
            // list (the owner, 2026-10-02), even when this week's question is
            // already used. A member is on the list already, as a member, and
            // listing them again would stamp them a lead.
            if (($refused->getData(true)['data']['refused'] ?? '') !== 'member') {
                $name = (string) $data['name'];
                app()->terminating(fn () => app(AskAneeLeads::class)->listOnly($email, $name));
            }

            return $refused;
        }

        $fields = [
            'name' => Str::limit(trim(strip_tags($data['name'])), 118, ''),
            'email' => $email,
            'farmSize' => round((float) $data['farmSize'], 2),
            'farmUnit' => $data['farmUnit'],
            'crop' => $crop,
            'cropLabel' => Str::limit($label, 118, ''),
            'country' => \App\Support\Region::valid($data['country'] ?? 'PH') ?: 'PH',
            'province' => Str::limit(trim(strip_tags($data['province'])), 78, ''),
            'town' => Str::limit(trim(strip_tags($data['town'])), 118, ''),
        ];
        // The same visitor going back to fix a detail keeps their row.
        $q = $editing;
        if ($q && in_array($q->status, ['profile', 'declined', 'ready', 'failed'], true)) {
            $q->update($fields);
        } else {
            $q = AsAskQuestion::create($fields + [
                'token' => Str::random(40),
                'question' => '',
                'status' => 'profile',
                'ip' => $ip,
                'userAgent' => Str::limit((string) $request->userAgent(), 250, ''),
                'source' => Str::limit((string) $request->input('src', ''), 120, '') ?: null,
                'deleteStatus' => 1,
            ]);
        }

        $payload = ['success' => true, 'message' => 'ok', 'data' => [
            'token' => $q->token,
            'first' => Str::before($fields['name'], ' ') ?: $fields['name'],
        ]];
        // The marketing side after the answer: the visitor waits on nothing.
        $lead = fn () => app(AskAneeLeads::class)->captureFarm($q->fresh());
        if (function_exists('fastcgi_finish_request')) {
            response()->json($payload)->send();
            fastcgi_finish_request();
            $lead();
            exit;
        }
        $lead();

        return response()->json($payload);
    }

    /** Step 2: the question. */
    public function ask(Request $request)
    {
        if ($refused = $this->guard($request, 'ask_question')) {
            return $refused;
        }
        $data = $request->validate(['question' => 'required|string|min:6|max:700', 'token' => 'required|string|size:40']);
        $q = $this->byToken($data['token']);
        if (! $q) {
            return $this->json(false, 'Please fill in your farm details first.', ['restart' => true], 410);
        }
        if ($q->status === 'emailed') {
            return $this->json(false, 'You have used this week\'s free question. You can ask again next week.', ['used' => true], 409);
        }
        // Another tab may have spent this email's week since the farm step.
        if ($refused = $this->refusal($request, (string) $q->email, $q->id)) {
            return $refused;
        }
        $ip = (string) $request->ip();
        foreach ([['ask-q-h:' . $ip, 8, 3600], ['ask-q-d:' . $ip, 25, 86400]] as [$key, $max, $decay]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return $this->json(false, 'That is a lot of questions for one day. Please try again tomorrow.', ['limited' => true], 429);
            }
            RateLimiter::hit($key, $decay);
        }

        $q->update([
            'question' => trim(preg_replace('/\s+/u', ' ', strip_tags($data['question']))),
            'status' => 'asked',
            'isAgri' => null,
            'reply' => null,
            'matchedPageId' => null,
            'answerError' => null,
        ]);

        /* Anee reads it off the request's clock: her first word can take
         * twenty seconds, and the edge in front of anee.io gives a request
         * about that long. The page asks after her answer (state). */
        $read = function () use ($q) {
            $c = $this->anee->classify($q->question, [
                'name' => Str::before((string) $q->name, ' '),
                'farm' => trim($q->farmWords() . ' of ' . Str::before((string) $q->cropLabel, ' (') . ($q->placeWords() ? ' in ' . $q->placeWords() : '')),
            ]);
            $q->update($c['ok'] ? [
                'topic' => $c['topic'],
                'lang' => $c['lang'],
                'isAgri' => $c['agri'],
                'reply' => $c['reply'],
                'detectedCrop' => $c['crop'],
                'matchedPageId' => $c['match'],
                'status' => $c['agri'] ? 'ready' : 'declined',
            ] : ['status' => 'failed', 'answerError' => Str::limit((string) $c['error'], 480, '')]);
        };
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            response()->json(['success' => true, 'message' => 'Reading…', 'data' => ['pending' => true, 'token' => $q->token]])->send();
            fastcgi_finish_request();
            $read();
            exit;
        }
        $read();

        return $this->state($q->token);
    }

    /** What Anee said to the question, once she has read it. */
    public function state(string $token)
    {
        $q = $this->byToken($token);
        if (! $q) {
            return $this->json(false, 'That question has expired. Please ask it again.', [], 410);
        }
        if ($q->status === 'asked') {
            if ($q->updated_at->lt(now()->subSeconds(100))) {
                $q->update(['status' => 'failed', 'answerError' => 'Anee took too long to read that.']);

                return $this->json(false, 'Anee could not read that just now. Please try again.', [], 503);
            }

            return $this->json(true, 'Reading…', ['pending' => true, 'token' => $q->token]);
        }
        if ($q->status === 'failed') {
            return $this->json(false, 'Anee could not read that just now. Please try again.', [], 503);
        }

        return $this->json(true, 'ok', [
            'token' => $q->token,
            'agri' => (bool) $q->isAgri,
            'reply' => $this->said((string) $q->reply),
        ]);
    }

    /** Step 4: the answer email, after the wait. The question joins the lead. */
    public function send(Request $request)
    {
        if ($refused = $this->guard($request, 'ask_send')) {
            return $refused;
        }
        $q = $this->byToken((string) $request->input('token'));
        if (! $q || ! $q->isAgri || ! in_array($q->status, ['ready', 'emailed'], true)) {
            return $this->json(false, 'That question has expired. Please ask it again.', [], 410);
        }
        if ($q->status === 'emailed') {
            return $this->json(true, 'Sent.', ['email' => $q->email]);
        }
        // The last door before the question is spent: asked once more.
        if ($refused = $this->refusal($request, (string) $q->email, $q->id)) {
            return $refused;
        }
        $url = route('ask.answer', ['token' => $q->token]);
        $sent = app(MailService::class)->sendTemplate('ask_anee_answer', (string) $q->email, (string) $q->name, [
            'firstName' => e(Str::before((string) $q->name, ' ') ?: 'there'),
            'question' => e(Str::limit($q->question, 400)),
            'crop' => e(Str::before((string) $q->cropLabel, ' (')),
            'farmSize' => e($q->farmWords()),
            'location' => e($q->placeWords()),
            'answerUrl' => $url,
            'signupUrl' => url('/signup') . '?utm_source=ask-anee&utm_medium=email&utm_campaign=answer',
            'siteName' => 'anee.io',
        ], ['relatedType' => 'ask_question', 'relatedId' => $q->id]);
        if (! $sent) {
            return $this->json(false, 'The email could not be sent just now. Please try again.', [], 502);
        }
        $q->update(['status' => 'emailed', 'emailedAt' => now()]);

        /* The lead hears of the question once the visitor has their answer.
         * A terminating callback, not a hand sent response: the browser's
         * week travels as a cookie, and only a response that goes back out
         * through the middleware gets it sealed and attached. (Symfony's
         * send() finishes the FastCGI request before these callbacks run.) */
        app()->terminating(function () use ($q, $url) {
            if ($now = $q->fresh()) {
                app(AskAneeLeads::class)->noteQuestion($now, $url);
            }
        });
        $week = $this->browserSends($request);
        $week[] = now()->getTimestamp();

        return response()->json(['success' => true, 'message' => 'Sent.', 'data' => [
            'email' => $q->email,
            'next' => now()->addDays(self::WEEK_DAYS)->toIso8601String(),
        ]])->withCookie(cookie(self::BROWSER_COOKIE, json_encode($week), self::WEEK_DAYS * 1440));
    }

    /** Step 5: the emailed button. The answer, or the wait for it. */
    public function answer(string $token)
    {
        $q = $this->byToken($token);
        abort_unless($q && $q->isAgri, 404);
        if (! $q->openedAt) {
            $q->forceFill(['openedAt' => now()])->save();
        }
        if ($page = $this->pageFor($q)) {
            return redirect()->to(SitePages::pageUrl($page));
        }

        return response()->view('public.ask.answer', ['q' => $q])->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /** Write the answer, off the request's clock. */
    public function start(string $token)
    {
        $q = $this->byToken($token);
        if (! $q || ! $q->isAgri) {
            return $this->json(false, 'That question is not here anymore.', [], 404);
        }
        if ($page = $this->pageFor($q)) {
            return $this->json(true, 'Ready.', ['status' => 'ready', 'url' => SitePages::pageUrl($page)]);
        }
        if ($q->answerStatus === 'working' && ! $this->dead($q)) {
            return $this->json(true, 'Working…', ['pending' => true]);
        }
        $q->update(['answerStatus' => 'working', 'answerPhase' => 'start', 'answerTry' => 1, 'answerStartedAt' => now(),
            'answerBeatAt' => now(), 'answerError' => null]);

        $work = function () use ($q) {
            $beat = function (string $phase, int $try = 1) use ($q) {
                AsAskQuestion::where('id', $q->id)->update(['answerPhase' => $phase, 'answerTry' => $try, 'answerBeatAt' => now()]);
            };
            try {
                $page = $this->anee->answer($q->fresh(), $beat);
                $q->update(['pageId' => $page->id, 'answerStatus' => 'ready']);
            } catch (\Throwable $e) {
                report($e);
                $q->update(['answerStatus' => 'failed', 'answerError' => Str::limit($e->getMessage(), 480, '')]);
            }
        };
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => ['pending' => true]])->send();
            fastcgi_finish_request();
            $work();
            exit;
        }
        @set_time_limit(600);
        $work();

        return $this->job($token);
    }

    public function job(string $token)
    {
        $q = $this->byToken($token);
        if (! $q) {
            return $this->json(false, 'That question is not here anymore.', ['status' => 'failed'], 404);
        }
        if ($page = $this->pageFor($q)) {
            return $this->json(true, 'Ready.', ['status' => 'ready', 'url' => SitePages::pageUrl($page)]);
        }
        if ($q->answerStatus === 'working') {
            if ($this->dead($q)) {
                $q->update(['answerStatus' => 'failed', 'answerError' => 'The answer was interrupted. Please try again.']);

                return $this->json(false, 'The answer was interrupted. Please try again.', ['status' => 'failed'], 502);
            }

            return $this->json(true, 'Working…', [
                'status' => 'pending',
                'phase' => (string) ($q->answerPhase ?: 'start'),
                'try' => (int) ($q->answerTry ?: 1),
                'beatAgo' => (int) max(0, now()->diffInSeconds($q->answerBeatAt ?? $q->updated_at, true)),
            ]);
        }
        if ($q->answerStatus === 'failed') {
            return $this->json(false, $q->answerError ?: 'The answer could not be written. Please try again.', ['status' => 'failed'], 502);
        }

        return $this->json(true, 'Not started.', ['status' => 'idle']);
    }

    /** The list of answered questions, drawn a page at a time as the reader scrolls. */
    public function questions(Request $request)
    {
        $per = 12;
        $pages = $this->published()->paginate($per);
        if ($request->boolean('rows')) {
            return response()->json(['success' => true, 'data' => [
                'html' => view('public.ask.tiles', ['pages' => $pages->getCollection()])->render(),
                'hasMore' => $pages->hasMorePages(),
                'next' => $pages->currentPage() + 1,
            ]]);
        }

        return view('public.ask.questions', [
            'pages' => $pages,
            'meta' => SitePages::SECTIONS['questions'],
            'total' => $pages->total(),
        ]);
    }

    // ------------------------------------------------------------------

    private function published()
    {
        return AsSitePage::live()->where('section', 'questions')->orderByDesc('publishedAt')->orderByDesc('id');
    }

    /** The page that answers a question: its own, or the one it matched. */
    private function pageFor(AsAskQuestion $q): ?AsSitePage
    {
        foreach ([$q->pageId, $q->matchedPageId] as $id) {
            if ($id && ($p = AsSitePage::live()->where('id', $id)->first())) {
                return $p;
            }
        }

        return null;
    }

    private function dead(AsAskQuestion $q): bool
    {
        $beat = $q->answerBeatAt ?? $q->updated_at;

        return $beat->lt(now()->subSeconds(\App\Services\AiClient::TIMEOUT_SEARCHED + 90))
            || ($q->answerStartedAt && $q->answerStartedAt->lt(now()->subMinutes(20)));
    }

    private function byToken(string $token): ?AsAskQuestion
    {
        return strlen($token) === 40 ? AsAskQuestion::where('token', $token)->where('deleteStatus', 1)->first() : null;
    }

    /**
     * The checks every step shares: a hidden field a person never fills, a
     * page that was open long enough to be read, and Google's word.
     */
    private function guard(Request $request, string $action)
    {
        if (trim((string) $request->input('website', '')) !== '') {
            return $this->json(false, 'Something went wrong. Please try again.', [], 422);
        }
        $check = Recaptcha::verify((string) $request->input('captcha'), $action, $request->ip());
        if (! $check['ok']) {
            return $this->json(false, 'We could not confirm you are not a robot. Please reload the page and try again.', ['captcha' => $check['why']], 422);
        }

        return null;
    }

    /**
     * Why this email (or this browser) may not ask now, or null.
     *
     *   member   the email already has an anee.io login: they ask in the app
     *   week     the email was sent an answer in the last seven days
     *   browser  this browser was, under any email (the cookie's week)
     *
     * The page shows each in a modal and starts the form over. $except is the
     * question being asked now, which never counts against itself.
     */
    private function refusal(Request $request, string $email, ?int $except = null)
    {
        $email = mb_strtolower(trim($email));
        if ($email !== '' && User::where('email', $email)->where('deleteStatus', 1)->exists()) {
            return $this->json(false, 'This email already has an anee.io account. Log in to ask Anee.', [
                'refused' => 'member', 'email' => $email,
            ], 403);
        }
        $last = $email === '' ? null : AsAskQuestion::where('email', $email)
            ->where('status', 'emailed')->where('deleteStatus', 1)
            ->where('emailedAt', '>=', now()->subDays(self::WEEK_DAYS))
            ->when($except, fn ($w) => $w->where('id', '!=', $except))
            ->max('emailedAt');
        if ($last) {
            return $this->refusedUntil('week', $email, Carbon::parse($last)->addDays(self::WEEK_DAYS),
                'This email already asked Anee this week.');
        }
        $used = $this->browserSends($request);
        if (count($used) >= self::BROWSER_MAX) {
            // Open again when enough of them have aged out of the week.
            $until = Carbon::createFromTimestamp($used[count($used) - self::BROWSER_MAX], config('app.timezone'))->addDays(self::WEEK_DAYS);

            return $this->refusedUntil('browser', $email, $until, 'This browser already asked Anee this week.');
        }

        return null;
    }

    private function refusedUntil(string $why, string $email, Carbon $until, string $message)
    {
        return $this->json(false, $message, [
            'refused' => $why,
            'email' => $email,
            'until' => $until->toIso8601String(),
            'untilText' => $until->format('l, F j \a\t g:i A'),
        ], 403);
    }

    /** When this browser was sent its answers this week (the cookie), oldest first. */
    private function browserSends(Request $request): array
    {
        $raw = json_decode((string) $request->cookie(self::BROWSER_COOKIE, '[]'), true);
        $since = now()->subDays(self::WEEK_DAYS)->getTimestamp();
        $out = array_values(array_filter(is_array($raw) ? $raw : [], fn ($t) => is_int($t) && $t > $since && $t <= time() + 60));
        sort($out);

        return $out;
    }

    /** Anee's words as the page draws them: escaped, then her faces. */
    private function said(string $text): string
    {
        $paras = array_filter(array_map('trim', preg_split('/\n{2,}/', $text) ?: []));

        return AneeEmoji::render(implode('', array_map(fn ($p) => '<p>' . nl2br(e($p)) . '</p>', $paras)));
    }

    /** The crop picker's shelves: [group => [key => label]]. */
    private function crops(): array
    {
        $out = [];
        foreach (CropCatalog::visible() as $key => $c) {
            $out[$c['group'] ?? 'Other'][$key] = ['label' => self::plain($c['label']), 'icon' => $c['icon'] ?? '🌱'];
        }
        $ordered = [];
        foreach (CropCatalog::GROUPS as $g) {
            if (isset($out[$g])) {
                $ordered[$g] = $out[$g];
            }
        }

        return $ordered + $out;
    }

    /** A catalogue label without its dash: "Rice, transplanted (Palay)". */
    public static function plain(string $label): string
    {
        return trim(str_replace([' — ', ' – ', '—', '–'], [', ', ', ', ', ', ', '], $label));
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
