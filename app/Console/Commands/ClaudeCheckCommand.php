<?php

namespace App\Console\Commands;

use App\Models\AiSetting;
use App\Models\AsSiteSetting;
use App\Services\AiClient;
use App\Support\ClaudeFirst;
use Illuminate\Console\Command;

/**
 * A live check of the owner's Claude (2026-10-27, App\Support\ClaudeFirst):
 * one small ask, and unless --quick a written page on each model (how fast
 * the words come), a searched question, a picture, and an ask with a
 * document's room for words (whether the account's limits take it). Asked
 * of Claude directly, whatever the switch or a rest says. The results go to
 * ai.claude_check on the settings shelf for the deploy check (never the
 * key), the provider's own words to ai.claude_check_detail (shelf only).
 * Setting ai.claude_check_now = 1 on the shelf has the scheduler run it.
 */
class ClaudeCheckCommand extends Command
{
    protected $signature = 'ai:claude-check {--quick : Only the one small ask}';

    protected $description = 'Check the owner\'s Claude: the key, the speed, search and pictures';

    public function handle(): int
    {
        $key = ClaudeFirst::key();
        if ($key === '') {
            AsSiteSetting::put('ai.claude_check', json_encode(['at' => now('Asia/Manila')->toIso8601String(), 'ok' => false, 'error' => 'No Anthropic key is kept.']));
            $this->error('No Anthropic key is kept.');

            return self::FAILURE;
        }
        $on = function (string $model) use ($key): AiSetting {
            $c = clone AiSetting::current();
            $c->provider = 'claude';
            $c->model = $model;
            $c->keyOverride = $key;

            return $c;
        };
        $main = ClaudeFirst::model('main');
        $page = 'Write a plain guide of about 900 words for a Filipino rice farmer on preparing a wet seedbed. Plain prose, no headings, no lists.';
        $tests = [['hello', $main, 'Reply with the word ok.', 64, []]];
        if (! $this->option('quick')) {
            $tests[] = ['page', $main, $page, 3000, ['timeout' => 300]];
            $tests[] = ['page', 'claude-sonnet-5-5', $page, 3000, ['timeout' => 300]];
            $tests[] = ['page', ClaudeFirst::model('cheap'), $page, 3000, ['timeout' => 300]];
            $tests[] = ['search', $main, 'Search the web for this week\'s farmgate price of fresh palay in Nueva Ecija, Philippines, and say it in two lines with the date of the figure.', 600, ['search' => true, 'timeout' => 300]];
            $tests[] = ['room', $main, 'Reply with the word ok.', 18000, []];
            if ($picture = self::picture()) {
                $tests[] = ['picture', $main, 'What colour fills this picture? One word.', 64, ['image' => $picture]];
            }
        }
        $ai = app(AiClient::class);
        $rows = [];
        $details = [];
        $allOk = true;
        foreach ($tests as [$name, $model, $prompt, $maxOut, $opts]) {
            $began = microtime(true);
            $ai->lastProviderError = null;
            $r = $ai->ask($on($model), [], $prompt, $opts['image'] ?? null, $maxOut, ['system' => 'You answer plainly.', 'timeout' => $opts['timeout'] ?? 60] + array_intersect_key($opts, ['search' => 1]));
            $secs = round(microtime(true) - $began, 1);
            $out = (int) ($r['tokensOut'] ?? 0);
            $row = ['test' => $name, 'model' => $r['model'] ?? $model, 'ok' => (bool) ($r['ok'] ?? false), 'seconds' => $secs,
                'tokensIn' => (int) ($r['tokensIn'] ?? 0), 'tokensOut' => $out, 'perSecond' => $secs > 0 ? round($out / $secs) : null,
                'answer' => mb_substr((string) ($r['text'] ?? ''), 0, $name === 'search' ? 240 : 40), 'error' => $r['error'] ?? null];
            if ($name === 'search') {
                $row += ['searched' => (bool) ($r['searched'] ?? false), 'sources' => count((array) ($r['sources'] ?? []))];
            }
            $rows[] = $row;
            if ($ai->lastProviderError) {
                $details[] = ['test' => $name, 'model' => $model] + $ai->lastProviderError;
            }
            $allOk = $allOk && $row['ok'];
            $this->line(json_encode($row, JSON_UNESCAPED_UNICODE));
        }
        AsSiteSetting::put('ai.claude_check', json_encode(['at' => now('Asia/Manila')->toIso8601String(), 'ok' => $allOk, 'tests' => $rows], JSON_UNESCAPED_UNICODE));
        AsSiteSetting::put('ai.claude_check_detail', json_encode($details, JSON_UNESCAPED_UNICODE));
        if ($rows[0]['ok']) {
            ClaudeFirst::answered((string) $rows[0]['model']);
        }

        return $allOk ? self::SUCCESS : self::FAILURE;
    }

    /** A small picture of one colour, for the picture test (null without GD). */
    private static function picture(): ?array
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }
        $im = imagecreatetruecolor(96, 96);
        imagefill($im, 0, 0, imagecolorallocate($im, 220, 30, 30));
        ob_start();
        imagepng($im);
        $png = (string) ob_get_clean();
        imagedestroy($im);

        return ['mime' => 'image/png', 'data' => base64_encode($png)];
    }
}
