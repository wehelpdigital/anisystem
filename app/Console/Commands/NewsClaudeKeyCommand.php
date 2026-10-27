<?php

namespace App\Console\Commands;

use App\Models\AiSetting;
use App\Models\AsSiteSetting;
use App\Support\AiKeyCipher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * The owner's Anthropic key for the daily farm news reading (2026-10-27,
 * App\Models\AiSetting::newsReader). Kept encrypted on the shared settings
 * shelf (news.claude_key, the same cipher as the AI settings row), so one
 * entry serves this machine and the live app alike. The key is typed at a
 * hidden prompt, checked with one small request, then kept.
 *
 *   php artisan news:claude-key            set or replace it
 *   php artisan news:claude-key --model=claude-haiku-5-5   change the model too
 *   php artisan news:claude-key --forget   remove it (the reading goes back to Anee's cheapest model)
 */
class NewsClaudeKeyCommand extends Command
{
    protected $signature = 'news:claude-key {--forget : Remove the kept key} {--model= : The Claude model for the reading}';

    protected $description = 'Keep the Anthropic key the daily news reading uses';

    public function handle(): int
    {
        if ($this->option('forget')) {
            AsSiteSetting::put('news.claude_key', '');
            $this->info('Removed. The daily news reading is back on Anee\'s cheapest model.');

            return self::SUCCESS;
        }
        if (! AiKeyCipher::available()) {
            $this->error('The shared key cipher is not set up on this machine.');

            return self::FAILURE;
        }
        if ($model = trim((string) $this->option('model'))) {
            AsSiteSetting::put('news.claude_model', $model);
        }
        $key = trim((string) $this->secret('Anthropic API key (sk-ant-...)'));
        if ($key === '') {
            $this->line($model ? 'Model set to ' . $model . '.' : 'Nothing changed.');

            return self::SUCCESS;
        }
        // One small request proves the key before it is kept.
        $res = Http::timeout(60)->withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01'])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => (string) (AsSiteSetting::get('news.claude_model') ?: config('services.anthropic.news_model') ?: AiSetting::NEWS_CLAUDE_MODEL),
                'max_tokens' => 1024, 'output_config' => ['effort' => 'low'],
                'messages' => [['role' => 'user', 'content' => 'Reply with the word ok.']],
            ]);
        if (! $res->successful()) {
            $this->error('The key was not accepted: ' . ($res->json('error.message') ?: 'HTTP ' . $res->status()));

            return self::FAILURE;
        }
        AsSiteSetting::put('news.claude_key', AiKeyCipher::encrypt($key));
        $this->info('Kept. The daily news reading now runs on ' . (AiSetting::newsReader()?->effectiveModel() ?? 'Claude') . '.');

        return self::SUCCESS;
    }
}
