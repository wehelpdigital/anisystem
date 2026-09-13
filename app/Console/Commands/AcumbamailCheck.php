<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AcumbamailService;
use Illuminate\Console\Command;

/**
 * Ask the live account what it actually has, before trusting any of it.
 *
 * Merge-field names are per list and are not guessable; the segment is a rule
 * over a field rather than a bag with a door. Both have to be read off the
 * account rather than assumed, and this is what reads them — and, with
 * --email, pushes one real row through the same path a signup takes.
 */
class AcumbamailCheck extends Command
{
    protected $signature = 'acumbamail:check
        {--email= : Push this address through addSubscriber as a live test}
        {--first=Sample}
        {--last=Signup}
        {--phone=09171234567}';

    protected $description = 'Check the Acumbamail list, its fields and its segments — and optionally add one subscriber';

    public function handle(AcumbamailService $am): int
    {
        if (! $am->configured()) {
            $this->error('Not configured. Set ACUMBAMAIL_TOKEN and ACUMBAMAIL_LIST_ID in .env.');

            return self::FAILURE;
        }

        $listId = (int) config('acumbamail.list_id');
        $this->line('List id in config: '.$listId);

        $this->newLine();
        $this->info('— getLists —');
        $lists = $am->lists();
        if ($lists === null) {
            $this->error('getLists failed. The token is wrong, or their API refused us. See the log.');

            return self::FAILURE;
        }
        foreach ($lists as $id => $row) {
            $name = is_array($row) ? ($row['name'] ?? '?') : (string) $row;
            $mark = ((int) $id === $listId) ? '  <<< the one in config' : '';
            $this->line('  '.$id.'  '.$name.$mark);
        }
        if (! array_key_exists((string) $listId, $lists) && ! array_key_exists($listId, $lists)) {
            $this->warn('  The configured list id is not in that list. Check ACUMBAMAIL_LIST_ID.');
        }

        $this->newLine();
        $this->info('— getFields (the names merge_fields must use) —');
        $fields = $am->fields();
        $this->line($fields === null ? '  failed' : json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->newLine();
        $this->info('— getListSegments (rules, not bags) —');
        $segments = $am->segments();
        $this->line($segments === null ? '  failed' : json_encode($segments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $email = (string) $this->option('email');
        if ($email === '') {
            $this->newLine();
            $this->comment('No --email given, so nothing was written. Pass --email=someone@example.com to push a real row.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('— addSubscriber —');
        $probe = new User([
            'firstName' => (string) $this->option('first'),
            'lastName' => (string) $this->option('last'),
            'phone' => (string) $this->option('phone'),
            'email' => $email,
        ]);
        $ok = $am->addMember($probe, 'free');
        $this->line($ok ? '  added / updated '.$email : '  FAILED — see the log');

        $this->newLine();
        $this->info('— searchSubscriber (reading it back) —');
        $found = $am->findSubscriber($email);
        $this->line($found === null ? '  failed' : json_encode($found, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
