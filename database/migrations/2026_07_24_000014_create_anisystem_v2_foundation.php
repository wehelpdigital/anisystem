<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foundation schema for the v2 feature set: per-schedule notes, worker email,
 * user location/profile, public share tokens, notifications, and the Community
 * social layer (groups, connections, walls).
 *
 * House conventions: camelCase columns, integer `deleteStatus` (1 active / 0
 * deleted), no DB-level foreign keys, guarded so it is safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- Users: location + light profile ----
        Schema::table('anisystem_users', function (Blueprint $table) {
            foreach ([
                'city' => fn () => $table->string('city', 120)->nullable()->after('phone'),
                'province' => fn () => $table->string('province', 120)->nullable()->after('city'),
                'bio' => fn () => $table->string('bio', 300)->nullable()->after('province'),
                'avatarPath' => fn () => $table->string('avatarPath', 500)->nullable()->after('bio'),
            ] as $col => $add) {
                if (! Schema::hasColumn('anisystem_users', $col)) {
                    $add();
                }
            }
        });

        // ---- Workers: optional email so the system can mail them ----
        if (! Schema::hasColumn('as_schedule_workers', 'email')) {
            Schema::table('as_schedule_workers', function (Blueprint $table) {
                $table->string('email', 191)->nullable()->after('workerName');
            });
        }

        // ---- Schedules: public share token for OG share pages ----
        if (! Schema::hasColumn('as_cropping_schedules', 'shareToken')) {
            Schema::table('as_cropping_schedules', function (Blueprint $table) {
                $table->string('shareToken', 40)->nullable()->unique()->after('isPublic');
            });
        }

        // ---- Schedule notes module ----
        $this->createIfMissing('as_schedule_notes', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId')->index();
            $table->integer('userId')->index();
            $table->string('title', 191);
            $table->longText('body')->nullable();     // sanitised Quill HTML
            $table->string('imagePath', 500)->nullable();
            $table->integer('sortOrder')->default(0);
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });

        // ---- Notifications ----
        $this->createIfMissing('anisystem_notifications', function (Blueprint $table) {
            $table->id();
            $table->integer('userId')->index();
            $table->string('type', 40);               // comment | reply | rating | connection | expiry | system
            $table->string('title', 191);
            $table->string('body', 500)->nullable();
            $table->string('url', 500)->nullable();
            $table->integer('actorUserId')->nullable();
            $table->integer('croppingScheduleId')->nullable();
            $table->timestamp('readAt')->nullable();
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });

        // ---- Community: groups ----
        $this->createIfMissing('as_community_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 170)->unique();
            $table->string('description', 500)->nullable();
            $table->string('coverImagePath', 500)->nullable();
            $table->integer('createdByUserId')->index();
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });

        $this->createIfMissing('as_community_group_members', function (Blueprint $table) {
            $table->id();
            $table->integer('groupId')->index();
            $table->integer('userId')->index();
            $table->string('role', 20)->default('member'); // owner | member
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
            $table->unique(['groupId', 'userId'], 'community_group_member_unique');
        });

        $this->createIfMissing('as_community_group_posts', function (Blueprint $table) {
            $table->id();
            $table->integer('groupId')->index();
            $table->integer('userId')->index();
            $table->string('title', 191)->nullable();
            $table->text('body');
            $table->string('imagePath', 500)->nullable();
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });

        $this->createIfMissing('as_community_group_replies', function (Blueprint $table) {
            $table->id();
            $table->integer('postId')->index();
            $table->integer('userId')->index();
            $table->text('body');
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });

        // ---- Community: connections (co-farmer friend requests) ----
        $this->createIfMissing('as_community_connections', function (Blueprint $table) {
            $table->id();
            // Stored one-directional; accepted means both can see each other.
            $table->integer('userId')->index();          // the requester
            $table->integer('friendUserId')->index();     // the addressee
            $table->string('status', 20)->default('pending'); // pending | accepted | declined
            $table->timestamp('respondedAt')->nullable();
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
            $table->unique(['userId', 'friendUserId'], 'community_connection_unique');
        });

        // ---- Community: account wall ----
        $this->createIfMissing('as_community_wall_posts', function (Blueprint $table) {
            $table->id();
            $table->integer('wallUserId')->index();       // whose wall
            $table->integer('authorUserId')->index();     // who posted
            $table->text('body')->nullable();
            $table->string('imagePath', 500)->nullable();
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });

        $this->createIfMissing('as_community_wall_comments', function (Blueprint $table) {
            $table->id();
            $table->integer('wallPostId')->index();
            $table->integer('userId')->index();
            $table->text('body');
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });

        // Backfill share tokens for existing schedules.
        foreach (\Illuminate\Support\Facades\DB::table('as_cropping_schedules')->whereNull('shareToken')->pluck('id') as $sid) {
            \Illuminate\Support\Facades\DB::table('as_cropping_schedules')
                ->where('id', $sid)
                ->update(['shareToken' => \Illuminate\Support\Str::random(32)]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_community_wall_comments');
        Schema::dropIfExists('as_community_wall_posts');
        Schema::dropIfExists('as_community_connections');
        Schema::dropIfExists('as_community_group_replies');
        Schema::dropIfExists('as_community_group_posts');
        Schema::dropIfExists('as_community_group_members');
        Schema::dropIfExists('as_community_groups');
        Schema::dropIfExists('anisystem_notifications');
        Schema::dropIfExists('as_schedule_notes');

        Schema::table('as_cropping_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('as_cropping_schedules', 'shareToken')) {
                $table->dropColumn('shareToken');
            }
        });
        if (Schema::hasColumn('as_schedule_workers', 'email')) {
            Schema::table('as_schedule_workers', fn (Blueprint $t) => $t->dropColumn('email'));
        }
        Schema::table('anisystem_users', function (Blueprint $table) {
            foreach (['city', 'province', 'bio', 'avatarPath'] as $col) {
                if (Schema::hasColumn('anisystem_users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function createIfMissing(string $table, callable $definition): void
    {
        if (! Schema::hasTable($table)) {
            Schema::create($table, $definition);
        }
    }
};
