<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public SaaS site
|--------------------------------------------------------------------------
*/

Route::get('/', [App\Http\Controllers\PublicController::class, 'home'])->name('home');
Route::get('/about', [App\Http\Controllers\PublicController::class, 'about'])->name('about');
Route::get('/tutorial', [App\Http\Controllers\PublicController::class, 'tutorial'])->name('tutorial');
Route::get('/contact', [App\Http\Controllers\PublicController::class, 'contact'])->name('contact');
Route::post('/contact', [App\Http\Controllers\PublicController::class, 'submitContact'])->name('contact.submit');

/*
|--------------------------------------------------------------------------
| Auth: login, signup, forgot/reset password
|--------------------------------------------------------------------------
*/

// `no-cache` keeps the browser/bfcache from re-serving these forms with a
// stale CSRF token, which is what produces intermittent 419s on login.
Route::middleware(['guest', 'no-cache'])->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'show'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login.attempt');

    Route::get('/signup', [App\Http\Controllers\Auth\RegisterController::class, 'show'])->name('signup');
    Route::post('/signup', [App\Http\Controllers\Auth\RegisterController::class, 'register'])->name('signup.attempt');

    Route::get('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Purchase / renewal flow (auth, but NOT subscription-gated)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/purchase', [App\Http\Controllers\PurchaseController::class, 'plans'])->name('purchase.plans');
    Route::get('/purchase/thank-you/{subscription}', [App\Http\Controllers\PurchaseController::class, 'thankYou'])->name('purchase.thankyou');
    Route::get('/purchase/{planKey}', [App\Http\Controllers\PurchaseController::class, 'payment'])->name('purchase.payment');
    Route::post('/purchase/{planKey}', [App\Http\Controllers\PurchaseController::class, 'submit'])->name('purchase.submit');

    /*
    |----------------------------------------------------------------------
    | Account (profile, password, subscription) — reachable even when locked
    |----------------------------------------------------------------------
    */
    Route::get('/account', [App\Http\Controllers\AccountController::class, 'index'])->name('account.index');
    Route::put('/account/profile', [App\Http\Controllers\AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::put('/account/password', [App\Http\Controllers\AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::get('/account/subscription', [App\Http\Controllers\AccountController::class, 'subscription'])->name('account.subscription');
    Route::post('/account/subscription/refresh', [App\Http\Controllers\AccountController::class, 'refreshSubscription'])->name('account.subscription.refresh');
});

/*
|--------------------------------------------------------------------------
| Client web app (auth + active subscription required)
|--------------------------------------------------------------------------
| Schedule manager routes mirror the mother system's contract: flat kebab
| URLs, parent schedule via ?scheduleId= (or ?id= for the schedule itself),
| child ids via ?id=, JSON envelope {success, message, data}.
*/

Route::middleware(['auth', 'subscription'])->group(function () {
    Route::get('/app', [App\Http\Controllers\AppController::class, 'dashboard'])->name('app.dashboard');

    // --- Cropping schedules (list / create / hub / settings) ---
    Route::get('/app/sm', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'index'])->name('sm.index');
    Route::get('/app/sm-create', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'create'])->name('sm.create');
    Route::post('/app/sm-store', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'store'])->name('sm.store');
    Route::post('/app/sm-store-wizard', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'storeWizard'])->name('sm.store.wizard');
    Route::get('/app/sm-hub', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'hub'])->name('sm.hub');
    Route::put('/app/sm-update', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'update'])->name('sm.update');
    Route::delete('/app/sm-delete', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'destroy'])->name('sm.destroy');

    // --- Module pages (each takes ?id={scheduleId}) ---
    Route::get('/app/sm-settings', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'settingsPage'])->name('sm.settings');
    Route::get('/app/sm-lots', [App\Http\Controllers\Manager\LotController::class, 'page'])->name('sm.lots');
    Route::get('/app/sm-workers', [App\Http\Controllers\Manager\WorkerController::class, 'page'])->name('sm.workers');
    Route::get('/app/sm-materials', [App\Http\Controllers\Manager\MaterialController::class, 'page'])->name('sm.materials');
    Route::get('/app/sm-services', [App\Http\Controllers\Manager\ServiceController::class, 'page'])->name('sm.services');
    Route::get('/app/sm-documentation', [App\Http\Controllers\Manager\DocumentationController::class, 'page'])->name('sm.documentation');
    Route::get('/app/sm-activities', [App\Http\Controllers\Manager\ActivityController::class, 'page'])->name('sm.activities');
    Route::get('/app/sm-irrigations', [App\Http\Controllers\Manager\IrrigationController::class, 'page'])->name('sm.irrigations');

    // --- Default groupings ---
    Route::post('/app/sm-default-groupings-save', [App\Http\Controllers\Manager\DefaultGroupingController::class, 'save'])->name('sm.default-groupings.save');

    // --- Lots ---
    Route::post('/app/sm-lots-store', [App\Http\Controllers\Manager\LotController::class, 'store'])->name('sm.lots.store');
    Route::put('/app/sm-lots-update', [App\Http\Controllers\Manager\LotController::class, 'update'])->name('sm.lots.update');
    Route::delete('/app/sm-lots-delete', [App\Http\Controllers\Manager\LotController::class, 'destroy'])->name('sm.lots.destroy');

    // --- Workers ---
    Route::post('/app/sm-workers-store', [App\Http\Controllers\Manager\WorkerController::class, 'store'])->name('sm.workers.store');
    Route::put('/app/sm-workers-update', [App\Http\Controllers\Manager\WorkerController::class, 'update'])->name('sm.workers.update');
    Route::delete('/app/sm-workers-delete', [App\Http\Controllers\Manager\WorkerController::class, 'destroy'])->name('sm.workers.destroy');
    Route::get('/app/sm-workers-rules', [App\Http\Controllers\Manager\WorkerController::class, 'rules'])->name('sm.workers.rules');
    Route::post('/app/sm-workers-rules-save', [App\Http\Controllers\Manager\WorkerController::class, 'saveRules'])->name('sm.workers.rules.save');

    // --- Protocol ---
    Route::post('/app/sm-protocol-save', [App\Http\Controllers\Manager\ProtocolController::class, 'save'])->name('sm.protocol.save');
    Route::get('/app/sm-protocol-download', [App\Http\Controllers\Manager\ProtocolController::class, 'download'])->name('sm.protocol.download');

    // --- Materials ---
    Route::post('/app/sm-materials-store', [App\Http\Controllers\Manager\MaterialController::class, 'store'])->name('sm.materials.store');
    Route::put('/app/sm-materials-update', [App\Http\Controllers\Manager\MaterialController::class, 'update'])->name('sm.materials.update');
    Route::delete('/app/sm-materials-delete', [App\Http\Controllers\Manager\MaterialController::class, 'destroy'])->name('sm.materials.destroy');

    // --- Services ---
    Route::post('/app/sm-services-store', [App\Http\Controllers\Manager\ServiceController::class, 'store'])->name('sm.services.store');
    Route::put('/app/sm-services-update', [App\Http\Controllers\Manager\ServiceController::class, 'update'])->name('sm.services.update');
    Route::delete('/app/sm-services-delete', [App\Http\Controllers\Manager\ServiceController::class, 'destroy'])->name('sm.services.destroy');

    // --- Attachments ---
    Route::post('/app/sm-attachments-store', [App\Http\Controllers\Manager\AttachmentController::class, 'store'])->name('sm.attachments.store');
    Route::put('/app/sm-attachments-update', [App\Http\Controllers\Manager\AttachmentController::class, 'update'])->name('sm.attachments.update');
    Route::delete('/app/sm-attachments-delete', [App\Http\Controllers\Manager\AttachmentController::class, 'destroy'])->name('sm.attachments.destroy');

    // --- Critical rules ---
    Route::post('/app/sm-critical-rules-store', [App\Http\Controllers\Manager\CriticalRuleController::class, 'store'])->name('sm.critical-rules.store');
    Route::put('/app/sm-critical-rules-update', [App\Http\Controllers\Manager\CriticalRuleController::class, 'update'])->name('sm.critical-rules.update');
    Route::delete('/app/sm-critical-rules-delete', [App\Http\Controllers\Manager\CriticalRuleController::class, 'destroy'])->name('sm.critical-rules.destroy');
    Route::post('/app/sm-critical-rules-reorder', [App\Http\Controllers\Manager\CriticalRuleController::class, 'reorder'])->name('sm.critical-rules.reorder');

    // --- Activities (main module) ---
    Route::post('/app/sm-activities-store', [App\Http\Controllers\Manager\ActivityController::class, 'store'])->name('sm.activities.store');
    Route::get('/app/sm-activities-show', [App\Http\Controllers\Manager\ActivityController::class, 'show'])->name('sm.activities.show');
    Route::put('/app/sm-activities-update', [App\Http\Controllers\Manager\ActivityController::class, 'update'])->name('sm.activities.update');
    Route::delete('/app/sm-activities-delete', [App\Http\Controllers\Manager\ActivityController::class, 'destroy'])->name('sm.activities.destroy');
    Route::post('/app/sm-activities-image-upload', [App\Http\Controllers\Manager\ActivityController::class, 'uploadImage'])->name('sm.activities.image-upload');
    Route::post('/app/sm-activities-toggle-hidden', [App\Http\Controllers\Manager\ActivityController::class, 'toggleHidden'])->name('sm.activities.toggle-hidden');
    Route::post('/app/sm-activities-duplicate', [App\Http\Controllers\Manager\ActivityController::class, 'duplicate'])->name('sm.activities.duplicate');
    Route::post('/app/sm-activities-set-date', [App\Http\Controllers\Manager\ActivityController::class, 'setDate'])->name('sm.activities.set-date');
    Route::post('/app/sm-activities-reorder', [App\Http\Controllers\Manager\ActivityController::class, 'reorder'])->name('sm.activities.reorder');
    Route::get('/app/sm-activities-export', [App\Http\Controllers\Manager\DocumentController::class, 'export'])->name('sm.activities.export');
    Route::post('/app/sm-activities-restore', [App\Http\Controllers\Manager\ActivityController::class, 'restore'])->name('sm.activities.restore');
    Route::post('/app/sm-activities-to-draft', [App\Http\Controllers\Manager\ActivityController::class, 'toDraft'])->name('sm.activities.to-draft');
    Route::post('/app/sm-activities-from-draft', [App\Http\Controllers\Manager\ActivityController::class, 'fromDraft'])->name('sm.activities.from-draft');
    Route::get('/app/sm-activities-drafts', [App\Http\Controllers\Manager\ActivityController::class, 'listDrafts'])->name('sm.activities.drafts');
    Route::get('/app/sm-activities-readiness', [App\Http\Controllers\Manager\ActivityController::class, 'readiness'])->name('sm.activities.readiness');
    Route::get('/app/sm-activities-labor', [App\Http\Controllers\Manager\ActivityController::class, 'laborSummary'])->name('sm.activities.labor');
    Route::post('/app/sm-activities-date-note-save', [App\Http\Controllers\Manager\ActivityController::class, 'saveDateNote'])->name('sm.activities.date-note.save');
    Route::delete('/app/sm-activities-date-note-delete', [App\Http\Controllers\Manager\ActivityController::class, 'deleteDateNote'])->name('sm.activities.date-note.delete');

    // --- Progress markers ---
    Route::post('/app/sm-markers-save', [App\Http\Controllers\Manager\MarkerController::class, 'save'])->name('sm.markers.save');
    Route::delete('/app/sm-markers-delete', [App\Http\Controllers\Manager\MarkerController::class, 'destroy'])->name('sm.markers.destroy');

    // --- Activity versions ---
    Route::get('/app/sm-activity-versions', [App\Http\Controllers\Manager\ActivityVersionController::class, 'index'])->name('sm.activity-versions.index');
    Route::post('/app/sm-activity-versions-store', [App\Http\Controllers\Manager\ActivityVersionController::class, 'store'])->name('sm.activity-versions.store');
    Route::put('/app/sm-activity-versions-update', [App\Http\Controllers\Manager\ActivityVersionController::class, 'update'])->name('sm.activity-versions.update');
    Route::delete('/app/sm-activity-versions-delete', [App\Http\Controllers\Manager\ActivityVersionController::class, 'destroy'])->name('sm.activity-versions.destroy');
    Route::post('/app/sm-activity-versions-set-active', [App\Http\Controllers\Manager\ActivityVersionController::class, 'setActive'])->name('sm.activity-versions.set-active');
    Route::post('/app/sm-activity-versions-global-note', [App\Http\Controllers\Manager\ActivityVersionController::class, 'setGlobalNote'])->name('sm.activity-versions.global-note');

    // --- Irrigations ---
    Route::post('/app/sm-irrigations-store', [App\Http\Controllers\Manager\IrrigationController::class, 'store'])->name('sm.irrigations.store');
    Route::put('/app/sm-irrigations-update', [App\Http\Controllers\Manager\IrrigationController::class, 'update'])->name('sm.irrigations.update');
    Route::delete('/app/sm-irrigations-delete', [App\Http\Controllers\Manager\IrrigationController::class, 'destroy'])->name('sm.irrigations.destroy');
    Route::post('/app/sm-irrigations-duplicate', [App\Http\Controllers\Manager\IrrigationController::class, 'duplicate'])->name('sm.irrigations.duplicate');
    Route::post('/app/sm-irrigations-reorder', [App\Http\Controllers\Manager\IrrigationController::class, 'reorder'])->name('sm.irrigations.reorder');

    // --- Printable / standalone documents ---
    Route::get('/app/sm-worker-presentation', [App\Http\Controllers\Manager\DocumentController::class, 'workerPresentation'])->name('sm.worker-presentation');
    Route::get('/app/sm-card-viewer', [App\Http\Controllers\Manager\DocumentController::class, 'cardViewer'])->name('sm.card-viewer');
});
