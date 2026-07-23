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

    public function ask(AiSetting $settings, array $history, string $prompt, ?array $image = null): array
    {
        $key = $settings->plainApiKey();
        if (! $key) {
            return $this->fail('The AI is not configured yet. Please contact support.');
        }

        try {
            return match ($settings->provider) {
                'openai' => $this->askOpenAi($settings, $key, $history, $prompt, $image),
                'gemini' => $this->askGemini($settings, $key, $history, $prompt, $image),
                default => $this->askClaude($settings, $key, $history, $prompt, $image),
            };
        } catch (\Throwable $e) {
            report($e);

            return $this->fail('The AI could not be reached. Please try again in a moment.');
        }
    }

    // ------------------------------------------------------------------

    private function askClaude(AiSetting $s, string $key, array $history, string $prompt, ?array $image): array
    {
        $messages = [];
        foreach ($history as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => [['type' => 'text', 'text' => $turn['text']]]];
        }

        $content = [];
        if ($image) {
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

    private function askOpenAi(AiSetting $s, string $key, array $history, string $prompt, ?array $image): array
    {
        $messages = [['role' => 'system', 'content' => $s->systemPrompt]];
        foreach ($history as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => $turn['text']];
        }

        $content = [['type' => 'text', 'text' => $prompt]];
        if ($image) {
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

    private function askGemini(AiSetting $s, string $key, array $history, string $prompt, ?array $image): array
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
        if ($image) {
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
