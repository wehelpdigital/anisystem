<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A discussion that can shut its door, and the record of who is at it.
 *
 * Every room here has been open since the day rooms existed. Some
 * conversations are not for everybody — a co-op's prices, a barangay's
 * plans — so a room can now be private, and a private room asks for either
 * a password the organiser shares or their say-so one person at a time.
 *
 * The password is ENCRYPTED, not hashed. It is a shared secret like the one
 * on a wifi router, not a credential: the organiser has to be able to read it
 * back to tell the next person, and a hash cannot be read back. Encryption
 * keeps it unreadable in a database dump, which is the threat that matters.
 *
 * People waiting at the door get their own table rather than a `pending` row
 * among the members. The membership table is what the whole app means by "is
 * this person in this room" — one query, five call sites — and a waiting
 * stranger sitting in it would silently become a member everywhere that query
 * runs.
 *
 * Being removed, on the other hand, DOES belong on the membership row: there
 * is already exactly one row per person per room, and leaving already empties
 * it. What was missing is the difference between walking out and being shown
 * out — without it, somebody removed from a password room simply retypes the
 * password and walks back in, which makes removing them mean nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_community_groups')) {
            Schema::table('as_community_groups', function (Blueprint $t) {
                if (! Schema::hasColumn('as_community_groups', 'privacy')) {
                    // public | private. Every room that already exists is open,
                    // and stays open — this changes nothing that is running.
                    $t->string('privacy', 10)->default('public')->after('description');
                }
                if (! Schema::hasColumn('as_community_groups', 'joinMode')) {
                    // password | approval. Only read when privacy is private.
                    $t->string('joinMode', 12)->nullable()->after('privacy');
                }
                if (! Schema::hasColumn('as_community_groups', 'joinPassword')) {
                    $t->string('joinPassword', 500)->nullable()->after('joinMode');
                }
            });
        }

        if (Schema::hasTable('as_community_group_members')) {
            Schema::table('as_community_group_members', function (Blueprint $t) {
                if (! Schema::hasColumn('as_community_group_members', 'removedAt')) {
                    $t->timestamp('removedAt')->nullable()->after('role');
                }
                if (! Schema::hasColumn('as_community_group_members', 'removedReason')) {
                    $t->string('removedReason', 500)->nullable()->after('removedAt');
                }
                if (! Schema::hasColumn('as_community_group_members', 'removedByUserId')) {
                    $t->integer('removedByUserId')->nullable()->after('removedReason');
                }
            });
        }

        if (! Schema::hasTable('as_community_group_join_requests')) {
            Schema::create('as_community_group_join_requests', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('groupId')->index();
                $t->integer('userId')->index();
                // pending | approved | declined. The row is kept after a
                // decision so a second ask can be told apart from a first.
                $t->string('status', 12)->default('pending')->index();
                $t->integer('decidedByUserId')->nullable();
                $t->timestamp('decidedAt')->nullable();
                $t->integer('deleteStatus')->default(1)->index();
                $t->timestamps();
                // One standing request per person per room; asking again
                // reuses the row rather than queueing a second copy.
                $t->unique(['groupId', 'userId'], 'community_group_join_request_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('as_community_groups')) {
            Schema::table('as_community_groups', function (Blueprint $t) {
                foreach (['privacy', 'joinMode', 'joinPassword'] as $col) {
                    if (Schema::hasColumn('as_community_groups', $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('as_community_group_members')) {
            Schema::table('as_community_group_members', function (Blueprint $t) {
                foreach (['removedAt', 'removedReason', 'removedByUserId'] as $col) {
                    if (Schema::hasColumn('as_community_group_members', $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('as_community_group_join_requests');
    }
};
