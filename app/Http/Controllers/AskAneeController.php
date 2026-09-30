<?php

namespace App\Http\Controllers;

use App\Models\AsAskQuestion;
use App\Models\AsSitePage;
use App\Services\AskAnee;
use App\Services\AskAneeLeads;
use App\Services\MailService;
use App\Support\AneeEmoji;
use App\Support\CropCatalog;
use App\Support\Recaptcha;
use App\Support\SitePages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Try and Ask Anee (2026-10-01): one free farming question on the public site.
 *
 *   1. ask      the question; Anee says whether she takes it (farming only)
 *   2. details  the farm: size, crop, province and town
 *   3. (the page plays a short wait: the answer is "being prepared")
 *   4. email    where to send it; the visitor becomes a lead (Acumbamail
 *               and the mother's CRM) and the email goes out with a button
 *   5. answer   the button: the answer is written then (a job the page
 *               waits on) as a page of its own at /question/{slug}, or an
 *               existing page that already answers it is opened
 *
 * Every step a visitor sends carries a reCAPTCHA token, a hidden field no
 * person fills, and a rate limit per address; the model is only ever asked
 * at step 1 and on the first opening of the answer.
 */
class AskAneeController extends Controller
{
    public function __construct(private AskAnee $anee) {}

    public function page()
    {
        return view('public.ask.index', [
            'siteKey' => Recaptcha::siteKey(),
            'crops' => $this->crops(),
            'recent' => $this->published()->limit(6)->get(),
            'countries' => \App\Support\Region::countries(),
        ]);
    }

    /** Step 1: the question. */
    public function ask(Request $request)
    {
        if ($refused = $this->guard($request, 'ask_question')) {
            return $refused;
        }
        $data = $request->validate(['question' => 'required|string|min:6|max:700']);
        $ip = (string) $request->ip();
        foreach ([['ask-q-h:' . $ip, 8, 3600], ['ask-q-d:' . $ip, 25, 86400]] as [$key, $max, $decay]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return $this->json(false, 'That is a lot of questions for one day. Make a free account and ask Anee as much as you like.', ['limited' => true], 429);
            }
            RateLimiter::hit($key, $decay);
        }

        $question = trim(preg_replace('/\s+/u', ' ', strip_tags($data['question'])));
        $q = AsAskQuestion::create([
            'token' => Str::random(40),
            'question' => $question,
            'status' => 'asked',
            'ip' => $ip,
            'userAgent' => Str::limit((string) $request->userAgent(), 250, ''),
            'source' => Str::limit((string) $request->input('src', ''), 120, '') ?: null,
            'deleteStatus' => 1,
        ]);

        /* Anee reads it off the request's clock: her first word can take
         * twenty seconds, and the edge in front of anee.io gives a request
         * about that long. The page asks after her answer (state). */
        $read = function () use ($q) {
            $c = $this->anee->classify($q->question);
            $q->update($c['ok'] ? [
                'topic' => $c['topic'],
                'lang' => $c['lang'],
                'isAgri' => $c['agri'],
                'reply' => $c['reply'],
                'detectedCrop' => $c['crop'],
                'matchedPageId' => $c['match'],
                'status' => $c['agri'] ? 'details' : 'declined',
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
        $q = strlen($token) === 40 ? AsAskQuestion::where('token', $token)->where('deleteStatus', 1)->first() : null;
        if (! $q) {
            return $this->json(false, 'That question has expired. Please ask it again.', [], 410);
        }
        if ($q->status === 'asked') {
            if ($q->created_at->lt(now()->subSeconds(100))) {
                $q->update(['status' => 'failed', 'answerError' => 'Anee took too long to read that.']);

                return $this->json(false, 'Anee could not read that just now. Please try again.', [], 503);
            }

            return $this->json(true, 'Reading…', ['pending' => true, 'token' => $q->token]);
        }
        if ($q->status === 'failed') {
            return $this->json(false, 'Anee could not read that just now. Please try again.', [], 503);
        }
        $agri = (bool) $q->isAgri;

        return $this->json(true, 'ok', [
            'token' => $agri ? $q->token : null,
            'agri' => $agri,
            'reply' => $this->said((string) $q->reply),
            'crop' => $q->detectedCrop,
            'cropLabel' => $q->detectedCrop ? self::plain(CropCatalog::label($q->detectedCrop)) : null,
        ]);
    }

    /** Step 2: the farm. */
    public function details(Request $request)
    {
        if ($refused = $this->guard($request, 'ask_details')) {
            return $refused;
        }
        $q = $this->byToken((string) $request->input('token'));
        if (! $q || ! in_array($q->status, ['details', 'ready'], true)) {
            return $this->json(false, 'That question has expired. Please ask it again.', [], 410);
        }
        $data = $request->validate([
            'farmSize' => 'required|numeric|min:0.01|max:1000000',
            'farmUnit' => 'required|in:ha,sqm',
            'crop' => 'required|string|max:40',
            'cropOther' => 'nullable|string|max:80',
            'country' => 'nullable|string|size:2',
            'province' => 'required|string|max:80',
            'town' => 'required|string|max:120',
        ], [
            'farmSize.required' => 'How big is the farm?',
            'crop.required' => 'Which crop is it?',
            'province.required' => 'Which province is the farm in?',
            'town.required' => 'Which town is the farm in?',
        ]);
        $crop = isset(CropCatalog::CROPS[$data['crop']]) ? $data['crop'] : null;
        $label = $crop ? self::plain(CropCatalog::label($crop)) : trim((string) ($data['cropOther'] ?? ''));
        if ($label === '') {
            return $this->json(false, 'Which crop is it?', ['errors' => ['crop' => ['Which crop is it?']]], 422);
        }
        $country = \App\Support\Region::valid($data['country'] ?? 'PH') ?: 'PH';
        $q->update([
            'farmSize' => round((float) $data['farmSize'], 2),
            'farmUnit' => $data['farmUnit'],
            'crop' => $crop,
            'cropLabel' => Str::limit($label, 118, ''),
            'country' => $country,
            'province' => Str::limit(trim(strip_tags($data['province'])), 78, ''),
            'town' => Str::limit(trim(strip_tags($data['town'])), 118, ''),
            'status' => 'ready',
        ]);

        return $this->json(true, 'ok', [
            'crop' => Str::before($label, ' ('),
            'place' => $q->placeWords(),
        ]);
    }

    /** Step 4: where the answer goes. The visitor becomes a lead here. */
    public function email(Request $request)
    {
        if ($refused = $this->guard($request, 'ask_email')) {
            return $refused;
        }
        $q = $this->byToken((string) $request->input('token'));
        if (! $q || ! in_array($q->status, ['ready', 'emailed'], true)) {
            return $this->json(false, 'That question has expired. Please ask it again.', [], 410);
        }
        $data = $request->validate(['email' => 'required|string|max:190|email:rfc,dns'], [
            'email.required' => 'Please type your email address.',
            'email.email' => 'That email address does not look right. Please check it.',
        ]);
        $email = mb_strtolower(trim($data['email']));
        $key = 'ask-e:' . sha1($email);
        if (RateLimiter::tooManyAttempts($key, 6)) {
            return $this->json(false, 'This address has asked a lot today. Make a free account and ask Anee as much as you like.', ['limited' => true], 429);
        }
        RateLimiter::hit($key, 86400);

        $q->update(['email' => $email]);
        $url = route('ask.answer', ['token' => $q->token]);
        $sent = app(MailService::class)->sendTemplate('ask_anee_answer', $email, '', [
            'question' => e(Str::limit($q->question, 400)),
            'crop' => e(Str::before((string) $q->cropLabel, ' (')),
            'farmSize' => e($q->farmWords()),
            'location' => e($q->placeWords()),
            'answerUrl' => $url,
            'signupUrl' => url('/signup') . '?utm_source=ask-anee&utm_medium=email&utm_campaign=answer',
            'siteName' => 'anee.io',
        ], ['relatedType' => 'ask_question', 'relatedId' => $q->id]);
        if (! $sent) {
            return $this->json(false, 'The email could not be sent just now. Please check the address and try again.', [], 502);
        }
        $q->update(['status' => 'emailed', 'emailedAt' => now()]);

        $payload = ['success' => true, 'message' => 'Sent.', 'data' => ['email' => $email]];
        $leads = fn () => app(AskAneeLeads::class)->capture($q->fresh(), $url);
        // The marketing side after the answer has gone: the visitor waits on nothing.
        if (function_exists('fastcgi_finish_request')) {
            response()->json($payload)->send();
            fastcgi_finish_request();
            $leads();
            exit;
        }
        $leads();

        return response()->json($payload);
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
