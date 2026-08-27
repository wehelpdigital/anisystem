<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A fourth answer to "how are days counted here": they are not.
 *
 * Every schedule so far has been a season with a beginning — sown,
 * transplanted or planted — and its days counted from that day. An orchard
 * has no such day. The trees were there last year and will be there next
 * year, and what they need depends on how old they are, not on how long ago
 * the season started.
 *
 * The column is a real MySQL enum, so a fourth value needs the table altered
 * rather than just the code taught. Done in raw SQL because Laravel's schema
 * builder cannot widen an enum without doctrine/dbal, and adding a dependency
 * to add one word to a list is a poor trade.
 *
 * Additive: every existing row keeps the value it has, and 'DAS' stays the
 * default, so nothing that is running changes.
 */
return new class extends Migration
{
    private const TABLE = 'as_cropping_schedules';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasColumn(self::TABLE, 'dayType')) {
            return;
        }
        DB::statement(
            "ALTER TABLE `" . self::TABLE . "` MODIFY `dayType` "
            . "ENUM('DAP','DAS','DAT','TREE') NOT NULL DEFAULT 'DAS'"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasColumn(self::TABLE, 'dayType')) {
            return;
        }
        // Anything standing on the new value has to come back to something the
        // narrower column can hold, or the ALTER silently blanks it.
        DB::table(self::TABLE)->where('dayType', 'TREE')->update(['dayType' => 'DAP']);
        DB::statement(
            "ALTER TABLE `" . self::TABLE . "` MODIFY `dayType` "
            . "ENUM('DAP','DAS','DAT') NOT NULL DEFAULT 'DAS'"
        );
    }
};
