<?php

namespace App\Console\Commands;

use App\Support\PhFertilizers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * NPK Plus's Philippine products (2026-10-12): the Fertilizer and Pesticide
 * Authority's list of active registered fertilizers, read once a month (and
 * once at first, while the table holds only the hand checked products).
 *
 *   fertilizers:fpa-sync            the newest list, if it changed
 *   fertilizers:fpa-sync --force    read it again even if it did not
 *   fertilizers:fpa-sync --file=x   a downloaded .xlsx instead of the page
 */
class FertilizersFpaSyncCommand extends Command
{
    protected $signature = 'fertilizers:fpa-sync {--force : read the list even if it did not change} {--file= : a downloaded .xlsx}';

    protected $description = 'Update the Philippine fertilizer products from the FPA list';

    public function handle(): int
    {
        if (! Schema::hasTable('as_ph_fertilizers')) {
            $this->warn('The Philippine fertilizer table is not there yet.');

            return self::SUCCESS;
        }
        if ($file = $this->option('file')) {
            $this->info(json_encode(PhFertilizers::syncFromFile((string) $file)));

            return self::SUCCESS;
        }
        $r = PhFertilizers::fetchLatest((bool) $this->option('force'));
        if (! empty($r['error'])) {
            $this->warn($r['error']);
        }
        $this->info(($r['changed'] ? 'Read ' : 'Unchanged: ') . ($r['url'] ?? 'no list') . (isset($r['result']) ? ' ' . json_encode($r['result']) : ''));

        return self::SUCCESS;
    }
}
