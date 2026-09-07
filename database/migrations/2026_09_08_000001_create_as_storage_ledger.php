<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The storage ledger behind the tier caps: one row per upload, bytes and
 * path, marked deleteStatus 0 when the file is forgotten. A user's usage
 * is the SUM of their living rows — MediaStore writes here on every put
 * and best-effort releases on delete (see App\Support\Tier).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_storage_ledger')) {
            return;
        }

        Schema::create('as_storage_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId')->index();
            $table->unsignedBigInteger('bytes');
            $table->string('path', 500);
            $table->string('source', 60)->default('');
            $table->tinyInteger('deleteStatus')->default(1);
            $table->timestamps();
            $table->index(['userId', 'deleteStatus']);
            $table->index('path');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_storage_ledger');
    }
};
