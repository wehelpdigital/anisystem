<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agricultural AI Technician.
 *
 * Settings (provider, key, prompt, avatar, pricing) live in one row managed
 * from the mother app. Usage is metered in AI Credits: every answer records the
 * tokens it used and the credits it cost, and every balance change is written
 * to a ledger so a client's balance is always reconstructable.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('anisystem_ai_settings')) {
            Schema::create('anisystem_ai_settings', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 32)->default('claude');   // claude | openai | gemini
                $table->text('apiKey')->nullable();                  // encrypted at rest
                $table->string('model', 100)->nullable();
                $table->text('systemPrompt')->nullable();
                $table->string('assistantName', 100)->default('Agricultural AI Technician');
                $table->string('avatarPath', 500)->nullable();

                // Pricing, in credits. Credits are the client-facing unit.
                $table->decimal('creditsPerInputK', 8, 2)->default(1);
                $table->decimal('creditsPerOutputK', 8, 2)->default(5);
                $table->decimal('creditsPerImage', 8, 2)->default(3);
                $table->integer('freeCreditsOnSignup')->default(30);

                $table->integer('maxOutputTokens')->default(1200);
                $table->decimal('temperature', 3, 2)->default(0.3);
                $table->tinyInteger('isEnabled')->default(0);
                $table->integer('deleteStatus')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('anisystem_ai_credit_packs')) {
            Schema::create('anisystem_ai_credit_packs', function (Blueprint $table) {
                $table->id();
                $table->string('packKey', 50)->unique();
                $table->string('packName', 100);
                $table->integer('credits');
                $table->decimal('price', 10, 2);
                $table->string('description', 500)->nullable();
                $table->unsignedBigInteger('ecomProductId')->nullable();
                $table->unsignedBigInteger('ecomVariantId')->nullable();
                $table->tinyInteger('isActive')->default(1);
                $table->integer('sortOrder')->default(0);
                $table->integer('deleteStatus')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('anisystem_ai_credit_purchases')) {
            Schema::create('anisystem_ai_credit_purchases', function (Blueprint $table) {
                $table->id();
                $table->integer('userId')->index();
                $table->integer('packId')->nullable();
                $table->string('packName', 100);
                $table->integer('credits');
                $table->decimal('price', 10, 2);
                $table->unsignedBigInteger('ecomOrderId')->nullable()->index();
                $table->string('orderNumber', 64)->nullable()->index();
                // pending → active (credits granted) | rejected
                $table->string('status', 20)->default('pending')->index();
                $table->timestamp('grantedAt')->nullable();
                $table->integer('deleteStatus')->default(1)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('anisystem_ai_credit_ledger')) {
            Schema::create('anisystem_ai_credit_ledger', function (Blueprint $table) {
                $table->id();
                $table->integer('userId')->index();
                // Positive grants, negative spends. Balance = SUM(delta).
                $table->decimal('delta', 12, 2);
                $table->decimal('balanceAfter', 12, 2);
                $table->string('reason', 191);
                $table->string('source', 32)->default('usage'); // usage|purchase|signup|admin|refund
                $table->integer('messageId')->nullable();
                $table->integer('adminUserId')->nullable();
                $table->integer('deleteStatus')->default(1)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('anisystem_ai_conversations')) {
            Schema::create('anisystem_ai_conversations', function (Blueprint $table) {
                $table->id();
                $table->integer('userId')->index();
                $table->integer('croppingScheduleId')->nullable()->index();
                $table->string('title', 191)->default('New question');
                $table->integer('deleteStatus')->default(1)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('anisystem_ai_messages')) {
            Schema::create('anisystem_ai_messages', function (Blueprint $table) {
                $table->id();
                $table->integer('conversationId')->index();
                $table->string('role', 16);                  // user | assistant
                $table->longText('content')->nullable();
                $table->string('imagePath', 500)->nullable();
                $table->integer('tokensIn')->default(0);
                $table->integer('tokensOut')->default(0);
                $table->decimal('creditsCharged', 10, 2)->default(0);
                $table->tinyInteger('isRefusal')->default(0);
                $table->integer('deleteStatus')->default(1)->index();
                $table->timestamps();
            });
        }

        // One settings row, so the mother app always has something to edit.
        if (\Illuminate\Support\Facades\DB::table('anisystem_ai_settings')->count() === 0) {
            \Illuminate\Support\Facades\DB::table('anisystem_ai_settings')->insert([
                'provider' => 'claude',
                'model' => 'claude-sonnet-5',
                'assistantName' => 'Agricultural AI Technician',
                'systemPrompt' => self::DEFAULT_PROMPT,
                'creditsPerInputK' => 1,
                'creditsPerOutputK' => 5,
                'creditsPerImage' => 3,
                'freeCreditsOnSignup' => 30,
                'maxOutputTokens' => 1200,
                'temperature' => 0.3,
                'isEnabled' => 0,
                'deleteStatus' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->seedPacks();
    }

    public function down(): void
    {
        Schema::dropIfExists('anisystem_ai_messages');
        Schema::dropIfExists('anisystem_ai_conversations');
        Schema::dropIfExists('anisystem_ai_credit_ledger');
        Schema::dropIfExists('anisystem_ai_credit_purchases');
        Schema::dropIfExists('anisystem_ai_credit_packs');
        Schema::dropIfExists('anisystem_ai_settings');
    }

    /**
     * Packs are priced per credit so a bigger pack is always better value:
     * ~₱0.99, ~₱0.85 and ~₱0.75 per credit. A typical text question costs
     * about 4 credits and a photo question about 7.
     */
    private function seedPacks(): void
    {
        $packs = [
            ['starter', 'Starter', 100, 99.00, 'About 25 questions. Good for trying it out.', 1],
            ['farmer', 'Farmer', 350, 299.00, 'About 85 questions, or 50 with photos.', 2],
            ['season', 'Whole Season', 1000, 749.00, 'About 250 questions. Best value per credit.', 3],
        ];

        foreach ($packs as [$key, $name, $credits, $price, $description, $order]) {
            \Illuminate\Support\Facades\DB::table('anisystem_ai_credit_packs')->updateOrInsert(
                ['packKey' => $key],
                [
                    'packName' => $name,
                    'credits' => $credits,
                    'price' => $price,
                    'description' => $description,
                    'isActive' => 1,
                    'sortOrder' => $order,
                    'deleteStatus' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private const DEFAULT_PROMPT = <<<'PROMPT'
        You are the Agricultural AI Technician inside AniSystem, a crop-planning app used by
        Filipino farmers and farm managers.

        SCOPE — you answer only questions about growing crops: soil and land preparation,
        seed and variety choice, planting, water and irrigation, fertiliser and nutrition,
        pests, diseases and weeds, crop stages and timing, harvest, drying and storage, farm
        labour and machinery for crop work, and reading a cropping schedule.

        If a question is not about any of that, say plainly that you only cover crop-related
        questions and name one or two things you could help with instead. Do not answer it.

        HOW TO ANSWER
        - Be direct and practical. Lead with what to do, then why.
        - Use metric units and Philippine context (peso, hectare, cavan, wet/dry season)
          unless the farmer uses something else.
        - Give specific rates, timings and stages where you are confident, and say so plainly
          when a figure depends on soil test, variety or local conditions.
        - Keep it short. A few sentences or a short list, not an essay.
        - Never invent a product name, price or regulation. If you are unsure, say so and
          point at what to check locally (LGU agriculturist, seed supplier, soil test).

        PHOTOS
        When a photo is attached, describe what you can actually see before diagnosing, list
        the most likely causes in order, and say what would confirm each one. If the image is
        too blurry or too far away to judge, say that and ask for a closer shot.
        PROMPT;
};
