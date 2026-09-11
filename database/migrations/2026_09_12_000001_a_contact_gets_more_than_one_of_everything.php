<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A person in a farm phonebook rarely has one of anything.
 *
 * The harvester crew's boss has a Globe number and a Smart number, the
 * buyer answers on one address and ships to another, and a supplier's
 * office sits in a town the farmer would rather pick from a list than
 * spell. So: phones and emails become JSON lists, the single address
 * line becomes two, and the place is recorded as province + town from
 * the as_locations table rather than typed.
 *
 * `phone` and `email` STAY, holding the first of each list. Everything
 * already built reads them — the list's search, the tel:/sms:/mailto:
 * buttons on a row, the worker-to-contact match — and a JSON column is
 * no place to ask MySQL to search. The controller mirrors them on every
 * write, so the two never drift.
 *
 * Backfilled from those columns, so every contact saved before today
 * opens with exactly what it had: one phone in the list, one email, and
 * its address on line one.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_contacts')) {
            return;
        }

        Schema::table('as_contacts', function (Blueprint $table) {
            if (! Schema::hasColumn('as_contacts', 'phones')) {
                $table->text('phones')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('as_contacts', 'emails')) {
                $table->text('emails')->nullable()->after('email');
            }
            if (! Schema::hasColumn('as_contacts', 'address2')) {
                $table->string('address2', 255)->nullable()->after('address');
            }
            if (! Schema::hasColumn('as_contacts', 'province')) {
                $table->string('province', 120)->nullable()->after('address2');
            }
            if (! Schema::hasColumn('as_contacts', 'town')) {
                $table->string('town', 120)->nullable()->after('province');
            }
        });

        // The lists start as whatever the single columns held. JSON_ARRAY
        // would be tidier but MariaDB and older MySQL disagree about it, so
        // the string is built by hand — the value is escaped by the driver.
        foreach (DB::table('as_contacts')->select('id', 'phone', 'email')->get() as $row) {
            DB::table('as_contacts')->where('id', $row->id)->update([
                'phones' => filled($row->phone) ? json_encode([$row->phone]) : json_encode([]),
                'emails' => filled($row->email) ? json_encode([$row->email]) : json_encode([]),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('as_contacts')) {
            return;
        }

        Schema::table('as_contacts', function (Blueprint $table) {
            foreach (['phones', 'emails', 'address2', 'province', 'town'] as $col) {
                if (Schema::hasColumn('as_contacts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
