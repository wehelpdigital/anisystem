<?php

namespace App\Console\Commands;

use App\Services\AcumbamailService;
use App\Support\Newsletter\Audience;
use App\Support\Newsletter\Demo;
use App\Support\Newsletter\SequenceImport;
use App\Support\Newsletter\Seg;
use Illuminate\Console\Command;

/**
 * The newsletter's one-off jobs (2026-10-17):
 *   --fields    add the "name" field to each of the eight lists
 *   --sync      put everyone on the lists they belong on now
 *   --pull      read every list's unsubscribes (ten reads a minute, so slow)
 *   --import    bring the Acumbamail sequence into the regular line (--dry to only look)
 *   --demo      the sample emails, as drafts
 */
class NewsletterSetup extends Command
{
    protected $signature = 'newsletter:setup {--fields} {--sync} {--pull} {--import} {--dry} {--demo} {--refresh : with --demo, write the samples again}';

    protected $description = 'One-off newsletter jobs: list fields, the first sync, the sequence import, the samples';

    public function handle(AcumbamailService $am): int
    {
        if ($this->option('fields')) {
            foreach (Seg::lists() as $key => $id) {
                $ok = $am->addListField($id, 'name', 'text');
                $this->line(str_pad($key, 10) . ($ok ? 'name field added' : 'not added: ' . $am->lastError()));
                sleep(7);
            }
        }
        if ($this->option('sync')) {
            $s = Audience::sync(null, 600);
            $this->info("checked {$s['checked']}, added {$s['added']}, removed {$s['removed']}, failed {$s['failed']}, unsubscribed kept off {$s['skipped']}");
            foreach ($s['errors'] as $e) {
                $this->warn($e);
            }
            foreach (Audience::counts() as $k => $n) {
                $this->line(str_pad(Seg::LABELS[$k][0], 16) . $n);
            }
        }
        if ($this->option('pull')) {
            foreach (array_keys(Seg::lists()) as $key) {
                $p = Audience::pullUnsubscribes($key);
                $this->line(str_pad($key, 10) . "{$p['gone']} gone, {$p['newly']} new" . ($p['error'] ? " ({$p['error']})" : ''));
                sleep(20);
            }
        }
        if ($this->option('import')) {
            $r = SequenceImport::run((bool) $this->option('dry'), 'Sequence import');
            if ($r['error']) {
                $this->error($r['error']);

                return self::FAILURE;
            }
            $this->info("found {$r['found']}, added {$r['added']}, already here {$r['skipped']}" . ($this->option('dry') ? ' (dry run)' : ''));
            foreach ($r['rows'] as $row) {
                if ($row['status'] === 'draft' || $row['sendOn'] || $row['audience'] !== [Seg::ALL]) {
                    $this->line(str_pad(substr($row['name'], 5, 9), 10) . str_pad($row['status'], 7) . str_pad((string) $row['sendOn'], 11) . mb_substr((string) $row['note'], 0, 110));
                }
            }
        }
        if ($this->option('demo')) {
            $made = Demo::run('Demo', (bool) $this->option('refresh'));
            $this->info($made ? 'Made: ' . implode(', ', $made) : 'The samples are already there.');
        }

        return self::SUCCESS;
    }
}
