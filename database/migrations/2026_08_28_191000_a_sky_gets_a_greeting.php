<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How to say good morning when it is raining.
 *
 * "Magandang umaga" is true at six on any morning and says nothing about
 * this one. A farmer who opens the app to a grey window would rather be
 * greeted by somebody who has looked out of it: "Maulang umaga."
 *
 * The half that changes is the adjective, and it carries its own linker
 * because Tagalog puts one where English does not — "Maulang umaga" but
 * "Maaraw na umaga". So the phrase is stored whole, and the time word is
 * simply added after it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_weather_scenes')
            || Schema::hasColumn('as_weather_scenes', 'greeting')) {
            return;
        }

        Schema::table('as_weather_scenes', function (Blueprint $table) {
            $table->string('greeting', 60)->nullable()->after('tagalog');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_weather_scenes', 'greeting')) {
            Schema::table('as_weather_scenes', function (Blueprint $table) {
                $table->dropColumn('greeting');
            });
        }
    }
};
