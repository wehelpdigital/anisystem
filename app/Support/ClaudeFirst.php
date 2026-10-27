<?php

namespace App\Support;

use App\Models\AiSetting;
use App\Models\AsSiteSetting;
use App\Services\AiClient;

/**
 * Anee thinks on the owner's Claude first (2026-10-27, the owner: "for the
 * meantime can we use anthropic as the llm since i have free credits? ...
 * step 1 we use anthropic for the ai processes. then if that is used up then
 * we go to gemini").
 *
 * Every ask AiClient makes goes to Claude on the owner's Anthropic key
 * (ANTHROPIC_API_KEY, or news.claude_key on the settings shelf), and the
 * model set in the mother app (Gemini) answers the same ask when Claude
 * cannot: the credit is used up, the key is refused, Claude is busy or
 * stalls, declines, or cannot read what was sent (a voice note, a video).
 * A used up balance rests Claude for an hour and then one ask tries it
 * again, so a top up is picked up without anyone touching a setting.
 *
 * The mother app's settings row is left as it is: prices, the house cost
 * estimates and the voice note a season chat records still follow it.
 * ai.claude_first = 0 on the settings shelf turns this off (the news
 * reader stays on Claude, it was asked for on its own).
 */
final class ClaudeFirst
{
    /** The model for whatever answers a person: the chat, every analysis, Anee Auto (the owner's rule: the best model there). */
    public const MAIN = 'claude-opus-5-5';

    /** What Claude reads besides words; anything else (a voice note, a video) goes to the mother app's model. */
    private const READS = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];

    /** The owner's Anthropic key, from the environment or the settings shelf; '' when neither has one. */
    public static function key(): string
    {
        return trim((string) (config('services.anthropic.key') ?: AiKeyCipher::decrypt((string) AsSiteSetting::get('news.claude_key', ''))));
    }

    /** Whether every ask tries Claude first: switched on (the default) and a key kept. */
    public static function on(): bool
    {
        return AsSiteSetting::yes('ai.claude_first', true) && self::key() !== '';
    }

    /** The Claude model for a copy's tier: the main model, or the quick and cheapest ones (AiSetting::fast, cheap). */
    public static function model(string $tier): string
    {
        return match ($tier) {
            'fast' => AiSetting::FAST_MODELS['claude'],
            'cheap' => AiSetting::CHEAP_MODELS['claude'],
            default => (string) (AsSiteSetting::get('ai.claude_model') ?: config('services.anthropic.model') ?: self::MAIN),
        };
    }

    /** Whether Claude can read every picture or file an ask carries. */
    public static function reads(array $pictures): bool
    {
        foreach ($pictures as $p) {
            if (! in_array(strtolower((string) ($p['mime'] ?? '')), self::READS, true)) {
                return false;
            }
        }

        return true;
    }

    /** Claude's rest, while one lasts: ['until' => ISO time, 'why' => word]. */
    public static function resting(): ?array
    {
        $rest = json_decode((string) AsSiteSetting::get('ai.claude_rest', ''), true);

        return is_array($rest) && ! empty($rest['until']) && now()->lt(\Carbon\Carbon::parse($rest['until'])) ? $rest : null;
    }

    /**
     * Claude could not answer an ask, and the mother app's model is asked
     * instead. A used up balance or a refused key rests Claude for an hour,
     * a busy or unwell Claude for a minute or two, a stall (nothing heard
     * for a long while) for five; anything else (a refusal, a request it
     * would not take, a long answer cut at the caller's clock) is this ask
     * alone. What happened is kept on the settings shelf for the deploy
     * check, the provider's own words apart from it (never shown in public).
     *
     * @param  array{status?:int, message?:string}|null  $error  AiClient::$lastProviderError, null for a transport failure
     */
    public static function failed(?array $error, string $model, bool $stalled): void
    {
        $status = (int) ($error['status'] ?? 0);
        $message = (string) ($error['message'] ?? '');
        [$rest, $why] = match (true) {
            $status === 402 || AiClient::outOfCredit($message) => [3600, 'used up'],
            $status === 401 || $status === 403 => [3600, 'key refused'],
            $status === 429 => [60, 'busy'],
            $status >= 500 => [120, 'unwell'],
            $status === 0 && $stalled => [300, 'stalled'],
            $status === 0 => [0, 'cut or unreachable'],
            $status === 200 => [0, str_starts_with($message, 'refusal') ? 'declined' : 'stopped early'],
            default => [0, 'request refused'],
        };
        try {
            if ($rest > 0) {
                AsSiteSetting::put('ai.claude_rest', json_encode(['until' => now()->addSeconds($rest)->toIso8601String(), 'why' => $why]));
            }
            AsSiteSetting::put('ai.claude_problem', json_encode(['at' => now('Asia/Manila')->toIso8601String(), 'why' => $why, 'status' => $status, 'model' => $model, 'rest' => $rest]));
            AsSiteSetting::put('ai.claude_problem_detail', json_encode(['status' => $status, 'message' => mb_substr($message, 0, 300)]));
        } catch (\Throwable $e) {
            // Bookkeeping never stands between a farmer and an answer.
        }
        logger()->{$rest >= 3600 ? 'error' : 'warning'}('Claude first: the mother app\'s model answers instead', ['why' => $why, 'status' => $status, 'model' => $model]);
    }

    /** Claude answered: noted on the shelf at most every ten minutes (for the deploy check), and any rest is over. */
    public static function answered(string $model): void
    {
        try {
            $last = json_decode((string) AsSiteSetting::get('ai.claude_ok', ''), true);
            if (! is_array($last) || empty($last['at']) || \Carbon\Carbon::parse($last['at'])->lt(now()->subMinutes(10))) {
                AsSiteSetting::put('ai.claude_ok', json_encode(['at' => now('Asia/Manila')->toIso8601String(), 'model' => $model]));
            }
            if (AsSiteSetting::get('ai.claude_rest')) {
                AsSiteSetting::put('ai.claude_rest', '');
            }
        } catch (\Throwable $e) {
            // As above.
        }
    }

    /** What the deploy check shows: on or off, where the key comes from, the models, the rest, the last answer and problem. Never the key. */
    public static function state(): array
    {
        $key = self::key();

        return [
            'on' => self::on(),
            'switch' => AsSiteSetting::yes('ai.claude_first', true),
            'from' => $key === '' ? null : (filled(config('services.anthropic.key')) ? 'environment' : 'settings'),
            'models' => ['main' => self::model('main'), 'fast' => self::model('fast'), 'cheap' => self::model('cheap')],
            'resting' => self::resting(),
            'lastAnswer' => json_decode((string) AsSiteSetting::get('ai.claude_ok', ''), true),
            'lastProblem' => json_decode((string) AsSiteSetting::get('ai.claude_problem', ''), true),
            'lastCheck' => json_decode((string) AsSiteSetting::get('ai.claude_check', ''), true),
        ];
    }
}
