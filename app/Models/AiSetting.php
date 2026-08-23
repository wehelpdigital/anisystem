<?php

namespace App\Models;

use App\Support\AiKeyCipher;

/**
 * The single AI configuration row, managed from the mother app. The API key is
 * encrypted at rest and never leaves the server.
 */
class AiSetting extends BaseModel
{
    protected $table = 'anisystem_ai_settings';

    public const PROVIDERS = [
        'claude' => 'Claude (Anthropic)',
        'openai' => 'GPT (OpenAI)',
        'gemini' => 'Gemini (Google)',
    ];

    /** Sensible current default per provider, offered in the mother app. */
    public const DEFAULT_MODELS = [
        'claude' => 'claude-sonnet-5',
        'openai' => 'gpt-4o',
        // Google's alias for the newest stable Pro: the smartest model the
        // key holds, and it survives model retirements (gemini-2.0-flash's
        // fate) without anyone editing settings again.
        'gemini' => 'gemini-pro-latest',
    ];

    protected $fillable = [
        'provider', 'apiKey', 'model', 'systemPrompt', 'assistantName', 'avatarPath',
        'creditsPerInputK', 'creditsPerOutputK', 'creditsPerImage', 'freeCreditsOnSignup',
        'maxOutputTokens', 'temperature', 'isEnabled', 'deleteStatus',
    ];

    protected $casts = [
        'creditsPerInputK' => 'decimal:2',
        'creditsPerOutputK' => 'decimal:2',
        'creditsPerImage' => 'decimal:2',
        'freeCreditsOnSignup' => 'integer',
        'maxOutputTokens' => 'integer',
        'temperature' => 'decimal:2',
        'isEnabled' => 'boolean',
        'deleteStatus' => 'integer',
    ];

    protected $hidden = ['apiKey'];

    public static function current(): self
    {
        return static::query()->orderBy('id')->first() ?? new static();
    }

    /** Store the key encrypted; an empty value leaves the existing key alone. */
    public function setApiKeyAttribute($value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->attributes['apiKey'] = AiKeyCipher::encrypt($value);
    }

    /**
     * The key is written by the mother app under a shared secret. A key we
     * cannot decrypt is treated as missing rather than breaking every request.
     */
    public function plainApiKey(): ?string
    {
        return AiKeyCipher::decrypt($this->attributes['apiKey'] ?? null);
    }

    public function hasKey(): bool
    {
        return filled($this->plainApiKey());
    }

    /** Only usable when it is switched on and actually has a key. */
    public function isUsable(): bool
    {
        return $this->isEnabled && $this->hasKey();
    }

    public function effectiveModel(): string
    {
        return $this->model ?: (self::DEFAULT_MODELS[$this->provider] ?? 'claude-sonnet-5');
    }

    /**
     * House rules, added under whatever the admin has written.
     *
     * A question can arrive carrying background -- the season's crop and
     * lots, a day it is pinned to, the earlier turns of this chat -- and the
     * model treated all of it as the subject. A farmer asked what a beetle
     * was and heard about their rice at 25 DAS; another opened a fresh chat
     * and was answered in terms of the last one. The background is there in
     * case it is needed, not to be recited.
     *
     * Kept in code rather than in the editable prompt so it holds however
     * that prompt is rewritten, and so the rule reads the same on every farm.
     */
    private const HOUSE_RULES = <<<'TXT'
        --- Always ---
        Answer the question in front of you, and nothing else.
        A question may arrive with background attached: the farmer's cropping
        plan, the season's crop and lots, a day or task it is pinned to, or
        earlier turns of this conversation. That material is reference. Use it
        only when the question is about it or plainly needs it, and do not
        bring it up otherwise -- no summaries of the plan, no "as we discussed
        earlier", no recommendations aimed at a crop the question never
        mentioned. If the answer really does depend on which crop, plot or
        stage is meant, ask one short question instead of assuming.
        TXT;

    /** The prompt the provider is actually given. */
    public function instructions(): string
    {
        return trim((string) $this->systemPrompt) . "\n\n" . self::HOUSE_RULES;
    }
}
