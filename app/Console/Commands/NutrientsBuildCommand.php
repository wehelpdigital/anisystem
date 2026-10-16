<?php

namespace App\Console\Commands;

use App\Support\NutrientBook;
use App\Support\ResearchShelf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * nutrients:build (2026-10-18): write NPK Plus's need model out as the
 * nutrient book (App\Support\NutrientBook, as_nutrient_needs). The scheduler
 * runs it daily with --if-stale, so the book follows the model the day the
 * model changes and costs nothing on the other days. --check compares a
 * sample of book rows with a live count of the same field.
 */
class NutrientsBuildCommand extends Command
{
    protected $signature = 'nutrients:build {--if-stale : only when the model changed since the book was written} {--check : compare book rows with a live count}';

    protected $description = 'Write the nutrient book from NPK Plus\'s need model';

    public function handle(): int
    {
        if (! NutrientBook::ready()) {
            $this->warn('The nutrient book table is not there yet.');

            return self::SUCCESS;
        }
        if ($this->option('check')) {
            return $this->check();
        }
        // The kept research past its time goes the same day.
        $pruned = ResearchShelf::prune();
        if ($this->option('if-stale') && NutrientBook::built()) {
            $this->info('The book is up to date (version ' . NutrientBook::version() . ').' . ($pruned ? ' Old research removed: ' . $pruned . '.' : ''));

            return self::SUCCESS;
        }
        $t = microtime(true);
        $n = NutrientBook::build();
        $this->info('Wrote ' . $n . ' rows, version ' . NutrientBook::version() . ', in ' . round(microtime(true) - $t, 1) . ' s.');

        return self::SUCCESS;
    }

    private function check(): int
    {
        $rows = DB::table('as_nutrient_needs')->where('version', NutrientBook::version())->inRandomOrder()->limit(200)->get();
        $bad = 0;
        foreach ($rows as $r) {
            $in = ['crop' => $r->crop, 'method' => null, 'potential' => $r->level === 'variety' ? (float) $r->potential : null, 'target' => $r->level === 'grid' ? (float) $r->yieldT : null,
                'areaHa' => 1, 'seed' => null, 'texture' => $r->texture, 'conditions' => [], 'ph' => NutrientBook::PH[$r->ph] ?? null, 'tests' => [], 'water' => 'irrigated', 'profile' => [], 'prev' => []];
            $live = NutrientBook::forField($in);
            $same = (int) $r->n === (int) ($live['need']['N'] ?? 0) && (int) $r->p === (int) ($live['need']['P2O5'] ?? 0) && (int) $r->k === (int) ($live['need']['K2O'] ?? 0);
            foreach ((array) json_decode((string) $r->micros, true) as $el => [$level, $kg]) {
                $same = $same && ($live['micro'][$el]['level'] ?? null) === $level && abs(round((float) $live['micro'][$el]['need'], 3) - (float) $kg) < 0.0005;
            }
            if (! $same) {
                $bad++;
                $this->line('Differs: #' . $r->id . ' ' . $r->crop . ' ' . $r->yieldT . ' ' . $r->texture . ' ' . $r->ph);
            }
        }
        $this->info('Checked ' . count($rows) . ' rows against a live count: ' . ($bad ? $bad . ' differ.' : 'all the same.'));

        return $bad ? self::FAILURE : self::SUCCESS;
    }
}
