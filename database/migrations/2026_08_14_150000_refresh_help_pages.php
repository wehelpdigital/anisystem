<?php

use Illuminate\Database\Migrations\Migration;

/**
 * The help pages describe the app, so they change when the app does.
 *
 * The Media Box became the Gallery's first tab, the weather lost its hourly
 * tab in favour of opening a day, the day counter grew a third answer and a
 * task can hold more than one type. A guide describing a screen nobody can
 * find is worse than none — it makes a grower think they are the one who is
 * lost. Rerunning the seeder is idempotent: it updates the starting content
 * and leaves anything the mother app's builder has since written on top.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new \Database\Seeders\TutorialPageSeeder)->run();

        // The Media Box has no door of its own any more.
        \App\Models\AsTutorialPage::where('moduleKey', 'media')->delete();
    }

    public function down(): void
    {
        // Content only; nothing to undo.
    }
};
