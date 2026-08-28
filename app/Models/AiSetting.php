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

    /** What she is called when nobody has renamed her. */
    public const DEFAULT_NAME = 'Anee';

    /**
     * She has a name, not a job title.
     *
     * "Agricultural AI Technician" is what she IS; every screen that greets a
     * farmer needs what she is CALLED. A farm that wants its own name still
     * sets one in the mother app and that wins — this only catches the blank
     * and the generic placeholder every install shipped with.
     */
    public function getAssistantNameAttribute($value): string
    {
        $value = trim((string) $value);

        return ($value === '' || $value === 'Agricultural AI Technician' || $value === 'AI Technician')
            ? self::DEFAULT_NAME
            : $value;
    }

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

        If no background is attached to a question, you do not have the
        farmer's plan and you must not pretend to. Do not guess their crop,
        their variety, their planting date, their soil, their region or their
        stage. Answer generally, or ask for the one detail that decides it.
        TXT;

    /**
     * Who is answering.
     *
     * She has a name and a manner because a farmer asking about their own
     * field at six in the morning is talking to somebody, not querying a
     * system -- and warmth is what gets a half-formed worry typed out at all.
     *
     * The manner stops exactly where the facts begin. A cheerful voice that
     * softens a bad diagnosis, agrees to be agreeable, or fills a gap with a
     * confident guess is worse than a cold one, because it is trusted. So the
     * warmth is in HOW she says it and never in WHAT she says.
     *
     * Written under the admin's own prompt and above the house rules, so a
     * farm that wants a different voice can still write one.
     */
    private const PERSONA = <<<'TXT'
        --- Who you are ---
        You are Anee, an agricultural technician for Filipino farmers. You are
        warm, upbeat and easy to talk to -- the technician people are glad to
        see walking up the dike. You use plain words, short sentences, and the
        farmer's own units (hectares, sacks, cavans, pesos). If they write in
        Tagalog, Bisaya or Taglish, answer the same way.

        Your warmth is in your manner, never in your facts:
        - Say the true thing, including when it is bad news, and say it plainly
          and early. Do not soften a diagnosis into uselessness.
        - When you do not know, say so. When the evidence is thin, say how
          thin. Never invent a number, a product name, a dose or a date.
        - Do not agree just to be agreeable. If the farmer's plan looks wrong,
          say which part and why, kindly and directly.
        - No brand favouritism, and no pushing chemicals where a cultural or
          preventive answer does the job. Give the cheaper honest option its
          fair hearing.
        - Note when something depends on local conditions, and say what would
          settle it -- a soil test, an extension officer, the seed label.
        - No flattery. Do not open by praising the question. Answer it.
        TXT;

    /**
     * Anee's face.
     *
     * Whatever the mother app's admin set, and her own portrait otherwise —
     * she ships with a face rather than a placeholder robot, and an admin can
     * still put another one over it.
     */
    public function faceUrl(): string
    {
        return $this->avatarPath
            ? \App\Support\MediaStore::url($this->avatarPath)
            : asset('images/anee/avatar-512.jpg');
    }

    /** The prompt the provider is actually given. */
    public function instructions(): string
    {
        return trim(self::PERSONA . "\n\n" . trim((string) $this->systemPrompt))
            . "\n\n" . self::HOUSE_RULES
            // Built rather than written out: the list of faces lives with the
            // pictures, so adding one to the sheet cannot leave the prompt
            // offering a name that draws nothing.
            . "\n\n" . \App\Support\AneeEmoji::promptLine();
    }
}
