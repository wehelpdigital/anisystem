<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Contact List — every member's own farm phonebook. Rows belong to the
 * ACTING user (a worker keeps their own book, an owner theirs); tags ride
 * the row as a JSON list so "Harvester" or "Tractor Rental" is a filter,
 * not a table. Open to every tier by design.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_contacts')) {
            return;
        }

        Schema::create('as_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId')->index();
            $table->string('name', 150);
            $table->string('phone', 40)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('company', 150)->nullable();
            $table->string('address', 255)->nullable();
            $table->text('notes')->nullable();
            $table->text('tags')->nullable();   // JSON array of strings
            $table->tinyInteger('deleteStatus')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_contacts');
    }
};
