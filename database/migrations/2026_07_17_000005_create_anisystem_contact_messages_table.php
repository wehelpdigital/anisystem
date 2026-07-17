<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('anisystem_contact_messages')) {
            return;
        }

        Schema::create('anisystem_contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 255);
            $table->string('phone', 30)->nullable();
            $table->string('subject', 255)->nullable();
            $table->text('message');
            $table->tinyInteger('isRead')->default(0);
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anisystem_contact_messages');
    }
};
