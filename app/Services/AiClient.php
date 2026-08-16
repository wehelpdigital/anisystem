<?php

namespace App\Services;

use App\Models\AiSetting;
use Illuminate\Support\Facades\Http;

/**
 * One call shape over three providers. Each `ask()` returns:
 *
 *   ['ok' => bool, 'text' => string, 'tokensIn' => int, 'tokensOut' => int, 'error' => ?string]
 *
 * History is passed as a plain list of ['role' => 'user'|'assistant', 'text' => string].
 * An image is passed as ['mime' => 'image/jpeg', 'data' => base64].
 */
class AiClient
{
    private const TIMEOUT = 90;

    /**
     * @param  array<int, array{mime:string,data:string}>|array{mime:string,data:string}|null  $image
     *   One picture or several. A single one is still accepted as it always
     *   was, because every existing caller passes exactly that.
     */
    public function ask(AiSetting $settings, array $history, string $prompt, array|null $image = null): array
    {
        $key = $settings->plainApiKey();
        if (! $key) {
            return $this->fail('The AI is not configured yet. Please contact support.');
        }

        try {
            return match ($settings->provider) {
                'openai' => $this->askOpenAi($settings, $key, $history, $prompt, self::pictures($image)),
                'gemini' => $this->askGemini($settings, $key, $history, $prompt, self::pictures($image)),
                default => $this->askClaude($settings, $key, $history, $prompt, self::pictures($image)),
            };
        } catch (\Throwable $e) {
            report($e);

            return $this->fail('The AI could not be reached. Please try again in a moment.');
        }
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

    private function askClaude(AiSetting $s, string $key, array $history, string $prompt, array $images): array
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

        $res = Http::timeout(self::TIMEOUT)
            ->withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01'])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $s->effectiveModel(),
                'max_tokens' => (int) $s->maxOutputTokens,
                'temperature' => (float) $s->temperature,
                'system' => $s->systemPrompt,
                'messages' => $messages,
            ]);

        if (! $res->successful()) {
            return $this->fail($this->providerError($res->json('error.message'), $res->status()));
        }

        $json = $res->json();
        $text = collect($json['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");

        return [
            'ok' => true,
            'text' => trim($text),
            'tokensIn' => (int) ($json['usage']['input_tokens'] ?? 0),
            'tokensOut' => (int) ($json['usage']['output_tokens'] ?? 0),
            'error' => null,
        ];
    }

    /** @param  array<int, array{mime:string,data:string}>  $images */
    private function askOpenAi(AiSetting $s, string $key, array $history, string $prompt, array $images): array
    {
        $messages = [['role' => 'system', 'content' => $s->systemPrompt]];
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

        $res = Http::timeout(self::TIMEOUT)
            ->withToken($key)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $s->effectiveModel(),
                'max_tokens' => (int) $s->maxOutputTokens,
                'temperature' => (float) $s->temperature,
                'messages' => $messages,
            ]);

        if (! $res->successful()) {
            return $this->fail($this->providerError($res->json('error.message'), $res->status()));
        }

        $json = $res->json();

        return [
            'ok' => true,
            'text' => trim((string) ($json['choices'][0]['message']['content'] ?? '')),
            'tokensIn' => (int) ($json['usage']['prompt_tokens'] ?? 0),
            'tokensOut' => (int) ($json['usage']['completion_tokens'] ?? 0),
            'error' => null,
        ];
    }

    /** @param  array<int, array{mime:string,data:string}>  $images */
    private function askGemini(AiSetting $s, string $key, array $history, string $prompt, array $images): array
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

        $res = Http::timeout(self::TIMEOUT)
            ->withHeaders(['x-goog-api-key' => $key])
            ->post($url, [
                'systemInstruction' => ['parts' => [['text' => $s->systemPrompt]]],
                'contents' => $contents,
                'generationConfig' => [
                    'maxOutputTokens' => (int) $s->maxOutputTokens,
                    'temperature' => (float) $s->temperature,
                ],
            ]);

        if (! $res->successful()) {
            return $this->fail($this->providerError($res->json('error.message'), $res->status()));
        }

        $json = $res->json();
        $text = collect($json['candidates'][0]['content']['parts'] ?? [])->pluck('text')->implode("\n");

        return [
            'ok' => true,
            'text' => trim($text),
            'tokensIn' => (int) ($json['usageMetadata']['promptTokenCount'] ?? 0),
            'tokensOut' => (int) ($json['usageMetadata']['candidatesTokenCount'] ?? 0),
            'error' => null,
        ];
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
        return ['ok' => false, 'text' => '', 'tokensIn' => 0, 'tokensOut' => 0, 'error' => $error];
    }
}
