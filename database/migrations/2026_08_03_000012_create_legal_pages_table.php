<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Editable legal / info pages (Privacy, Terms, Cookies, About) shown in the
 * AniSystem footer and managed from the mother app. Shared `as_*` conventions:
 * camelCase, integer deleteStatus, no FK. Seeded with starter content.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_legal_pages')) {
            Schema::create('as_legal_pages', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 40)->unique();
                $table->string('title', 191);
                $table->longText('body')->nullable();
                $table->integer('sortOrder')->default(0);
                $table->boolean('isPublished')->default(1);
                $table->integer('deleteStatus')->default(1)->index();
                $table->timestamps();
            });
        }

        $now = now();
        $defaults = [
            ['slug' => 'privacy', 'title' => 'Privacy Policy', 'order' => 1, 'body' =>
                '<p>Your privacy matters to us. This policy explains what information AniSystem collects, how we use it, and the choices you have.</p>'
                . '<h3>What we collect</h3><p>Account details you provide (name, email, farm location) and the data you enter about your cropping schedules. We do not sell your personal information.</p>'
                . '<h3>How we use it</h3><p>To run the service, personalise your dashboard and community, and improve AniSystem. Community posts you choose to share are visible to other members.</p>'
                . '<h3>Contact</h3><p>Questions about your data? Reach us through the Support page.</p>'],
            ['slug' => 'terms', 'title' => 'Terms of Service', 'order' => 2, 'body' =>
                '<p>By using AniSystem you agree to these terms. Please read them carefully.</p>'
                . '<h3>Using the service</h3><p>Use AniSystem lawfully and respectfully. You are responsible for the accuracy of the data you enter and for keeping your account secure.</p>'
                . '<h3>Subscriptions</h3><p>Paid features require an active subscription. Prices and inclusions are shown on the subscription page.</p>'
                . '<h3>Community</h3><p>Be kind. Content that is abusive, misleading, or unlawful may be restricted or removed by moderators.</p>'],
            ['slug' => 'cookies', 'title' => 'Cookie Policy', 'order' => 3, 'body' =>
                '<p>AniSystem uses cookies to keep you signed in and to remember your preferences.</p>'
                . '<h3>What cookies we use</h3><p>Essential cookies for login and security, and preference cookies (like your light/dark theme). We do not use cookies to sell your data.</p>'
                . '<h3>Managing cookies</h3><p>You can clear or block cookies in your browser settings, though some features may stop working if you do.</p>'],
            ['slug' => 'about', 'title' => 'About AniSystem', 'order' => 4, 'body' =>
                '<p>AniSystem helps Filipino farmers plan and run a productive cropping season — from lots, workers and activities to reports and a supportive community.</p>'
                . '<h3>Our mission</h3><p>To put practical, data-driven farm planning in every grower\'s hands, in a language and workflow that fits real life on the farm.</p>'
                . '<h3>The community</h3><p>Beyond the tools, AniSystem is a place for co-farmers to swap tips, ask questions, and learn from the AniSenso team.</p>'],
        ];

        foreach ($defaults as $d) {
            $exists = DB::table('as_legal_pages')->where('slug', $d['slug'])->exists();
            if (! $exists) {
                DB::table('as_legal_pages')->insert([
                    'slug' => $d['slug'],
                    'title' => $d['title'],
                    'body' => $d['body'],
                    'sortOrder' => $d['order'],
                    'isPublished' => 1,
                    'deleteStatus' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_legal_pages');
    }
};
