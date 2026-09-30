<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Try and Ask Anee (2026-10-01): the public "one free question" flow.
 *
 * as_ask_questions   one visitor's question, from the first words to the
 *                    article that answers it: what Anee said, the farm they
 *                    described, the email the answer went to, and the job
 *                    that wrote the article.
 * as_seo_keywords    the keywords the site writes toward, loaded from
 *                    database/seo/anisenso_keywords.csv and grown from the
 *                    mother app (AniSystem > SEO keywords).
 *
 * The articles themselves are as_site_pages rows in the "questions" section,
 * so the mother's Website pages builder edits them like any guide.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_ask_questions')) {
            Schema::create('as_ask_questions', function (Blueprint $t) {
                $t->id();
                $t->char('token', 40)->unique();
                $t->text('question');
                $t->string('topic', 190)->nullable();
                $t->string('lang', 8)->nullable();
                $t->boolean('isAgri')->nullable();
                $t->text('reply')->nullable();
                $t->string('detectedCrop', 40)->nullable();
                $t->unsignedBigInteger('matchedPageId')->nullable();
                $t->decimal('farmSize', 10, 2)->nullable();
                $t->string('farmUnit', 12)->nullable();
                $t->string('crop', 40)->nullable();
                $t->string('cropLabel', 120)->nullable();
                $t->string('country', 4)->nullable();
                $t->string('province', 80)->nullable();
                $t->string('town', 120)->nullable();
                $t->string('email', 190)->nullable()->index();
                $t->string('status', 16)->default('asked')->index();
                $t->string('answerStatus', 12)->nullable();
                $t->string('answerPhase', 24)->nullable();
                $t->unsignedTinyInteger('answerTry')->nullable();
                $t->timestamp('answerStartedAt')->nullable();
                $t->timestamp('answerBeatAt')->nullable();
                $t->string('answerError', 500)->nullable();
                $t->unsignedBigInteger('pageId')->nullable()->index();
                $t->unsignedBigInteger('crmLeadId')->nullable();
                $t->timestamp('listedAt')->nullable();
                $t->timestamp('emailedAt')->nullable();
                $t->timestamp('openedAt')->nullable();
                $t->string('ip', 45)->nullable();
                $t->string('userAgent', 255)->nullable();
                $t->string('source', 120)->nullable();
                $t->unsignedTinyInteger('deleteStatus')->default(1);
                $t->timestamps();
                $t->index('created_at');
            });
        }

        if (! Schema::hasTable('as_seo_keywords')) {
            Schema::create('as_seo_keywords', function (Blueprint $t) {
                $t->id();
                $t->string('keyword', 191)->unique();
                $t->unsignedInteger('volume')->default(0);
                $t->decimal('cpc', 10, 2)->nullable();
                $t->unsignedSmallInteger('paidDifficulty')->nullable();
                $t->unsignedSmallInteger('seoDifficulty')->nullable();
                $t->string('source', 40)->default('import');
                $t->unsignedInteger('usedCount')->default(0);
                $t->timestamp('lastUsedAt')->nullable();
                $t->unsignedTinyInteger('deleteStatus')->default(1);
                $t->timestamps();
            });
        }

        $csv = database_path('seo/anisenso_keywords.csv');
        if (is_file($csv)) {
            \App\Support\SeoKeywords::importCsv((string) file_get_contents($csv), 'anisenso_keywords.csv');
        }

        // The answer email's template (ask_anee_answer), editable in the mother app.
        if (Schema::hasTable('as_email_templates')) {
            (new \Database\Seeders\AniSystemEmailTemplateSeeder())->run();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_ask_questions');
        Schema::dropIfExists('as_seo_keywords');
    }
};
