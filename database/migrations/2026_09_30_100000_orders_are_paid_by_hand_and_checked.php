<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Orders paid by hand (2026-09-30): GCash or a bank transfer in the
 * Philippines, PayPal elsewhere. One row per purchase -- a plan or a pack of
 * Anee's credits -- from the moment the buyer picks how to pay, through the
 * proof they send, Anee's reading of it, to the decision (by the AI for a
 * GCash plan it can vouch for, else by an admin) and, if ever, the revoke.
 *
 * The proof file is kept IN the database (as_order_files): receipts carry
 * names, numbers and balances, so they must never sit at a public address,
 * and Laravel Cloud's own disk is wiped at every deploy. Both apps read it.
 *
 * `effect` remembers what an approval did (the subscription row, the credits)
 * so a revoke can take exactly that back.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_orders')) {
            Schema::create('as_orders', function (Blueprint $table) {
                $table->id();
                $table->string('orderNumber', 24)->unique();
                $table->unsignedBigInteger('userId')->index();
                $table->string('kind', 12);                 // plan | credits
                $table->string('itemKey', 48);              // solo:month, pack:starter
                $table->string('itemName', 120);
                $table->string('tier', 24)->nullable();     // plans: libreAnee | solo | owner
                $table->string('period', 8)->nullable();    // month | year
                $table->unsignedSmallInteger('days')->nullable();
                $table->unsignedSmallInteger('months')->nullable();
                $table->unsignedBigInteger('packId')->nullable();
                $table->unsignedInteger('credits')->nullable();       // a pack's credits, or a plan's bundled credits
                $table->char('currency', 3)->default('PHP');
                $table->decimal('price', 10, 2);
                $table->decimal('fee', 10, 2)->default(0);
                $table->decimal('total', 10, 2);
                $table->string('method', 12);               // gcash | bank | paypal
                $table->string('status', 12)->index();      // awaiting | review | approved | rejected | revoked | cancelled
                // The proof
                $table->string('refNumber', 64)->nullable()->index();
                $table->string('proofKind', 8)->nullable(); // image | pdf | ref
                $table->unsignedBigInteger('proofFileId')->nullable();
                $table->char('proofSha', 64)->nullable()->index();
                $table->string('buyerNote', 500)->nullable();
                $table->dateTime('submittedAt')->nullable();
                // Anee's reading
                $table->string('aiStatus', 12)->nullable(); // pass | fail | unsure | error | skipped
                $table->unsignedTinyInteger('aiScore')->nullable();
                $table->json('aiReport')->nullable();
                $table->dateTime('aiCheckedAt')->nullable();
                // The decision
                $table->string('decidedBy', 80)->nullable();  // 'ai', 'admin:<id>', 'mother:<name>'
                $table->dateTime('approvedAt')->nullable();
                $table->dateTime('rejectedAt')->nullable();
                $table->string('rejectReason', 500)->nullable();
                $table->dateTime('revokedAt')->nullable();
                $table->string('revokedBy', 80)->nullable();
                $table->string('revokeReason', 500)->nullable();
                $table->json('effect')->nullable();
                $table->timestamps();
                $table->index(['userId', 'status']);
            });
        }

        if (! Schema::hasTable('as_order_files')) {
            Schema::create('as_order_files', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('orderId')->index();
                $table->string('mime', 60);
                $table->string('name', 160)->nullable();
                $table->unsignedInteger('size');
                $table->char('sha', 64)->index();       // of the file as uploaded
                $table->binary('bytes');
                $table->timestamp('created_at')->nullable();
            });
            // BLOB tops out at 64 KB; a receipt screenshot is hundreds.
            DB::statement('ALTER TABLE as_order_files MODIFY bytes LONGBLOB NOT NULL');
        }

        // The new order emails, editable in the mother app like the others.
        (new \Database\Seeders\AniSystemEmailTemplateSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('as_order_files');
        Schema::dropIfExists('as_orders');
    }
};
