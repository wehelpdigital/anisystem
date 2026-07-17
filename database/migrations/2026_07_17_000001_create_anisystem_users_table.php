<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('anisystem_users')) {
            return;
        }

        Schema::create('anisystem_users', function (Blueprint $table) {
            $table->id();
            $table->string('firstName', 100);
            $table->string('lastName', 100);
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->index();
            $table->string('password', 255);
            $table->unsignedBigInteger('clientId')->nullable()->index(); // soft FK -> clients_all_database.id
            $table->string('status', 20)->default('active'); // active | disabled
            $table->rememberToken();
            $table->integer('deleteStatus')->default(1)->index(); // 1 = active, 0 = soft-deleted (house convention)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anisystem_users');
    }
};
