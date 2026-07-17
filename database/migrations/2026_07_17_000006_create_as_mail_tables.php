<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_mail_smtp_settings')) {
            Schema::create('as_mail_smtp_settings', function (Blueprint $table) {
                $table->id();
                $table->string('groupKey', 50)->index(); // 'AniSystem' | 'AniSenso' | ...
                $table->string('smtpHost', 255)->nullable();
                $table->integer('smtpPort')->default(587);
                $table->string('smtpUsername', 255)->nullable();
                $table->text('smtpPassword')->nullable(); // stored plain; masked in admin UI (cross-app: no shared APP_KEY)
                $table->string('smtpEncryption', 10)->default('tls'); // tls | ssl | none
                $table->string('smtpFromEmail', 255)->nullable();
                $table->string('smtpFromName', 255)->nullable();
                $table->tinyInteger('isActive')->default(0);
                $table->tinyInteger('isVerified')->default(0);
                $table->timestamp('lastTestedAt')->nullable();
                $table->integer('deleteStatus')->default(1)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('as_email_templates')) {
            Schema::create('as_email_templates', function (Blueprint $table) {
                $table->id();
                $table->string('groupKey', 50)->index(); // 'AniSystem' | 'AniSenso'
                $table->string('templateKey', 80)->index();
                $table->string('templateName', 150);
                $table->string('subject', 255);
                $table->mediumText('bodyHtml');
                $table->text('availableTags')->nullable(); // comma-separated {{tag}} list for the admin UI
                $table->tinyInteger('isActive')->default(1);
                $table->integer('deleteStatus')->default(1)->index();
                $table->timestamps();
            });
        }

        $now = now();

        if (! DB::table('as_mail_smtp_settings')->where('groupKey', 'AniSystem')->where('deleteStatus', 1)->exists()) {
            DB::table('as_mail_smtp_settings')->insert([
                'groupKey' => 'AniSystem',
                'smtpPort' => 587,
                'smtpEncryption' => 'tls',
                'smtpFromName' => 'AniSystem',
                'isActive' => 0,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $commonTags = '{{firstName}}, {{lastName}}, {{email}}, {{siteName}}, {{loginUrl}}';
        $subTags = $commonTags.', {{planName}}, {{price}}, {{orderNumber}}, {{expiresAt}}';
        $btnStyle = 'display:inline-block;padding:12px 28px;background:#f5c518;color:#1a1a1a;font-weight:bold;text-decoration:none;border-radius:8px;';
        $wrap = function (string $inner) use ($btnStyle): string {
            return '<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;padding:24px;color:#1f2937;">'
                .'<h2 style="color:#2d5016;margin:0 0 16px;">{{siteName}}</h2>'
                .$inner
                .'<p style="margin-top:28px;font-size:12px;color:#6b7280;">This email was sent by {{siteName}} — the AniSenso cropping schedule manager.</p>'
                .'</div>';
        };

        $templates = [
            ['registration_welcome', 'Registration Welcome', 'Welcome to {{siteName}}, {{firstName}}!',
                '<p>Hi {{firstName}},</p><p>Welcome to <strong>{{siteName}}</strong>! Your account has been created successfully.</p><p>Once your subscription payment is verified you will get full access to your cropping schedule manager.</p><p><a href="{{loginUrl}}" style="'.$btnStyle.'">Log In</a></p>', $commonTags],
            ['payment_submitted', 'Payment Submitted', 'We received your payment details — Order {{orderNumber}}',
                '<p>Hi {{firstName}},</p><p>Thank you! We received your GCash payment details for order <strong>{{orderNumber}}</strong> ({{planName}} — ₱{{price}}).</p><p>Our team will verify your payment shortly. You will receive another email once your subscription is activated.</p>', $subTags],
            ['payment_approved', 'Payment Approved / Subscription Activated', 'Your {{siteName}} subscription is now active!',
                '<p>Hi {{firstName}},</p><p>Great news — your payment for order <strong>{{orderNumber}}</strong> has been verified.</p><p>Your <strong>{{planName}}</strong> subscription is now active until <strong>{{expiresAt}}</strong>.</p><p><a href="{{loginUrl}}" style="'.$btnStyle.'">Open My Schedule Manager</a></p>', $subTags],
            ['payment_rejected', 'Payment Rejected', 'Payment issue on order {{orderNumber}}',
                '<p>Hi {{firstName}},</p><p>Unfortunately we could not verify your payment for order <strong>{{orderNumber}}</strong>.</p><p>Please review your payment details and submit again, or contact our support team for help.</p><p><a href="{{loginUrl}}" style="'.$btnStyle.'">Review My Account</a></p>', $subTags],
            ['password_reset', 'Password Reset', 'Reset your {{siteName}} password',
                '<p>Hi {{firstName}},</p><p>We received a request to reset your password. Click the button below to choose a new one. This link expires in 60 minutes.</p><p><a href="{{resetUrl}}" style="'.$btnStyle.'">Reset Password</a></p><p>If you did not request this, you can safely ignore this email.</p>', $commonTags.', {{resetUrl}}'],
            ['subscription_expiring', 'Subscription Expiring Soon', 'Your {{siteName}} subscription expires on {{expiresAt}}',
                '<p>Hi {{firstName}},</p><p>Your <strong>{{planName}}</strong> subscription expires on <strong>{{expiresAt}}</strong>.</p><p>Renew now to keep your cropping schedules running without interruption.</p><p><a href="{{loginUrl}}" style="'.$btnStyle.'">Renew Now</a></p>', $subTags],
            ['subscription_expired', 'Subscription Expired', 'Your {{siteName}} subscription has expired',
                '<p>Hi {{firstName}},</p><p>Your <strong>{{planName}}</strong> subscription expired on <strong>{{expiresAt}}</strong>. Your schedules are safe, but access is locked until you renew.</p><p><a href="{{loginUrl}}" style="'.$btnStyle.'">Renew My Subscription</a></p>', $subTags],
            ['subscription_suspended', 'Subscription Suspended', 'Your {{siteName}} subscription has been suspended',
                '<p>Hi {{firstName}},</p><p>Your subscription has been suspended. Please contact our support team to resolve this.</p>', $subTags],
            ['subscription_cancelled', 'Subscription Cancelled', 'Your {{siteName}} subscription has been cancelled',
                '<p>Hi {{firstName}},</p><p>Your <strong>{{planName}}</strong> subscription has been cancelled.</p><p>You can subscribe again anytime from your account page.</p><p><a href="{{loginUrl}}" style="'.$btnStyle.'">My Account</a></p>', $subTags],
            ['subscription_renewed', 'Subscription Renewed', 'Your {{siteName}} subscription has been renewed',
                '<p>Hi {{firstName}},</p><p>Your <strong>{{planName}}</strong> subscription has been renewed and now runs until <strong>{{expiresAt}}</strong>. Thank you!</p><p><a href="{{loginUrl}}" style="'.$btnStyle.'">Open My Schedule Manager</a></p>', $subTags],
            ['contact_received', 'Contact Form Received', 'We received your message — {{siteName}}',
                '<p>Hi {{firstName}},</p><p>Thanks for reaching out! We received your message and will get back to you as soon as possible.</p>', $commonTags],
        ];

        foreach ($templates as [$key, $name, $subject, $body, $tags]) {
            $exists = DB::table('as_email_templates')
                ->where('groupKey', 'AniSystem')->where('templateKey', $key)->where('deleteStatus', 1)->exists();
            if (! $exists) {
                DB::table('as_email_templates')->insert([
                    'groupKey' => 'AniSystem',
                    'templateKey' => $key,
                    'templateName' => $name,
                    'subject' => $subject,
                    'bodyHtml' => $wrap($body),
                    'availableTags' => $tags,
                    'isActive' => 1,
                    'deleteStatus' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_email_templates');
        Schema::dropIfExists('as_mail_smtp_settings');
    }
};
