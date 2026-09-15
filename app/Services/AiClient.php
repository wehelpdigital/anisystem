<?php

namespace App\Services;

use App\Models\AiSetting;
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

    /**
     * @param  array<int, array{mime:string,data:string}>|array{mime:string,data:string}|null  $image
     *   One picture or several. A single one is still accepted as it always
     *   was, because every existing caller passes exactly that.
     */
    public function ask(AiSetting $settings, array $history, string $prompt, array|null $image = null, ?int $maxOut = null, array $opts = []): array
    {
        $key = $settings->plainApiKey();
        if (! $key) {
            return $this->fail('The AI is not configured yet. Please contact support.');
        }

        // Refused before the provider is paid: see MAX_PROMPT_TOKENS.
        $carried = (int) ceil((mb_strlen($prompt) + array_sum(array_map(fn ($t) => mb_strlen((string) ($t['text'] ?? '')), $history))) / 4);
        if ($carried > self::MAX_PROMPT_TOKENS) {
            logger()->warning('AI ask refused: prompt over the ceiling', ['tokens' => $carried]);

            return $this->fail('That is more than Anee can read in one go. Try a shorter span or fewer things at once.');
        }

        $search = (bool) ($opts['search'] ?? false);
        $this->timeout = max(30, min(600, (int) ($opts['timeout'] ?? self::TIMEOUT)));

        /* The settings row's cap is sized for a chat answer. A caller whose
         * answer is a document — the when-to-plant JSON — says so here, or
         * the reply is cut mid-object and reads as "unreadable". */
        try {
            $r = match ($settings->provider) {
                'openai' => $this->askOpenAi($settings, $key, $history, $prompt, self::pictures($image), $maxOut, $search),
                'gemini' => $this->askGemini($settings, $key, $history, $prompt, self::pictures($image), $maxOut, $search),
                default => $this->askClaude($settings, $key, $history, $prompt, self::pictures($image), $maxOut, $search),
            };
        } catch (\Throwable $e) {
            report($e);

            return $this->fail('The AI could not be reached. Please try again in a moment.');
        }

        return $r + ['searched' => false, 'sources' => []];
    }

    /**
     * Ask for a document -- a JSON object -- with the patience a job can
     * afford: one more try if the transport fails, one polite retry if the
     * answer is not the JSON asked for, and every token of every try summed,
     * because the house pays for all of them. `$parse` turns the text into
     * the array wanted, or null when it cannot.
     *
     * Returns ['ok', 'data', 'text', 'tokensIn', 'tokensOut', 'searched', 'sources', 'error'].
     */
    public function askForJson(AiSetting $settings, string $prompt, int $maxOut, callable $parse, array $opts = []): array
    {
        $sum = ['tokensIn' => 0, 'tokensOut' => 0, 'searched' => false, 'sources' => []];
        $fold = function (array $r) use (&$sum): void {
            $sum['tokensIn'] += (int) ($r['tokensIn'] ?? 0);
            $sum['tokensOut'] += (int) ($r['tokensOut'] ?? 0);
            $sum['searched'] = $sum['searched'] || (bool) ($r['searched'] ?? false);
            foreach ((array) ($r['sources'] ?? []) as $src) {
                if (! in_array($src, $sum['sources'], true)) {
                    $sum['sources'][] = $src;
                }
            }
        };

        // A document's own clock, longer when the web is read first.
        $opts += ['timeout' => ! empty($opts['search']) ? self::TIMEOUT_SEARCHED : self::TIMEOUT_DOCUMENT];
        $result = $this->ask($settings, [], $prompt, null, $maxOut, $opts);
        $fold($result);
        if (! ($result['ok'] ?? false)) {
            // A transport blip (the provider timing out once) gets a second try.
            sleep(3);
            $result = $this->ask($settings, [], $prompt, null, $maxOut, $opts);
            $fold($result);
        }
        if (! ($result['ok'] ?? false)) {
            return $sum + ['ok' => false, 'data' => null, 'text' => '',
                'error' => $result['error'] ?? 'The AI could not be reached. Nothing was charged.'];
        }

        $data = $parse((string) $result['text']);
        if ($data === null) {
            // History turns carry 'text', never 'content'. No search on the
            // retry: the reading is done, only the shape is wanted.
            $retry = $this->ask($settings, [
                ['role' => 'user', 'text' => $prompt],
                ['role' => 'assistant', 'text' => (string) $result['text']],
            ], 'That was not valid JSON. Return ONLY the JSON object described, with no fences and no commentary.', null, $maxOut, ['timeout' => $opts['timeout']]);
            $fold($retry);
            if ($retry['ok'] ?? false) {
                $data = $parse((string) $retry['text']);
            }
        }

        return $sum + ['ok' => $data !== null, 'data' => $data, 'text' => (string) $result['text'],
            'error' => $data === null ? 'The answer came back unreadable. Nothing was charged — please try again.' : null];
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
     */
    public function researchThenJson(AiSetting $settings, string $researchPrompt, string $jsonPrompt, int $maxOut, callable $parse): array
    {
        /* Whether the model searches is its own call, made per request; the
         * same brief is searched one time and answered from memory the next.
         * So an unsearched research is asked again, told plainly, and only
         * a second miss goes through as a reading from memory. */
        $research = ['ok' => false, 'text' => '', 'searched' => false];
        $spent = ['tokensIn' => 0, 'tokensOut' => 0];
        for ($try = 0; $try < 3; $try++) {
            $brief = $try === 0 ? $researchPrompt
                : "IMPORTANT: the previous attempt answered from memory without using the search tool. You MUST use the google_search / web search tool now — run the searches, read the pages, and write the notes from what they say.

" . $researchPrompt;
            $got = $this->ask($settings, [], $brief, null, 3000, ['search' => true, 'timeout' => self::TIMEOUT_SEARCHED]);
            $spent['tokensIn'] += (int) ($got['tokensIn'] ?? 0);
            $spent['tokensOut'] += (int) ($got['tokensOut'] ?? 0);
            if (($got['ok'] ?? false) && trim((string) $got['text']) !== '') {
                $research = $got;
                if ($got['searched'] ?? false) {
                    break;
                }
            } elseif (! ($got['ok'] ?? false)) {
                sleep(3);
            }
        }
        if (! ($research['searched'] ?? false)) {
            logger()->warning('AI research step: no web search was made', ['tries' => $try + 1, 'ok' => (bool) ($research['ok'] ?? false)]);
        }
        if (! ($research['ok'] ?? false) || trim((string) $research['text']) === '') {
            return $this->askForJson($settings, $jsonPrompt, $maxOut, $parse, ['search' => true]);
        }

        $notes = "\n\nRESEARCH NOTES — found on the web just now by a search step. Rely on these FIRST, over memory; where they and memory disagree, the notes win; where the notes are silent, say so in dataGaps:\n"
            . trim((string) $research['text']);
        $doc = $this->askForJson($settings, $jsonPrompt . $notes, $maxOut, $parse);
        $doc['tokensIn'] += $spent['tokensIn'];
        $doc['tokensOut'] += $spent['tokensOut'];
        $doc['searched'] = (bool) ($research['searched'] ?? false);
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

        return isset($image['mime']) ? [$image] : array_values(array_filter(
            $image,
            fn ($p) => is_array($p) && isset($p['mime'], $p['data'])
        ));
    }

    private function askClaude(AiSetting $s, string $key, array $history, string $prompt, array $images, ?int $maxOut = null, bool $search = false): array
    {
        $messages = [];
        foreach ($history as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => [['type' => 'text', 'text' => $turn['text']]]];
        }

        $content = [];
        foreach ($images as $image) {
            $content[] = [
                'type' => 'image',
                'source' => ['type' => 'base64', 'media_type' => $image['mime'], 'data' => $image['data']],
            ];
        }
        $content[] = ['type' => 'text', 'text' => $prompt];
        $messages[] = ['role' => 'user', 'content' => $content];

        $body = [
            'model' => $s->effectiveModel(),
            'max_tokens' => (int) ($maxOut ?? $s->maxOutputTokens),
            'temperature' => (float) $s->temperature,
            'system' => $s->instructions(),
            'messages' => $messages,
        ];
        if ($search) {
            $body['tools'] = [['type' => 'web_search_20250305', 'name' => 'web_search', 'max_uses' => self::SEARCH_MAX_USES]];
        }
        $res = Http::timeout($this->timeout)
            ->withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01'])
            ->post('https://api.anthropic.com/v1/messages', $body);

        if (! $res->successful()) {
            return $this->fail($this->providerError($res->json('error.message'), $res->status()));
        }

        $json = $res->json();
        $text = collect($json['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");
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
            'tokensIn' => (int) ($json['usage']['input_tokens'] ?? 0),
            'tokensOut' => (int) ($json['usage']['output_tokens'] ?? 0),
            'searched' => (int) ($json['usage']['server_tool_use']['web_search_requests'] ?? 0) > 0,
            'sources' => self::uniqueSources($sources),
            'error' => null,
        ];
    }

    /** @param  array<int, array{mime:string,data:string}>  $images */
    private function askOpenAi(AiSetting $s, string $key, array $history, string $prompt, array $images, ?int $maxOut = null, bool $search = false): array
    {
        $messages = [['role' => 'system', 'content' => $s->instructions()]];
        foreach ($history as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => $turn['text']];
        }

        $content = [['type' => 'text', 'text' => $prompt]];
        foreach ($images as $image) {
            $content[] = [
                'type' => 'image_url',
                'image_url' => ['url' => 'data:' . $image['mime'] . ';base64,' . $image['data']],
            ];
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
            'systemInstruction' => ['parts' => [['text' => $s->instructions()]]],
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
                    'maxOutputTokens' => (int) ($maxOut ?? $s->maxOutputTokens) + self::GEMINI_THINKING_BUDGET,
                    'temperature' => (float) $s->temperature,
                    'thinkingConfig' => ['thinkingBudget' => self::GEMINI_THINKING_BUDGET],
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

    /** Provider errors are for the operator; clients get something actionable. */
    private function providerError(?string $message, int $status): string
    {
        if ($message) {
            logger()->warning('AI provider error', ['status' => $status, 'message' => $message]);
        }

        return match (true) {
            $status === 401 || $status === 403 => 'The AI key was rejected. Please contact support.',
            $status === 429 => 'The AI is busy right now. Please try again in a minute.',
            $status >= 500 => 'The AI service is having trouble. Please try again shortly.',
            default => 'The AI could not answer that. Please try rephrasing your question.',
        };
    }

    private function fail(string $error): array
    {
        return ['ok' => false, 'text' => '', 'tokensIn' => 0, 'tokensOut' => 0, 'searched' => false, 'sources' => [], 'error' => $error];
    }
}
