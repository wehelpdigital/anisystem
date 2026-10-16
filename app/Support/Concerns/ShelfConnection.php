<?php

namespace App\Support\Concerns;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The tables the app keeps for itself, to ask the outside less (the climate
 * shelf, a place's risk record, kept research, the nutrient book, 2026-10-18),
 * are read through one connection: the app's own, unless
 * config('climate.connection') names another, as a rehearsal on a local
 * database does. The cache keys carry its name, so the answers of one
 * database never pass for another's.
 */
trait ShelfConnection
{
    private static function conn(): string
    {
        return (string) (config('climate.connection') ?: DB::getDefaultConnection());
    }

    private static function db(): Connection
    {
        return DB::connection(self::conn());
    }

    private static function schema(): Builder
    {
        return Schema::connection(self::conn());
    }
}
