<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Support\ClaudeFirst;
use Illuminate\Support\Facades\Http;

/**
 * One call shape over three providers. Each `ask()` returns:
 *
 *   ['ok' => bool, 'text' => string, 'tokensIn' => int, 'tokensOut' => int, 'error' => ?string,
 *    'searched' => bool, 'sources' => [['title' => string, 'url' => string], …]]
 *
 * History is passed as a plain list of ['role' => 'user'|'assistant', 'text' => string].
 * An image is passed as ['mime' => 'image/jpeg', 'data' => base64].
 * Options: ['search' => true] lets the model search the web for the answer
 * (Google Search grounding on Gemini, Anthropic's web search on Claude,
 * OpenAI's web search on its search models) and hands back what it read.
 */
class AiClient
{
    /**
     * How long one call may take. A chat answer is back well inside the
     * default; a document -- thousands of tokens of JSON after a thinking
     * budget -- is not, and a document written after a web search is
     * slower still (measured: a grounded Pro call cut off at ninety
     * seconds with nothing received). Callers say what they are asking
     * for through the 'timeout' option; askForJson picks these itself.
     */
    private const TIMEOUT = 90;
    public const TIMEOUT_DOCUMENT = 180;
    /* Measured: a grounded Pro call that has sent nothing after 150 s never
     * does; one that answers, answers in 60–90 s. So the searched clock is
     * short enough to ask again inside a job's own patience. */
    public const TIMEOUT_SEARCHED = 150;

    /** The per-call timeout, the caller's or the default. */
    private int $timeout = self::TIMEOUT;

    /* Claude answers as a stream (2026-10-27), so a long document is told
     * from a stall by silence, not by the clock: nothing heard (not even the
     * stream's pings) for CLAUDE_SILENCE seconds is a stall. A job that beats
     * its heart (onBeat) may let one answer run to CLAUDE_JOB_CAP, since the
     * beat keeps it alive while the words come; anything else keeps its own
     * clock. */
    private const CLAUDE_SILENCE = 120;
    private const CLAUDE_JOB_CAP = 420;

    /**
     * The most one ask may carry in, in tokens (~4 characters each): a
     * ceiling on what the house pays for a single question, and on what any
     * provider accepts. A season report of a large farm runs to twenty
     * thousand; a chat with history to a few thousand. Anything near this
     * is a runaway -- a prompt built from a table nobody bounded -- and is
     * refused before a peso is spent rather than paid for and then cut.
     */
    public const MAX_PROMPT_TOKENS = 120000;

    /** A web search costs the house a flat fee per grounded request on top of the tokens. */
    private const SEARCH_MAX_USES = 5;

    /**
     * How much silent reasoning a Gemini thinking model may spend per ask.
     * Bounded so a hard photo cannot think the wallet dry, roomy enough that
     * the diagnosis is done thinking before it starts talking.
     */
    private const GEMINI_THINKING_BUDGET = 2048;

    /** The system prompt of the ask in hand, when the caller brought one. */
    private ?string $system = null;

    /** The provider's own words and status for the last failed ask, for an operator's check (never shown to a farmer). */
    public ?array $lastProviderError = null;

    /**
     * @param  array<int, array{mime:string,data:string}>|array{mime:string,data:string}|null  $image
     *   One picture or several. A single one is still accepted as it always
     *   was, because every existing caller passes exactly that.
     */
    public function ask(AiSetting $settings, array $history, string $prompt, array|null $image = null, ?int $maxOut = null, array $opts = []): array
    {
        // Refused before the provider is paid: see MAX_PROMPT_TOKENS.
        $carried = (int) ceil((mb_strlen($prompt) + array_sum(array_map(fn ($t) => mb_strlen((string) ($t['text'] ?? '')), $history))) / 4);
        if ($carried > self::MAX_PROMPT_TOKENS) {
            logger()->warning('AI ask refused: prompt over the ceiling', ['tokens' => $carried]);

            return $this->fail('That is more than Anee can read in one go. Try a shorter span or fewer things at once.');
        }

        $search = (bool) ($opts['search'] ?? false);
        $this->timeout = max(30, min(600, (int) ($opts['timeout'] ?? self::TIMEOUT)));
        // A job that is not Anee talking (a receipt read for the payments
        // desk) brings its own system prompt: no persona, no farm rules.
        $this->system = isset($opts['system']) && trim((string) $opts['system']) !== '' ? (string) $opts['system'] : null;
        // A job's heartbeat (askForJson's and researchThenJson's onPhase, or the caller's own): a long Claude answer beats it as it comes.
        $beat = is_callable($opts['onBeat'] ?? null) ? $opts['onBeat'] : null;
        $pictures = self::pictures($image);

        /* The owner's Claude first (App\Support\ClaudeFirst), while its key
         * works and it is not resting; whatever it cannot answer, the
         * settings row's own model answers in the same ask. */
        $claude = ClaudeFirst::reads($pictures) && ! ClaudeFirst::resting() ? $settings->onClaude() : null;
        if ($claude) {
            $this->lastProviderError = null;
            $this->stalled = false;
            try {
                $r = $this->askClaude($claude, (string) $claude->plainApiKey(), $history, $prompt, $pictures, $maxOut, $search, $beat);
            } catch (\Throwable $e) {
                // Nothing heard for CLAUDE_SILENCE is a stall; the caller's clock running out on a long answer is not.
                $this->stalled = str_contains($e->getMessage(), 'too slow');
                logger()->warning('Claude first: no answer', ['error' => mb_substr($e->getMessage(), 0, 200)]);
                $r = $this->fail('The AI could not be reached. Please try again in a moment.');
            }
            if ($r['ok'] ?? false) {
                ClaudeFirst::answered((string) ($r['model'] ?? $claude->effectiveModel()));

                return $this->finish($r + ['provider' => 'claude', 'model' => $claude->effectiveModel()], $opts);
            }
            ClaudeFirst::failed($this->lastProviderError, $claude->effectiveModel(), $this->stalled);
            if ($beat) {
                // The Claude try may have used most of a job's clock: it is still alive.
                rescue($beat, null, false);
            }
        }

        $key = $settings->plainApiKey();
        if (! $key) {
            return $this->fail('The AI is not configured yet. Please contact support.');
        }

        /* The settings row's cap is sized for a chat answer. A caller whose
         * answer is a document — the when-to-plant JSON — says so here, or
         * the reply is cut mid-object and reads as "unreadable". */
        try {
            $r = match ($settings->provider) {
                'openai' => $this->askOpenAi($settings, $key, $history, $prompt, $pictures, $maxOut, $search),
                'gemini' => $this->askGemini($settings, $key, $history, $prompt, $pictures, $maxOut, $search),
                default => $this->askClaude($settings, $key, $history, $prompt, $pictures, $maxOut, $search, $beat),
            };
        } catch (\Throwable $e) {
            report($e);

            return $this->fail('The AI could not be reached. Please try again in a moment.');
        }

        return $this->finish($r + ['provider' => (string) $settings->provider, 'model' => $settings->effectiveModel()], $opts);
    }

    /** Whether the last Claude try heard nothing for CLAUDE_SILENCE (a stall, not a slow answer). */
    private bool $stalled = false;

    /** An answer as every caller takes it: the source lists filled in, and an agency's name taken out (GovSource). */
    private function finish(array $r, array $opts): array
    {
        $r += ['searched' => false, 'sources' => []];
        /* Anee's own words never give a government body as her source (the
         * owner, 2026-10-17): the prompt says so (GovSource::RULE), and what
         * slips through is taken out here, with the pages she read. A job
         * with its own system prompt (a receipt, a news sort) is left alone,
         * and so is one that reports the news ('keepAgencies'). */
        if ($this->system === null && empty($opts['keepAgencies']) && ($r['ok'] ?? false)) {
            $r['text'] = \App\Support\GovSource::scrub((string) ($r['text'] ?? ''));
            $r['sources'] = \App\Support\GovSource::keep((array) $r['sources']);
        }

        return $r;
    }

    /**
     * Ask for a document -- a JSON object -- with the patience a job can
     * afford: one more try if the transport fails, one polite retry if the
     * answer is not the JSON asked for, and every token of every try summed,
     * because the house pays for all of them. `$parse` turns the text into
     * the array wanted, or null when it cannot.
     *
     * `$opts['retryAfterTimeout'] = false` keeps the transport retry for a
     * quick failure only: a try that used up most of its clock is not asked
     * again (a job with a long clock would otherwise sit through two).
     *
     * `$opts['onPhase']` is called before each call the model is made --
     * ('document'), ('document', 2) for the transport retry, ('document-json')
     * for the shape retry -- so a job can beat its heart in the database
     * and a watcher can tell "still working" from "died".
     *
     * Returns ['ok', 'data', 'text', 'tokensIn', 'tokensOut', 'searched', 'sources', 'error'].
     */
    public function askForJson(AiSetting $settings, string $prompt, int $maxOut, callable $parse, array $opts = []): array
    {
        // A JSON document is a report: written, not spoken (see AiSetting::forDocument).
        $settings = $settings->forDocument();
        $onPhase = is_callable($opts['onPhase'] ?? null) ? $opts['onPhase'] : null;
        // A picture to read with the document (2026-10-14: the LCC Checker's leaves).
        $image = $opts['image'] ?? null;
        $retryAfterTimeout = (bool) ($opts['retryAfterTimeout'] ?? true);
        unset($opts['onPhase'], $opts['image'], $opts['retryAfterTimeout']);
        $sum = ['tokensIn' => 0, 'tokensOut' => 0, 'tokensCached' => 0, 'searched' => false, 'sources' => []];
        $fold = function (array $r) use (&$sum): void {
            $sum['tokensIn'] += (int) ($r['tokensIn'] ?? 0);
            $sum['tokensOut'] += (int) ($r['tokensOut'] ?? 0);
            $sum['tokensCached'] += (int) ($r['tokensCached'] ?? 0);
            $sum['searched'] = $sum['searched'] || (bool) ($r['searched'] ?? false);
            // Who answered (Claude first, or the settings row's model): the usage ledger records it.
            if (! empty($r['ok']) && ! empty($r['provider'])) {
                $sum['provider'] = (string) $r['provider'];
                $sum['model'] = (string) ($r['model'] ?? '');
            }
            foreach ((array) ($r['sources'] ?? []) as $src) {
                if (! in_array($src, $sum['sources'], true)) {
                    $sum['sources'][] = $src;
                }
            }
        };

        // A document's own clock, longer when the web is read first.
        $opts += ['timeout' => ! empty($opts['search']) ? self::TIMEOUT_SEARCHED : self::TIMEOUT_DOCUMENT];
        if ($onPhase) {
            $onPhase('document', 1);
        }
        $began = microtime(true);
        $result = $this->ask($settings, [], $prompt, $image, $maxOut, $opts + ['onBeat' => $onPhase ? fn () => $onPhase('document', 1) : null]);
        $fold($result);
        $timedOut = (microtime(true) - $began) > 0.8 * (int) $opts['timeout'];
        if (! ($result['ok'] ?? false) && ($retryAfterTimeout || ! $timedOut)) {
            // A transport blip (the provider timing out once) gets a second try.
            sleep(3);
            if ($onPhase) {
                $onPhase('document', 2);
            }
            $result = $this->ask($settings, [], $prompt, $image, $maxOut, $opts + ['onBeat' => $onPhase ? fn () => $onPhase('document', 2) : null]);
            $fold($result);
        }
        if (! ($result['ok'] ?? false)) {
            return $sum + ['ok' => false, 'data' => null, 'text' => '', 'timedOut' => $timedOut,
                'error' => $result['error'] ?? 'The AI could not be reached. Nothing was charged.'];
        }

        $data = $parse((string) $result['text']);
        if ($data === null) {
            // History turns carry 'text', never 'content'. No search on the
            // retry: the reading is done, only the shape is wanted.
            if ($onPhase) {
                $onPhase('document-json', 1);
            }
            $retry = $this->ask($settings, [
                ['role' => 'user', 'text' => $prompt],
                ['role' => 'assistant', 'text' => (string) $result['text']],
            ], 'That was not valid JSON. Return ONLY the JSON object described, with no fences and no commentary.', null, $maxOut, ['timeout' => $opts['timeout'], 'keepAgencies' => ! empty($opts['keepAgencies']),
                // A job with its own system prompt (a news sort) keeps it on the retry, not Anee's whole persona (2026-10-19).
                'system' => $opts['system'] ?? null, 'onBeat' => $onPhase ? fn () => $onPhase('document-json', 1) : null]);
            $fold($retry);
            if ($retry['ok'] ?? false) {
                $data = $parse((string) $retry['text']);
            }
        }

        return $sum + ['ok' => $data !== null, 'data' => $data, 'text' => (string) $result['text'],
            'error' => $data === null ? 'Anee\'s answer could not be read. Nothing was charged. Please try again.' : null];
    }

    /**
     * Research first, then the document.
     *
     * Asked to search the web AND answer in nothing but a JSON object, the
     * model searches nowhere (measured: two grounded runs, no query made).
     * Asked to research and write notes, it searches every time. So the
     * two are separate asks: the research brief, with the web open, comes
     * back as prose with the pages it read; the document brief is then
     * asked with those notes appended and no search. Everything the two
     * spent is summed; the sources are the research's.
     *
     * Should the research fail twice, the document is asked on its own
     * with the web open -- a reading from memory beats no reading, and the
     * answer says which it got ('searched').
     *
     * Since 2026-10-18 (the owner: fewer tokens "in the long run"):
     *  - $shelf = ['kind' => ..., 'scope' => ...] keeps searched notes in
     *    App\Support\ResearchShelf under the brief's fingerprint, and a later
     *    run with the very same brief reads them instead of searching. Only a
     *    brief written from what is not the farmer's own (the crop, the
     *    variety, the province, the month) should ask for it.
     *  - An empty research brief means there is nothing left to look up (the
     *    place's record is kept, the variety is on record): the document is
     *    asked alone, without the web.
     *  - $jsonPrompt may be a closure that takes the notes and returns the
     *    whole document brief, for a caller that keeps part of the notes
     *    (App\Support\ClimateRecord) before the document is written.
     * 'searched' still says the notes came from the web; 'paidSearch' says
     * whether this run paid for a search (false on a kept brief).
     */
    public function researchThenJson(AiSetting $settings, string $researchPrompt, string|\Closure $jsonPrompt, int $maxOut, callable $parse, ?callable $onPhase = null, int $notesMaxOut = 3000, array $shelf = []): array
    {
        // The notes and the document are both written work, not a chat turn.
        $settings = $settings->forDocument();
        $docOf = fn (string $notes, array $meta = []) => $jsonPrompt instanceof \Closure ? (string) $jsonPrompt($notes, $meta)
            : $jsonPrompt . ($notes !== '' ? "\n\nRESEARCH NOTES — found on the web just now by a search step. Rely on these FIRST, over memory; where they and memory disagree, the notes win; where the notes are silent, say so in dataGaps:\n" . $notes : '');
        if (trim($researchPrompt) === '') {
            $doc = $this->askForJson($settings, $docOf(''), $maxOut, $parse, ['onPhase' => $onPhase]);

            return $doc + ['paidSearch' => false, 'shelf' => false, 'researchText' => ''];
        }
        $kept = ! empty($shelf['kind']) ? \App\Support\ResearchShelf::get((string) $shelf['kind'], $researchPrompt) : null;
        if ($kept) {
            if ($onPhase) {
                $onPhase('research', 1);
            }
            $doc = $this->askForJson($settings, $docOf(trim($kept['notes']), ['searched' => true, 'sources' => (array) $kept['sources'], 'shelf' => true]), $maxOut, $parse, ['onPhase' => $onPhase]);
            $doc['searched'] = true;
            // A brief kept before 2026-10-17 may still list an agency's page (GovSource).
            $doc['sources'] = \App\Support\GovSource::keep((array) $kept['sources']);
            $doc['researchText'] = (string) $kept['notes'];

            return $doc + ['paidSearch' => false, 'shelf' => true, 'shelfId' => $kept['id']];
        }
        /* Whether the model searches is its own call, made per request; the
         * same brief is searched one time and answered from memory the next.
         * So an unsearched research is asked again, told plainly, and only
         * a second miss goes through as a reading from memory. A research
         * call that never answers (the provider stalling until the clock
         * runs out) is tried once more, not twice: three stalls would be
         * seven and a half minutes of nothing. */
        $research = ['ok' => false, 'text' => '', 'searched' => false];
        $spent = ['tokensIn' => 0, 'tokensOut' => 0, 'tokensCached' => 0];
        $stalls = 0;
        for ($try = 0; $try < 3; $try++) {
            $brief = $try === 0 ? $researchPrompt
                : "IMPORTANT: the previous attempt answered from memory without using the search tool. You MUST use the google_search / web search tool now — run the searches, read the pages, and write the notes from what they say.

" . $researchPrompt;
            if ($onPhase) {
                $onPhase('research', $try + 1);
            }
            $got = $this->ask($settings, [], $brief, null, $notesMaxOut, ['search' => true, 'timeout' => self::TIMEOUT_SEARCHED,
                'onBeat' => $onPhase ? fn () => $onPhase('research', $try + 1) : null]);
            $spent['tokensIn'] += (int) ($got['tokensIn'] ?? 0);
            $spent['tokensOut'] += (int) ($got['tokensOut'] ?? 0);
            $spent['tokensCached'] += (int) ($got['tokensCached'] ?? 0);
            if (($got['ok'] ?? false) && trim((string) $got['text']) !== '') {
                $research = $got;
                if ($got['searched'] ?? false) {
                    break;
                }
            } elseif (! ($got['ok'] ?? false)) {
                if (++$stalls >= 2) {
                    break;
                }
                sleep(3);
            }
        }
        if (! ($research['searched'] ?? false)) {
            logger()->warning('AI research step: no web search was made', ['tries' => $try + 1, 'ok' => (bool) ($research['ok'] ?? false)]);
        }
        if (! ($research['ok'] ?? false) || trim((string) $research['text']) === '') {
            $doc = $this->askForJson($settings, $docOf(''), $maxOut, $parse, ['search' => true, 'onPhase' => $onPhase]);

            return $doc + ['paidSearch' => (bool) ($doc['searched'] ?? false) || $spent['tokensIn'] > 0, 'shelf' => false, 'researchText' => ''];
        }
        if (! empty($shelf['kind']) && ($research['searched'] ?? false)) {
            \App\Support\ResearchShelf::put((string) $shelf['kind'], $researchPrompt, (string) ($shelf['scope'] ?? $shelf['kind']), array_merge($research, $spent));
        }

        $doc = $this->askForJson($settings, $docOf(trim((string) $research['text']), ['searched' => (bool) ($research['searched'] ?? false), 'sources' => (array) ($research['sources'] ?? [])]), $maxOut, $parse, ['onPhase' => $onPhase]);
        $doc['tokensIn'] += $spent['tokensIn'];
        $doc['tokensOut'] += $spent['tokensOut'];
        $doc['tokensCached'] = (int) ($doc['tokensCached'] ?? 0) + $spent['tokensCached'];
        $doc['searched'] = (bool) ($research['searched'] ?? false);
        $doc['paidSearch'] = true;
        $doc['shelf'] = false;
        $doc['sources'] = (array) ($research['sources'] ?? []);
        $doc['researchText'] = (string) $research['text'];

        return $doc;
    }

    // ------------------------------------------------------------------

    /** @param  array<int, array{mime:string,data:string}>  $images */
    /**
     * One picture, several, or none — always returned as a list.
     *
     * Callers have always passed a single ['mime'=>…, 'data'=>…]; a question
     * about four photos of the same leaf is a different question from four
     * questions about one photo each, so the shape had to widen. Telling the
     * two apart by looking for the keys is safe: a list of pictures never has
     * a 'mime' key of its own.
     *
     * @return array<int, array{mime:string,data:string}>
     */
    private static function pictures(array|null $image): array
    {
        if (! $image) {
            return [];
        }

        $list = isset($image['mime']) ? [$image] : array_values(array_filter(
            $image,
            fn ($p) => is_array($p) && isset($p['mime'], $p['data'])
        ));

        // Every picture at a size the model takes (App\Support\ModelImage).
        return array_map(fn ($p) => \App\Support\ModelImage::fitEncoded($p), $list);
    }

    private function askClaude(AiSetting $s, string $key, array $history, string $prompt, array $images, ?int $maxOut = null, bool $search = false, ?callable $beat = null): array
    {
        $messages = [];
        foreach ($history as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => [['type' => 'text', 'text' => $turn['text']]]];
        }

        $content = [];
        foreach ($images as $image) {
            $content[] = [
                // A PDF (a receipt sent as a document) is a document block, not an image.
                'type' => $image['mime'] === 'application/pdf' ? 'document' : 'image',
                'source' => ['type' => 'base64', 'media_type' => $image['mime'], 'data' => $image['data']],
            ];
        }
        $content[] = ['type' => 'text', 'text' => $prompt];
        $messages[] = ['role' => 'user', 'content' => $content];

        /* The Claude models of 2026 (2026-10-27): no sampling settings (a temperature is refused with a 400), thinking
         * that is always on and paced by effort instead, and room in max_tokens for it; a decline on safety grounds
         * re-run by the API on the model it picks (server-side fallback). Older models keep the old request. */
        $model = $s->effectiveModel();
        $current = (bool) preg_match('/^claude-(opus-(4-[678]|5)|sonnet-5|fable-5|haiku-5)/', $model);
        $answer = (int) ($maxOut ?? $s->maxOutputTokens);
        $body = [
            'model' => $model,
            // A document thinks longer before it writes than a chat answer does.
            'max_tokens' => $answer + ($current ? ($answer >= 4000 ? 8192 : 4096) : 0),
            'system' => $this->system ?? $s->instructions(),
            'messages' => $messages,
            'stream' => true,
        ];
        if (! $current) {
            $body['temperature'] = (float) $s->temperature;
        } elseif ($s->thinkingLevel) {
            $body['output_config'] = ['effort' => in_array($s->thinkingLevel, ['minimal', 'low'], true) ? 'low' : 'medium'];
        }
        $headers = ['x-api-key' => $key, 'anthropic-version' => '2023-06-01'];
        if (preg_match('/^claude-(opus-5|fable-5-1|sonnet-5-5)/', $model)) {
            $body['fallbacks'] = 'default';
            $headers['anthropic-beta'] = 'server-side-fallback-2026-07-01';
        }
        if ($search) {
            $body['tools'] = [['type' => $current && ! str_starts_with($model, 'claude-haiku') ? 'web_search_20260209' : 'web_search_20250305', 'name' => 'web_search', 'max_uses' => self::SEARCH_MAX_USES]];
        }
        // Curl stops a stream that has been silent for CLAUDE_SILENCE; a job's heart beats every twenty seconds meanwhile.
        $options = ['curl' => [CURLOPT_LOW_SPEED_LIMIT => 1, CURLOPT_LOW_SPEED_TIME => self::CLAUDE_SILENCE]];
        if ($beat) {
            $lastBeat = microtime(true);
            $options['progress'] = function () use (&$lastBeat, $beat): void {
                if (microtime(true) - $lastBeat >= 20) {
                    $lastBeat = microtime(true);
                    rescue($beat, null, false);
                }
            };
        }
        $res = Http::timeout($beat ? max($this->timeout, self::CLAUDE_JOB_CAP) : $this->timeout)
            ->connectTimeout(20)
            ->withOptions($options)
            ->withHeaders($headers)
            ->post('https://api.anthropic.com/v1/messages', $body);

        if (! $res->successful()) {
            return $this->fail($this->providerError($res->json('error.message'), $res->status()));
        }

        $json = self::unstream($res->body());
        if (! empty($json['error'])) {
            // Trouble after the stream began (overloaded, mostly): the provider's words, as for any failed ask.
            return $this->fail($this->providerError((string) ($json['error']['message'] ?? ''), ($json['error']['type'] ?? '') === 'overloaded_error' ? 529 : 500));
        }
        if (($json['stop_reason'] ?? '') === 'refusal') {
            logger()->warning('Claude declined', ['model' => $model, 'category' => $json['stop_details']['category'] ?? null]);
            $this->lastProviderError = ['status' => 200, 'message' => 'refusal: ' . ($json['stop_details']['category'] ?? 'none')];

            return $this->fail('The AI declined to answer that.');
        }
        // Text blocks in a row are one passage cut around its citations; a search between two starts a new one.
        $text = '';
        $gap = false;
        foreach ((array) ($json['content'] ?? []) as $block) {
            if (($block['type'] ?? '') !== 'text') {
                $gap = $text !== '';

                continue;
            }
            $text .= ($gap ? "\n\n" : '') . (string) ($block['text'] ?? '');
            $gap = false;
        }
        if (in_array($json['stop_reason'] ?? '', ['max_tokens', 'pause_turn'], true)) {
            logger()->warning('Claude stopped early', ['model' => $model, 'stop' => $json['stop_reason'], 'out' => $json['usage']['output_tokens'] ?? null]);
            if (trim($text) === '') {
                $this->lastProviderError = ['status' => 200, 'message' => 'stopped early: ' . $json['stop_reason']];

                return $this->fail('The AI could not answer that. Please try again.');
            }
        }
        // What it read: every page the search tool handed back.
        $sources = [];
        foreach ((array) ($json['content'] ?? []) as $block) {
            if (($block['type'] ?? '') !== 'web_search_tool_result') {
                continue;
            }
            foreach ((array) ($block['content'] ?? []) as $hit) {
                if (! empty($hit['url'])) {
                    $sources[] = ['title' => (string) ($hit['title'] ?? ''), 'url' => (string) $hit['url']];
                }
            }
        }

        return [
            'ok' => true,
            'text' => trim($text),
            // Every token read, the cached ones too (as Gemini counts them); the cached ones apart.
            'tokensIn' => (int) ($json['usage']['input_tokens'] ?? 0) + (int) ($json['usage']['cache_read_input_tokens'] ?? 0)
                + (int) ($json['usage']['cache_creation_input_tokens'] ?? 0),
            'tokensOut' => (int) ($json['usage']['output_tokens'] ?? 0),
            'tokensCached' => (int) ($json['usage']['cache_read_input_tokens'] ?? 0),
            'searched' => (int) ($json['usage']['server_tool_use']['web_search_requests'] ?? 0) > 0,
            'sources' => self::uniqueSources($sources),
            // The model that wrote it: a decline re-run by the API (fallbacks) names the one that answered.
            'model' => (string) ($json['model'] ?? $model),
            'error' => null,
        ];
    }

    /**
     * A streamed answer read back into the shape of a whole one: the content
     * blocks with their text, the stop reason, the usage (the closing count
     * over the opening one), the model, and an error the stream itself sent.
     */
    private static function unstream(string $raw): array
    {
        $msg = ['content' => [], 'stop_reason' => null, 'usage' => []];
        foreach (preg_split("/\r?\n\r?\n/", $raw) as $event) {
            $data = '';
            foreach (preg_split("/\r?\n/", $event) as $line) {
                if (str_starts_with($line, 'data:')) {
                    $data .= ltrim(substr($line, 5));
                }
            }
            $e = $data !== '' ? json_decode($data, true) : null;
            if (! is_array($e)) {
                continue;
            }
            $i = (int) ($e['index'] ?? 0);
            switch ($e['type'] ?? '') {
                case 'message_start':
                    $msg['model'] = $e['message']['model'] ?? null;
                    $msg['usage'] = (array) ($e['message']['usage'] ?? []);
                    break;
                case 'content_block_start':
                    $msg['content'][$i] = (array) ($e['content_block'] ?? []);
                    break;
                case 'content_block_delta':
                    if (($e['delta']['type'] ?? '') === 'text_delta') {
                        $msg['content'][$i]['text'] = ($msg['content'][$i]['text'] ?? '') . (string) ($e['delta']['text'] ?? '');
                    }
                    break;
                case 'message_delta':
                    $msg['stop_reason'] = $e['delta']['stop_reason'] ?? $msg['stop_reason'];
                    if (isset($e['delta']['stop_details'])) {
                        $msg['stop_details'] = $e['delta']['stop_details'];
                    }
                    foreach ((array) ($e['usage'] ?? []) as $k => $v) {
                        if ($v !== null) {
                            $msg['usage'][$k] = $v;
                        }
                    }
                    break;
                case 'error':
                    $msg['error'] = (array) ($e['error'] ?? []);
                    break;
            }
        }
        ksort($msg['content']);
        $msg['content'] = array_values($msg['content']);

        return $msg;
    }

    /** @param  array<int, array{mime:string,data:string}>  $images */
    private function askOpenAi(AiSetting $s, string $key, array $history, string $prompt, array $images, ?int $maxOut = null, bool $search = false): array
    {
        $messages = [['role' => 'system', 'content' => $this->system ?? $s->instructions()]];
        foreach ($history as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => $turn['text']];
        }

        $content = [['type' => 'text', 'text' => $prompt]];
        foreach ($images as $image) {
            $content[] = $image['mime'] === 'application/pdf'
                ? ['type' => 'file', 'file' => ['filename' => 'document.pdf', 'file_data' => 'data:application/pdf;base64,' . $image['data']]]
                : ['type' => 'image_url', 'image_url' => ['url' => 'data:' . $image['mime'] . ';base64,' . $image['data']]];
        }
        $messages[] = ['role' => 'user', 'content' => $content];

        $body = [
            'model' => $s->effectiveModel(),
            'max_tokens' => (int) ($maxOut ?? $s->maxOutputTokens),
            'temperature' => (float) $s->temperature,
            'messages' => $messages,
        ];
        $post = fn (array $b) => Http::timeout($this->timeout)->withToken($key)->post('https://api.openai.com/v1/chat/completions', $b);
        $searched = false;
        if ($search) {
            // Only the search models take this; any other answers 400, and
            // the question is then asked plainly rather than not at all.
            $withSearch = $body + ['web_search_options' => new \stdClass];
            unset($withSearch['temperature']);
            $res = $post($withSearch);
            $searched = $res->successful();
            if (! $searched && $res->status() !== 400) {
                return $this->fail($this->providerError($res->json('error.message'), $res->status()));
            }
        }
        if (! $searched) {
            $res = $post($body);
        }

        if (! $res->successful()) {
            return $this->fail($this->providerError($res->json('error.message'), $res->status()));
        }

        $json = $res->json();
        $sources = [];
        foreach ((array) ($json['choices'][0]['message']['annotations'] ?? []) as $a) {
            if (! empty($a['url_citation']['url'])) {
                $sources[] = ['title' => (string) ($a['url_citation']['title'] ?? ''), 'url' => (string) $a['url_citation']['url']];
            }
        }

        return [
            'ok' => true,
            'text' => trim((string) ($json['choices'][0]['message']['content'] ?? '')),
            'tokensIn' => (int) ($json['usage']['prompt_tokens'] ?? 0),
            'tokensOut' => (int) ($json['usage']['completion_tokens'] ?? 0),
            'tokensCached' => (int) ($json['usage']['prompt_tokens_details']['cached_tokens'] ?? 0),
            'searched' => $searched,
            'sources' => self::uniqueSources($sources),
            'error' => null,
        ];
    }

    /** @param  array<int, array{mime:string,data:string}>  $images */
    private function askGemini(AiSetting $s, string $key, array $history, string $prompt, array $images, ?int $maxOut = null, bool $search = false): array
    {
        $contents = [];
        foreach ($history as $turn) {
            $contents[] = [
                // Gemini calls the assistant "model".
                'role' => $turn['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $turn['text']]],
            ];
        }

        $parts = [];
        foreach ($images as $image) {
            $parts[] = ['inline_data' => ['mime_type' => $image['mime'], 'data' => $image['data']]];
        }
        $parts[] = ['text' => $prompt];
        $contents[] = ['role' => 'user', 'parts' => $parts];

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($s->effectiveModel()) . ':generateContent';

        $body = [
            'systemInstruction' => ['parts' => [['text' => $this->system ?? $s->instructions()]]],
            'contents' => $contents,
        ];
        if ($search) {
            // Google Search grounding: the model searches, reads, and cites.
            $body['tools'] = [['google_search' => new \stdClass]];
        }
        $res = Http::timeout($this->timeout)
            ->withHeaders(['x-goog-api-key' => $key])
            ->post($url, $body + [
                'generationConfig' => [
                    /* Thinking models charge their reasoning against this cap,
                     * and a photo makes them reason hard: measured, a grass
                     * ID spent 1152 thought tokens against a 1200 cap and the
                     * farmer got a 44-token stub cut mid-sentence. So the
                     * thoughts get their own bounded lane, and the settings
                     * row's number stays what it reads as: the ANSWER budget. */
                    'maxOutputTokens' => (int) ($maxOut ?? $s->maxOutputTokens) + ($s->thinkingLevel ? 1024 : self::GEMINI_THINKING_BUDGET),
                    'temperature' => (float) $s->temperature,
                    // A cheap check asks for the least thinking by level (AiSetting::cheap()).
                    'thinkingConfig' => $s->thinkingLevel ? ['thinkingLevel' => $s->thinkingLevel] : ['thinkingBudget' => self::GEMINI_THINKING_BUDGET],
                ],
            ]);

        if (! $res->successful()) {
            return $this->fail($this->providerError($res->json('error.message'), $res->status()));
        }

        $json = $res->json();
        // Never the thought parts — some models return their reasoning as
        // parts flagged `thought`, and reasoning read aloud is not an answer.
        $text = collect($json['candidates'][0]['content']['parts'] ?? [])
            ->reject(fn ($p) => ! empty($p['thought']))
            ->pluck('text')->filter()->implode("\n");
        // What it read, when it searched: the grounding chunks' pages.
        $grounding = $json['candidates'][0]['groundingMetadata'] ?? [];
        $sources = [];
        foreach ((array) ($grounding['groundingChunks'] ?? []) as $chunk) {
            if (! empty($chunk['web']['uri'])) {
                $sources[] = ['title' => (string) ($chunk['web']['title'] ?? ''), 'url' => (string) $chunk['web']['uri']];
            }
        }

        return [
            'ok' => true,
            'text' => trim($text),
            'tokensIn' => (int) ($json['usageMetadata']['promptTokenCount'] ?? 0),
            // Of those, the ones Gemini's implicit prefix cache served (billed at a fraction): the
            // fixed text first in a prompt is what lets it apply (2026-10-19, measured in as_ai_usage).
            'tokensCached' => (int) ($json['usageMetadata']['cachedContentTokenCount'] ?? 0),
            // Thoughts are billed output at Google even though they are not
            // candidate text — the ledger counts what the house actually pays.
            'tokensOut' => (int) ($json['usageMetadata']['candidatesTokenCount'] ?? 0)
                + (int) ($json['usageMetadata']['thoughtsTokenCount'] ?? 0),
            'searched' => $search && ! empty($grounding['webSearchQueries']),
            'sources' => self::uniqueSources($sources),
            'error' => null,
        ];
    }

    /** Each page once, in the order first met, a dozen at most. */
    private static function uniqueSources(array $sources): array
    {
        $seen = [];
        $out = [];
        foreach ($sources as $src) {
            $u = (string) ($src['url'] ?? '');
            if ($u === '' || isset($seen[$u])) {
                continue;
            }
            $seen[$u] = true;
            $out[] = ['title' => mb_substr((string) ($src['title'] ?? ''), 0, 160), 'url' => mb_substr($u, 0, 500)];
            if (count($out) >= 12) {
                break;
            }
        }

        return $out;
    }

    // ------------------------------------------------------------------

    /** Whether a provider's words say its account has run out of prepaid credit. */
    public static function outOfCredit(?string $message): bool
    {
        return (bool) preg_match('/credit balance is too low|prepayment credits are depleted|insufficient.{0,20}(credit|quota|balance)/i', (string) $message);
    }

    /** Provider errors are for the operator; clients get something actionable. */
    private function providerError(?string $message, int $status): string
    {
        $this->lastProviderError = ['status' => $status, 'message' => mb_substr((string) $message, 0, 300)];
        if ($message) {
            // 402: the provider account's prepaid balance is used up, and every ask fails until it is topped up.
            logger()->{$status === 402 || self::outOfCredit($message) ? 'error' : 'warning'}('AI provider error', ['status' => $status, 'message' => $message]);
        }

        return match (true) {
            $status === 401 || $status === 403 => 'The AI key was rejected. Please contact support.',
            // Anthropic says an empty prepaid balance with a 400 ("Your credit balance is too low"), Gemini with a 402.
            $status === 402 || self::outOfCredit($message) => 'Anee is not available right now because of a problem on our side. Nothing was charged. Please try again later.',
            $status === 429 => 'The AI is busy right now. Please try again in a minute.',
            $status >= 500 => 'The AI service is having trouble. Please try again shortly.',
            default => 'The AI could not answer that. Please try rephrasing your question.',
        };
    }

    private function fail(string $error): array
    {
        return ['ok' => false, 'text' => '', 'tokensIn' => 0, 'tokensOut' => 0, 'tokensCached' => 0, 'searched' => false, 'sources' => [], 'error' => $error];
    }
}
