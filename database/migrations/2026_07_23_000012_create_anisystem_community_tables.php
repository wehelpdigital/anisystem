<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Community: members publish a cropping plan and, in exchange, get to browse,
 * question and rate everybody else's.
 *
 * `as_cropping_schedules` is shared with the mother btc-check app, so the
 * publishing flags are added as nullable columns and nothing existing changes
 * meaning — a row with isPublic NULL/0 behaves exactly as it does today.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_cropping_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('as_cropping_schedules', 'isPublic')) {
                $table->tinyInteger('isPublic')->default(0)->index()->after('status');
            }
            if (! Schema::hasColumn('as_cropping_schedules', 'publishedAt')) {
                $table->timestamp('publishedAt')->nullable()->after('isPublic');
            }
            if (! Schema::hasColumn('as_cropping_schedules', 'publicSummary')) {
                $table->string('publicSummary', 500)->nullable()->after('publishedAt');
            }
            if (! Schema::hasColumn('as_cropping_schedules', 'publicRegion')) {
                $table->string('publicRegion', 120)->nullable()->after('publicSummary');
            }
        });

        if (! Schema::hasTable('as_community_comments')) {
            Schema::create('as_community_comments', function (Blueprint $table) {
                $table->id();
                $table->integer('croppingScheduleId')->index();
                $table->integer('anisystemUserId')->index();
                // A reply points at the comment it answers; top-level is null.
                $table->integer('parentId')->nullable()->index();
                $table->text('body');
                $table->tinyInteger('isQuestion')->default(0);
                $table->integer('deleteStatus')->default(1)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('as_community_ratings')) {
            Schema::create('as_community_ratings', function (Blueprint $table) {
                $table->id();
                $table->integer('croppingScheduleId')->index();
                $table->integer('anisystemUserId')->index();
                $table->tinyInteger('rating');           // 1-5
                $table->string('review', 500)->nullable();
                $table->integer('deleteStatus')->default(1)->index();
                $table->timestamps();

                // One rating per member per plan; re-rating updates the row.
                $table->unique(['croppingScheduleId', 'anisystemUserId'], 'community_rating_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_community_ratings');
        Schema::dropIfExists('as_community_comments');

        Schema::table('as_cropping_schedules', function (Blueprint $table) {
            $table->dropColumn(['isPublic', 'publishedAt', 'publicSummary', 'publicRegion']);
        });
    }
};
