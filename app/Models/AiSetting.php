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
        'gemini' => 'gemini-2.0-flash',
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
}
