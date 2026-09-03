<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public SaaS site
|--------------------------------------------------------------------------
*/

// Public, link-based shares (no auth) — the unguessable token is the key.
Route::get('/s/{token}', [App\Http\Controllers\ShareController::class, 'schedule'])->name('share.schedule');
Route::get('/s/{token}/a/{activity}', [App\Http\Controllers\ShareController::class, 'activity'])->name('share.activity');
Route::get('/s/{token}/d/{date}', [App\Http\Controllers\ShareController::class, 'day'])->where('date', '\d{4}-\d{2}-\d{2}')->name('share.day');

// Worker login invite (no auth) — set a password from the emailed link.
// Runtime uploads on Railway live only in storage/app/public (public/storage
// is a committed directory there, not a symlink) — misses fall through to
// this and stream from the real disk. See StorageFallbackController.
// Cookie-free on purpose: these responses carry Cache-Control public/immutable,
// and StartSession would stamp a Set-Cookie onto them — a session cookie inside
// a publicly cacheable object. Media needs no session; the app middlewares
// below it are auth-gated and no-op without one.
Route::get('/storage/{path}', App\Http\Controllers\StorageFallbackController::class)
    ->where('path', '.+')
    ->withoutMiddleware([
        Illuminate\Cookie\Middleware\EncryptCookies::class,
        Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        Illuminate\Session\Middleware\StartSession::class,
        Illuminate\View\Middleware\ShareErrorsFromSession::class,
        App\Http\Middleware\BindSessionToIp::class,
        App\Http\Middleware\EnforceSingleSession::class,
        App\Http\Middleware\UpdateLastSeen::class,
    ])
    ->name('storage.fallback');

/**
 * Which build is actually serving? "The fix is pushed" and "the fix is live"
 * are different claims, and without this the difference is guesswork: it
 * reports whether the deployed source carries a couple of named markers, plus
 * the commit Railway built. Feature names and a short SHA only — nothing that
 * is not already visible in the UI or the repo.
 */
Route::get('/deploy-check', function () {
    $activities = resource_path('views/sm/activities.blade.php');
    $source = is_file($activities) ? (string) file_get_contents($activities) : '';

    return response()->json([
        'commit' => substr((string) env('RAILWAY_GIT_COMMIT_SHA', 'unknown'), 0, 7),
        'builtAt' => (string) env('RAILWAY_DEPLOYMENT_ID', 'unknown'),
        'markers' => [
            'eyeFilterButton' => str_contains($source, 'id="viewFilterBtn"'),
            'dragGrip' => str_contains($source, 'A grip says the card can be dragged'),
            'versionsButton' => str_contains($source, 'id="versionsSheetBtn"'),
        ],
        'viewCacheCompiled' => count(glob(storage_path('framework/views/*.php')) ?: []),
    ]);
})->name('deploy.check');

// A wall post somebody shared outward. Unguessable token, read-only, and
// every way to join in asks the reader to sign in first.
Route::get('/pw/{token}', [App\Http\Controllers\CommunityPublicController::class, 'post'])
    ->where('token', '[A-Za-z0-9]{10,64}')
    ->name('community.public.post');

Route::get('/worker-invite/{token}', [App\Http\Controllers\WorkerInviteController::class, 'show'])->name('worker.invite.show');
Route::post('/worker-invite/{token}', [App\Http\Controllers\WorkerInviteController::class, 'accept'])->name('worker.invite.accept');

Route::get('/', [App\Http\Controllers\PublicController::class, 'home'])->name('home');
Route::get('/about', [App\Http\Controllers\PublicController::class, 'about'])->name('about');

// Editable legal / info pages (Privacy, Terms, Cookies, About) — public.
Route::get('/legal/{slug}', [App\Http\Controllers\LegalController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('legal.show');
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
    // Which hat: shown after login when an account is more than one thing.
    Route::get('/account/choose', [App\Http\Controllers\AccountController::class, 'choose'])->name('account.choose');
    Route::post('/account/choose', [App\Http\Controllers\AccountController::class, 'chooseApply'])->name('account.choose.apply');
    Route::get('/account', [App\Http\Controllers\AccountController::class, 'index'])->name('account.index');
    Route::post('/app/worker-switch-farm', [App\Http\Controllers\AccountController::class, 'switchFarm'])->name('worker.switch');
    Route::put('/account/profile', [App\Http\Controllers\AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::put('/account/password', [App\Http\Controllers\AccountController::class, 'updatePassword'])->name('account.password.update');
    // How the app behaves for this person — accessibility lives here.
    Route::get('/account/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('account.settings');
    Route::get('/account/subscription', [App\Http\Controllers\AccountController::class, 'subscription'])->name('account.subscription');
    Route::post('/account/subscription/refresh', [App\Http\Controllers\AccountController::class, 'refreshSubscription'])->name('account.subscription.refresh');

    // Support tickets (client side).
    Route::get('/app/tutorials', [App\Http\Controllers\TutorialController::class, 'index'])->name('tutorials.index');
    Route::get('/app/notes', [App\Http\Controllers\NotesHubController::class, 'index'])->name('notes.hub');
    // Every picture from every season, the way Global Notes gathers the words.
    Route::get('/app/gallery', [App\Http\Controllers\GalleryHubController::class, 'index'])->name('gallery.hub');
    Route::post('/app/notes-store', [App\Http\Controllers\NotesHubController::class, 'store'])->name('notes.hub.store');
    Route::delete('/app/notes-delete', [App\Http\Controllers\NotesHubController::class, 'destroy'])->name('notes.hub.destroy');
    Route::post('/app/notes-draw', [App\Http\Controllers\NotesHubController::class, 'drawUpload'])->name('notes.hub.draw');
    Route::post('/app/notes-image-upload', [App\Http\Controllers\NotesHubController::class, 'imageUpload'])->name('notes.hub.image-upload');
    Route::post('/app/notes-video-upload', [App\Http\Controllers\NotesHubController::class, 'videoUpload'])->name('notes.hub.video-upload');
    // "How are we doing?" — answered once, or waved away twice.
    Route::post('/app/review', [App\Http\Controllers\ReviewController::class, 'store'])->name('review.store');
    Route::post('/app/review-dismiss', [App\Http\Controllers\ReviewController::class, 'dismiss'])->name('review.dismiss');
    Route::get('/app/support', [App\Http\Controllers\SupportController::class, 'index'])->name('support.index');
    Route::post('/app/support', [App\Http\Controllers\SupportController::class, 'store'])->name('support.store');
    Route::get('/app/support/{id}', [App\Http\Controllers\SupportController::class, 'show'])->whereNumber('id')->name('support.show');
    Route::post('/app/support/{id}/reply', [App\Http\Controllers\SupportController::class, 'reply'])->whereNumber('id')->name('support.reply');

    // Top-bar notification bell.
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/count', [App\Http\Controllers\NotificationController::class, 'count'])->name('notifications.count');
    Route::post('/notifications/read', [App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/seen', [App\Http\Controllers\NotificationController::class, 'markSeen'])->name('notifications.seen');

    // Save a picture or a clip to the device. Re-serves it from this origin
    // so the browser's download attribute is actually honoured.
    Route::get('/app/media/save', App\Http\Controllers\MediaSaveController::class)->name('media.save');

    // Enter a schedule from anywhere: puts the session in the right farm
    // before going there, so a notification about somebody else's schedule
    // does not land on a 404 for a worker who also owns land of their own.
    Route::get('/app/enter/{schedule}', App\Http\Controllers\EnterScheduleController::class)
        ->whereNumber('schedule')->name('sm.enter');
});

/*
|--------------------------------------------------------------------------
| Client web app (auth + active subscription required)
|--------------------------------------------------------------------------
| Schedule manager routes mirror the mother system's contract: flat kebab
| URLs, parent schedule via ?scheduleId= (or ?id= for the schedule itself),
| child ids via ?id=, JSON envelope {success, message, data}.
*/

/*
|--------------------------------------------------------------------------
| Admin panel — the platform seen from above (admins only, no sub gate)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin.panel'])->prefix('admin')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\AdminPanelController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/clients', [App\Http\Controllers\Admin\AdminPanelController::class, 'clients'])->name('admin.clients');
    Route::get('/support', [App\Http\Controllers\Admin\AdminSupportController::class, 'page'])->name('admin.support');

    Route::get('/data/overview', [App\Http\Controllers\Admin\AdminPanelController::class, 'overview'])->name('admin.data.overview');
    Route::get('/data/clients', [App\Http\Controllers\Admin\AdminPanelController::class, 'clientsData'])->name('admin.data.clients');
    Route::get('/data/client/{id}', [App\Http\Controllers\Admin\AdminPanelController::class, 'clientOne'])->whereNumber('id')->name('admin.data.client');
    Route::put('/client/{id}/info', [App\Http\Controllers\Admin\AdminPanelController::class, 'updateInfo'])->whereNumber('id')->name('admin.client.info');
    Route::post('/client/{id}/password-link', [App\Http\Controllers\Admin\AdminPanelController::class, 'sendPasswordLink'])->whereNumber('id')->name('admin.client.password-link');
    Route::put('/client/{id}/password', [App\Http\Controllers\Admin\AdminPanelController::class, 'setPassword'])->whereNumber('id')->name('admin.client.password');
    Route::put('/client/{id}/community-suspend', [App\Http\Controllers\Admin\AdminPanelController::class, 'communitySuspend'])->whereNumber('id')->name('admin.client.suspend');
    Route::post('/client/{id}/credits', [App\Http\Controllers\Admin\AdminPanelController::class, 'adjustCredits'])->whereNumber('id')->name('admin.client.credits');
    Route::post('/client/{id}/impersonate', [App\Http\Controllers\Admin\AdminPanelController::class, 'impersonate'])->whereNumber('id')->name('admin.client.impersonate');

    Route::get('/data/tickets', [App\Http\Controllers\Admin\AdminSupportController::class, 'tickets'])->name('admin.data.tickets');
    Route::get('/data/ticket/{id}', [App\Http\Controllers\Admin\AdminSupportController::class, 'one'])->whereNumber('id')->name('admin.data.ticket');
    Route::post('/ticket/{id}/reply', [App\Http\Controllers\Admin\AdminSupportController::class, 'reply'])->whereNumber('id')->name('admin.ticket.reply');
    Route::put('/ticket/{id}/status', [App\Http\Controllers\Admin\AdminSupportController::class, 'setStatus'])->whereNumber('id')->name('admin.ticket.status');
});

// The way back from looking through a client's eyes. Auth only: the signed-in
// user is the CLIENT right now, and the session key is the credential.
Route::post('/admin/return', [App\Http\Controllers\Admin\AdminPanelController::class, 'stopImpersonating'])
    ->middleware('auth')->name('admin.return');

Route::middleware(['auth', 'subscription'])->group(function () {
    Route::get('/app', [App\Http\Controllers\AppController::class, 'dashboard'])->name('app.dashboard');
    // The storefront's promise, ahead of the storefront.
    Route::view('/app/shop', 'shop.index')->name('shop.index');
    Route::get('/app/weather', [App\Http\Controllers\WeatherController::class, 'forecast'])->name('app.weather');
    Route::get('/app/sm-weather', [App\Http\Controllers\WeatherController::class, 'scheduleForecast'])->name('sm.weather');
    // Weather as a schedule module: the 6-day view plus an hourly tab.
    Route::get('/app/sm-weather-page', [App\Http\Controllers\WeatherController::class, 'page'])->name('sm.weather.page');
    // What the forecast said for one day, kept for reports and the AI.
    Route::get('/app/sm-weather-saved', [App\Http\Controllers\WeatherController::class, 'savedDay'])->name('sm.weather.saved');

    // --- Cropping schedules (list / create / hub / settings) ---
    Route::get('/app/sm', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'index'])->name('sm.index');
    Route::get('/app/sm-create', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'create'])->name('sm.create');
    Route::post('/app/sm-store', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'store'])->name('sm.store');
    Route::post('/app/sm-store-wizard', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'storeWizard'])->name('sm.store.wizard');
    Route::get('/app/sm-hub', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'hub'])->name('sm.hub');
    Route::put('/app/sm-update', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'update'])->name('sm.update');
    Route::delete('/app/sm-delete', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'destroy'])->name('sm.destroy');
    Route::post('/app/sm-duplicate', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'duplicate'])->name('sm.duplicate');

    // --- Module pages (each takes ?id={scheduleId}) ---
    Route::post('/app/sm-digest-test', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'sendTestDigest'])->name('sm.digest.test');
    Route::get('/app/sm-settings', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'settingsPage'])->name('sm.settings');
    Route::post('/app/sm-day-type', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'setDayType'])->name('sm.day-type');
    Route::get('/app/sm-lots', [App\Http\Controllers\Manager\LotController::class, 'page'])->name('sm.lots');
    Route::get('/app/sm-workers', [App\Http\Controllers\Manager\WorkerController::class, 'page'])->name('sm.workers');
    Route::get('/app/sm-inventory', [App\Http\Controllers\Manager\InventoryController::class, 'page'])->name('sm.inventory');
    Route::get('/app/sm-documentation', [App\Http\Controllers\Manager\DocumentationController::class, 'page'])->name('sm.documentation');
    Route::get('/app/sm-activities', [App\Http\Controllers\Manager\ActivityController::class, 'page'])->name('sm.activities');
    Route::get('/app/sm-ai', [App\Http\Controllers\AiController::class, 'schedulePage'])->name('sm.ai');

    // Schedule notebook
    Route::get('/app/sm-notes', [App\Http\Controllers\Manager\NoteController::class, 'page'])->name('sm.notes');
    Route::post('/app/sm-notes-store', [App\Http\Controllers\Manager\NoteController::class, 'store'])->name('sm.notes.store');
    Route::put('/app/sm-notes-update', [App\Http\Controllers\Manager\NoteController::class, 'update'])->name('sm.notes.update');
    Route::delete('/app/sm-notes-delete', [App\Http\Controllers\Manager\NoteController::class, 'destroy'])->name('sm.notes.destroy');
    Route::post('/app/sm-notes-image-upload', [App\Http\Controllers\Manager\NoteController::class, 'uploadImage'])->name('sm.notes.image-upload');
    Route::post('/app/sm-notes-video-upload', [App\Http\Controllers\Manager\NoteController::class, 'uploadVideo'])->name('sm.notes.video-upload');

    // Quick Share — email today's / tomorrow's plan to workers with an email.
    Route::post('/app/sm-quick-share-email', [App\Http\Controllers\Manager\ScheduleShareController::class, 'emailWorkers'])->name('sm.quick-share.email');

    // Quick Capture — save a captured photo group as notes on a schedule.
    Route::post('/app/quick-capture-notes', [App\Http\Controllers\Manager\QuickCaptureController::class, 'storeNotes'])->name('quick-capture.notes');
    Route::post('/app/quick-record-clip', [App\Http\Controllers\Manager\QuickCaptureController::class, 'storeClip'])->name('quick-record.clip');
    Route::get('/app/quick-capture-albums', [App\Http\Controllers\Manager\QuickCaptureController::class, 'albums'])->name('quick-capture.albums');
    Route::post('/app/quick-capture-gallery', [App\Http\Controllers\Manager\QuickCaptureController::class, 'storeGallery'])->name('quick-capture.gallery');

    // --- Default groupings ---
    Route::post('/app/sm-default-groupings-save', [App\Http\Controllers\Manager\DefaultGroupingController::class, 'save'])->name('sm.default-groupings.save');

    // --- Lots ---
    Route::post('/app/sm-lots-store', [App\Http\Controllers\Manager\LotController::class, 'store'])->name('sm.lots.store');
    Route::put('/app/sm-lots-update', [App\Http\Controllers\Manager\LotController::class, 'update'])->name('sm.lots.update');
    Route::delete('/app/sm-lots-delete', [App\Http\Controllers\Manager\LotController::class, 'destroy'])->name('sm.lots.destroy');
    // Where a lot is. Posted by the map the moment a pin goes down.
    Route::post('/app/sm-lots-pin', [App\Http\Controllers\Manager\LotController::class, 'pin'])->name('sm.lots.pin');
    // One lot's own map — its own page, not the Maps module wearing a label.
    Route::get('/app/sm-lot-map', [App\Http\Controllers\Manager\LotController::class, 'map'])->name('sm.lots.map');

    // --- Workers ---
    Route::post('/app/sm-workers-store', [App\Http\Controllers\Manager\WorkerController::class, 'store'])->name('sm.workers.store');
    Route::put('/app/sm-workers-update', [App\Http\Controllers\Manager\WorkerController::class, 'update'])->name('sm.workers.update');
    Route::delete('/app/sm-workers-delete', [App\Http\Controllers\Manager\WorkerController::class, 'destroy'])->name('sm.workers.destroy');
    Route::get('/app/sm-workers-rules', [App\Http\Controllers\Manager\WorkerController::class, 'rules'])->name('sm.workers.rules');
    Route::post('/app/sm-workers-rules-save', [App\Http\Controllers\Manager\WorkerController::class, 'saveRules'])->name('sm.workers.rules.save');
    // --- Inventory: the shelf, and every change to it ---
    Route::get('/app/sm-inventory-list', [App\Http\Controllers\Manager\InventoryController::class, 'listAll'])->name('sm.inventory.list');
    Route::post('/app/sm-inventory-store', [App\Http\Controllers\Manager\InventoryController::class, 'store'])->name('sm.inventory.store');
    Route::put('/app/sm-inventory-update', [App\Http\Controllers\Manager\InventoryController::class, 'update'])->name('sm.inventory.update');
    Route::delete('/app/sm-inventory-delete', [App\Http\Controllers\Manager\InventoryController::class, 'destroy'])->name('sm.inventory.destroy');
    Route::post('/app/sm-inventory-move', [App\Http\Controllers\Manager\InventoryController::class, 'moveStock'])->name('sm.inventory.move');
    Route::post('/app/sm-inventory-restart', [App\Http\Controllers\Manager\InventoryController::class, 'restart'])->name('sm.inventory.restart');
    Route::delete('/app/sm-inventory-move-delete', [App\Http\Controllers\Manager\InventoryController::class, 'deleteMove'])->name('sm.inventory.move.delete');
    Route::post('/app/sm-inventory-move-update', [App\Http\Controllers\Manager\InventoryController::class, 'updateMove'])->name('sm.inventory.move.update');

    Route::post('/app/sm-workers-access-grant', [App\Http\Controllers\Manager\WorkerAccessController::class, 'grant'])->name('sm.workers.access.grant');
    Route::post('/app/sm-workers-access-password', [App\Http\Controllers\Manager\WorkerAccessController::class, 'setPassword'])->name('sm.workers.access.password');
    Route::delete('/app/sm-workers-access-revoke', [App\Http\Controllers\Manager\WorkerAccessController::class, 'revoke'])->name('sm.workers.access.revoke');

    // --- Schedule team group chat ---
    Route::get('/app/sm-chat', [App\Http\Controllers\Manager\ScheduleChatController::class, 'messages'])->name('sm.chat');
    Route::get('/app/sm-chat-members', [App\Http\Controllers\Manager\ScheduleChatController::class, 'members'])->name('sm.chat.members');
    Route::post('/app/sm-chat-send', [App\Http\Controllers\Manager\ScheduleChatController::class, 'send'])->name('sm.chat.send');
    // Who has seen what: posted when the thread is actually on screen.
    Route::post('/app/sm-chat-seen', [App\Http\Controllers\Manager\ScheduleChatController::class, 'seen'])->name('sm.chat.seen');
    // A keystroke's heartbeat: a 4s cache flag the poll reads back as "…is typing".
    Route::post('/app/sm-chat-typing', [App\Http\Controllers\Manager\ScheduleChatController::class, 'typing'])->name('sm.chat.typing');

    // --- Schedule team collaborative whiteboard ---
    Route::get('/app/sm-board', [App\Http\Controllers\Manager\ScheduleBoardController::class, 'events'])->name('sm.board');
    Route::post('/app/sm-board-push', [App\Http\Controllers\Manager\ScheduleBoardController::class, 'push'])->name('sm.board.push');
    Route::get('/app/sm-board-pages', [App\Http\Controllers\Manager\ScheduleBoardController::class, 'pages'])->name('sm.board.pages');
    Route::post('/app/sm-board-page', [App\Http\Controllers\Manager\ScheduleBoardController::class, 'createPage'])->name('sm.board.page-create');
    Route::post('/app/sm-board-save-notes', [App\Http\Controllers\Manager\ScheduleBoardController::class, 'saveToNotes'])->name('sm.board.save-notes');
    Route::post('/app/sm-board-undo', [App\Http\Controllers\Manager\ScheduleBoardController::class, 'undo'])->name('sm.board.undo');

    // The Collab Room's shared photo: one picture, the whole team's pens.
    Route::get('/app/sm-photo', [App\Http\Controllers\Manager\SchedulePhotoController::class, 'state'])->name('sm.photo');
    Route::post('/app/sm-photo-set', [App\Http\Controllers\Manager\SchedulePhotoController::class, 'setPhoto'])->name('sm.photo.set');
    Route::post('/app/sm-photo-push', [App\Http\Controllers\Manager\SchedulePhotoController::class, 'push'])->name('sm.photo.push');
    Route::post('/app/sm-photo-undo', [App\Http\Controllers\Manager\SchedulePhotoController::class, 'undo'])->name('sm.photo.undo');
    Route::post('/app/sm-photo-save', [App\Http\Controllers\Manager\SchedulePhotoController::class, 'save'])->name('sm.photo.save');
    // --- Collab Room map (persistent shapes + ephemeral live positions) ---
    Route::get('/app/sm-map', [App\Http\Controllers\Manager\ScheduleMapController::class, 'objects'])->name('sm.map');
    Route::post('/app/sm-map-push', [App\Http\Controllers\Manager\ScheduleMapController::class, 'push'])->name('sm.map.push');
    Route::delete('/app/sm-map-remove', [App\Http\Controllers\Manager\ScheduleMapController::class, 'remove'])->name('sm.map.remove');
    Route::post('/app/sm-map-update', [App\Http\Controllers\Manager\ScheduleMapController::class, 'update'])->name('sm.map.update');
    Route::post('/app/sm-map-clear', [App\Http\Controllers\Manager\ScheduleMapController::class, 'clear'])->name('sm.map.clear');
    Route::post('/app/sm-map-loc', [App\Http\Controllers\Manager\ScheduleMapController::class, 'location'])->name('sm.map.loc');
    Route::post('/app/sm-map-trace', [App\Http\Controllers\Manager\ScheduleMapController::class, 'trace'])->name('sm.map.trace');
    Route::get('/app/sm-map-saves', [App\Http\Controllers\Manager\ScheduleMapController::class, 'saves'])->name('sm.map.saves');
    Route::get('/app/sm-map-thumb', [App\Http\Controllers\Manager\ScheduleMapController::class, 'thumb'])->name('sm.map.thumb');
    Route::get('/app/sm-map-basemap', [App\Http\Controllers\Manager\ScheduleMapController::class, 'basemap'])->name('sm.map.basemap');
    // The map as its own module in the schedule hub (the Collab Room embeds
    // the same partial as a tab).
    // "How to use" — one page per module per device, written in the mother app.
    Route::get('/app/help/{module}', [App\Http\Controllers\HelpController::class, 'show'])->name('help.show');
    // Gallery: albums a grower makes and names, and the pictures in them.
    Route::get('/app/sm-activity-advanced', [App\Http\Controllers\Manager\ActivityController::class, 'advanced'])->name('sm.activity.advanced');
    // What this season already has, for any composer asking for an attachment.
    // Read-only, so it rides the same GET gate every other listing does.
    Route::get('/app/sm-media-picker', [App\Http\Controllers\Manager\MediaPickerController::class, 'index'])->name('sm.media-picker');
    Route::post('/app/sm-media-poster', [App\Http\Controllers\Manager\MediaPickerController::class, 'poster'])->name('sm.media-picker.poster');
    Route::post('/app/sm-media-poster-save', [App\Http\Controllers\Manager\MediaPickerController::class, 'posterSave'])->name('sm.media-picker.poster-save');

    Route::get('/app/sm-gallery', [App\Http\Controllers\Manager\GalleryController::class, 'page'])->name('sm.gallery');
    Route::post('/app/sm-gallery-album', [App\Http\Controllers\Manager\GalleryController::class, 'albumSave'])->name('sm.gallery.album.save');
    Route::delete('/app/sm-gallery-album', [App\Http\Controllers\Manager\GalleryController::class, 'albumDestroy'])->name('sm.gallery.album.destroy');
    Route::post('/app/sm-gallery-images', [App\Http\Controllers\Manager\GalleryController::class, 'imageStore'])->name('sm.gallery.image.store');
    Route::post('/app/sm-gallery-move', [App\Http\Controllers\Manager\GalleryController::class, 'imageMove'])->name('sm.gallery.image.move');
    // Renaming is a write like moving: same gate, one field of its own.
    Route::post('/app/sm-gallery-rename', [App\Http\Controllers\Manager\GalleryController::class, 'imageRename'])->name('sm.gallery.image.rename');
    Route::delete('/app/sm-gallery-images', [App\Http\Controllers\Manager\GalleryController::class, 'imageDestroy'])->name('sm.gallery.image.destroy');
    // Growth Stages: what each lot's crop is doing, read off its own day count.
    Route::get('/app/sm-growth', [App\Http\Controllers\Manager\GrowthStageController::class, 'page'])->name('sm.growth');
    // Media Box: every picture and video this schedule has, in one place.
    Route::get('/app/sm-media', [App\Http\Controllers\Manager\MediaBoxController::class, 'page'])->name('sm.media');
    Route::get('/app/sm-draw', [App\Http\Controllers\Manager\ScheduleDrawController::class, 'page'])->name('sm.draw');
    Route::get('/app/sm-draw-one', [App\Http\Controllers\Manager\ScheduleDrawController::class, 'one'])->name('sm.draw.one');
    Route::post('/app/sm-draw-save', [App\Http\Controllers\Manager\ScheduleDrawController::class, 'save'])->name('sm.draw.save');
    Route::delete('/app/sm-draw-delete', [App\Http\Controllers\Manager\ScheduleDrawController::class, 'remove'])->name('sm.draw.destroy');
    Route::get('/app/sm-maps', [App\Http\Controllers\Manager\ScheduleMapController::class, 'page'])->name('sm.maps');
    Route::post('/app/sm-map-save', [App\Http\Controllers\Manager\ScheduleMapController::class, 'saveMap'])->name('sm.map.save');
    Route::post('/app/sm-map-load', [App\Http\Controllers\Manager\ScheduleMapController::class, 'loadSave'])->name('sm.map.load');
    // Drawing sessions: a fresh page 1 per session, past drawings kept as drafts.
    Route::post('/app/sm-board-open', [App\Http\Controllers\Manager\ScheduleBoardController::class, 'open'])->name('sm.board.open');
    Route::post('/app/sm-board-heartbeat', [App\Http\Controllers\Manager\ScheduleBoardController::class, 'heartbeat'])->name('sm.board.heartbeat');
    Route::get('/app/sm-board-drafts', [App\Http\Controllers\Manager\ScheduleBoardController::class, 'drafts'])->name('sm.board.drafts');
    Route::post('/app/sm-board-draft-open', [App\Http\Controllers\Manager\ScheduleBoardController::class, 'openDraft'])->name('sm.board.draft-open');

    // --- Collab Room (shared team workspace: chat + drawing + activities + AI) ---
    Route::get('/app/sm-collab', [App\Http\Controllers\Manager\CollabRoomController::class, 'page'])->name('sm.collab');
    Route::post('/app/sm-collab-recording', [App\Http\Controllers\Manager\CollabRoomController::class, 'storeRecording'])->name('sm.collab.recording');
    Route::get('/app/sm-ai-group', [App\Http\Controllers\Manager\ScheduleAiController::class, 'messages'])->name('sm.ai.group.messages');
    Route::post('/app/sm-ai-group-ask', [App\Http\Controllers\Manager\ScheduleAiController::class, 'ask'])->name('sm.ai.group.ask');
    Route::get('/app/sm-ai-group-sessions', [App\Http\Controllers\Manager\ScheduleAiController::class, 'sessions'])->name('sm.ai.group.sessions');
    Route::post('/app/sm-ai-group-session', [App\Http\Controllers\Manager\ScheduleAiController::class, 'createSession'])->name('sm.ai.group.session-create');
    Route::post('/app/sm-ai-group-session-note', [App\Http\Controllers\Manager\ScheduleAiController::class, 'saveSessionNote'])->name('sm.ai.group.session-note');

    // --- Collab Room calls (LiveKit media + Pusher ring signalling) ---
    Route::post('/app/sm-call-token', [App\Http\Controllers\Manager\ScheduleCallController::class, 'token'])->name('sm.call.token');
    Route::post('/app/sm-call-ring', [App\Http\Controllers\Manager\ScheduleCallController::class, 'ring'])->name('sm.call.ring');
    Route::post('/app/sm-call-end', [App\Http\Controllers\Manager\ScheduleCallController::class, 'end'])->name('sm.call.end');
    Route::post('/app/sm-call-mute', [App\Http\Controllers\Manager\ScheduleCallController::class, 'mute'])->name('sm.call.mute');

    // --- Protocol ---
    Route::post('/app/sm-protocol-save', [App\Http\Controllers\Manager\ProtocolController::class, 'save'])->name('sm.protocol.save');
    Route::get('/app/sm-protocol-download', [App\Http\Controllers\Manager\ProtocolController::class, 'download'])->name('sm.protocol.download');


    // --- Attachments ---
    Route::post('/app/sm-attachments-store', [App\Http\Controllers\Manager\AttachmentController::class, 'store'])->name('sm.attachments.store');
    Route::put('/app/sm-attachments-update', [App\Http\Controllers\Manager\AttachmentController::class, 'update'])->name('sm.attachments.update');
    Route::delete('/app/sm-attachments-delete', [App\Http\Controllers\Manager\AttachmentController::class, 'destroy'])->name('sm.attachments.destroy');

    // --- Critical rules ---
    Route::post('/app/sm-critical-rules-store', [App\Http\Controllers\Manager\CriticalRuleController::class, 'store'])->name('sm.critical-rules.store');
    Route::put('/app/sm-critical-rules-update', [App\Http\Controllers\Manager\CriticalRuleController::class, 'update'])->name('sm.critical-rules.update');
    Route::delete('/app/sm-critical-rules-delete', [App\Http\Controllers\Manager\CriticalRuleController::class, 'destroy'])->name('sm.critical-rules.destroy');
    Route::post('/app/sm-critical-rules-reorder', [App\Http\Controllers\Manager\CriticalRuleController::class, 'reorder'])->name('sm.critical-rules.reorder');

    // --- Documentation entries (unified: introduction / critical rule / custom tag) ---
    Route::post('/app/sm-doc-entries-store', [App\Http\Controllers\Manager\DocEntryController::class, 'store'])->name('sm.doc-entries.store');
    Route::post('/app/sm-doc-entries-update', [App\Http\Controllers\Manager\DocEntryController::class, 'update'])->name('sm.doc-entries.update');
    Route::delete('/app/sm-doc-entries-delete', [App\Http\Controllers\Manager\DocEntryController::class, 'destroy'])->name('sm.doc-entries.destroy');
    Route::post('/app/sm-doc-tags-store', [App\Http\Controllers\Manager\DocEntryController::class, 'storeTag'])->name('sm.doc-tags.store');

    // --- Activities (main module) ---
    Route::post('/app/sm-activities-store', [App\Http\Controllers\Manager\ActivityController::class, 'store'])->name('sm.activities.store');
    Route::get('/app/sm-activities-show', [App\Http\Controllers\Manager\ActivityController::class, 'show'])->name('sm.activities.show');
    Route::put('/app/sm-activities-update', [App\Http\Controllers\Manager\ActivityController::class, 'update'])->name('sm.activities.update');
    Route::delete('/app/sm-activities-delete', [App\Http\Controllers\Manager\ActivityController::class, 'destroy'])->name('sm.activities.destroy');
    Route::post('/app/sm-activities-image-upload', [App\Http\Controllers\Manager\ActivityController::class, 'uploadImage'])->name('sm.activities.image-upload');
    Route::post('/app/sm-activities-toggle-hidden', [App\Http\Controllers\Manager\ActivityController::class, 'toggleHidden'])->name('sm.activities.toggle-hidden');
    Route::post('/app/sm-activities-marker', [App\Http\Controllers\Manager\ActivityController::class, 'setMarker'])->name('sm.activities.marker');
    Route::post('/app/sm-activities-toggle-done', [App\Http\Controllers\Manager\ActivityController::class, 'toggleDone'])->name('sm.activities.toggle-done');
    Route::post('/app/sm-activities-append-note', [App\Http\Controllers\Manager\ActivityController::class, 'appendNote'])->name('sm.activities.append-note');

    /* Sending a day, or one job, to the people who have to do it. The morning
       digest goes out on its own; this is an owner standing on the board
       deciding that these people had better know about Thursday now. */
    Route::get('/app/sm-email-audience', [App\Http\Controllers\Manager\ScheduleEmailController::class, 'audience'])->name('sm.email.audience');
    Route::post('/app/sm-email-day', [App\Http\Controllers\Manager\ScheduleEmailController::class, 'sendDay'])->name('sm.email.day');
    Route::post('/app/sm-email-activity', [App\Http\Controllers\Manager\ScheduleEmailController::class, 'sendActivity'])->name('sm.email.activity');
    Route::post('/app/sm-activities-duplicate', [App\Http\Controllers\Manager\ActivityController::class, 'duplicate'])->name('sm.activities.duplicate');
    Route::post('/app/sm-activities-set-date', [App\Http\Controllers\Manager\ActivityController::class, 'setDate'])->name('sm.activities.set-date');
    Route::post('/app/sm-activities-reorder', [App\Http\Controllers\Manager\ActivityController::class, 'reorder'])->name('sm.activities.reorder');
    Route::get('/app/sm-activities-export', [App\Http\Controllers\Manager\DocumentController::class, 'export'])->name('sm.activities.export');
    Route::post('/app/sm-activities-restore', [App\Http\Controllers\Manager\ActivityController::class, 'restore'])->name('sm.activities.restore');
    Route::post('/app/sm-activities-to-draft', [App\Http\Controllers\Manager\ActivityController::class, 'toDraft'])->name('sm.activities.to-draft');
    Route::post('/app/sm-activities-from-draft', [App\Http\Controllers\Manager\ActivityController::class, 'fromDraft'])->name('sm.activities.from-draft');
    Route::get('/app/sm-activities-drafts', [App\Http\Controllers\Manager\ActivityController::class, 'listDrafts'])->name('sm.activities.drafts');
    Route::get('/app/sm-activities-readiness', [App\Http\Controllers\Manager\ActivityController::class, 'readiness'])->name('sm.activities.readiness');

    // --- Agricultural AI Technician + AI Credits ---
    Route::get('/app/ai', [App\Http\Controllers\AiController::class, 'index'])->name('ai.index');
    Route::post('/app/ai-ask', [App\Http\Controllers\AiController::class, 'ask'])->name('ai.ask');
    // What attaching a plan to a question would cost, asked before it is spent.
    Route::get('/app/ai-plan-preview', [App\Http\Controllers\AiController::class, 'planPreview'])->name('ai.plan.preview');
    Route::post('/app/ai-photo', [App\Http\Controllers\AiController::class, 'uploadImage'])->name('ai.photo');
    // "Ask the AI about this" from a picture the app is already showing.
    Route::post('/app/ai-photo-existing', [App\Http\Controllers\AiController::class, 'attachExisting'])->name('ai.photo.existing');
    Route::post('/app/ai-conversation-new', [App\Http\Controllers\AiController::class, 'newConversation'])->name('ai.conversation.new');
    // The floating chat's doors: list old chats, replay one, file one away.
    Route::get('/app/ai-conversations', [App\Http\Controllers\AiController::class, 'conversations'])->name('ai.conversations');
    Route::get('/app/ai-transcript', [App\Http\Controllers\AiController::class, 'transcript'])->name('ai.transcript');
    Route::post('/app/ai-conversation-note', [App\Http\Controllers\AiController::class, 'saveToNote'])->name('ai.conversation.note');
    // The same chat, kept where a chat that belongs to no season belongs.
    Route::post('/app/ai-conversation-global-note', [App\Http\Controllers\AiController::class, 'saveToGlobalNote'])->name('ai.conversation.global-note');
    // What the "attach to a task" sheet asks: which seasons, and what is on
    // a day of one.
    Route::get('/app/ai-attach-options', [App\Http\Controllers\AiController::class, 'attachOptions'])->name('ai.attach.options');
    // The technician, opened from the homepage: no season to attach, and the
    // notebook it keeps is the global one.
    Route::get('/app/ai-home', [App\Http\Controllers\AiController::class, 'home'])->name('ai.home');
    Route::delete('/app/ai-conversation-delete', [App\Http\Controllers\AiController::class, 'deleteConversation'])->name('ai.conversation.delete');
    Route::post('/app/ai-conversation-rename', [App\Http\Controllers\AiController::class, 'renameConversation'])->name('ai.conversation.rename');
    Route::post('/app/ai-conversation-link', [App\Http\Controllers\AiController::class, 'linkConversation'])->name('ai.conversation.link');
    Route::get('/app/ai-credits', [App\Http\Controllers\AiCreditController::class, 'index'])->name('ai.credits');
    Route::get('/app/ai-credits/{packKey}', [App\Http\Controllers\AiCreditController::class, 'payment'])->name('ai.credits.payment');
    Route::post('/app/ai-credits/{packKey}', [App\Http\Controllers\AiCreditController::class, 'submit'])->name('ai.credits.submit');

    // --- Community: the wall, and the plans members publish from it ---
    Route::get('/app/community', [App\Http\Controllers\CommunityController::class, 'feed'])->name('community.index');
    Route::get('/app/community/co-farmers', [App\Http\Controllers\CommunityConnectController::class, 'cofarmers'])->name('community.cofarmers');
    Route::get('/app/community/co-farmers-list', [App\Http\Controllers\CommunityConnectController::class, 'cofarmersList'])->name('community.cofarmers.list');
    Route::get('/app/community/feed-more', [App\Http\Controllers\CommunityController::class, 'feedMore'])->name('community.feed-more');
    Route::get('/app/community/hashtag/{tag}', [App\Http\Controllers\CommunityController::class, 'hashtag'])->name('community.hashtag');
    Route::get('/app/community/location/{slug}', [App\Http\Controllers\CommunityController::class, 'location'])->name('community.location');
    Route::get('/app/community/blog', [App\Http\Controllers\CommunityBlogController::class, 'index'])->name('community.blog');
    Route::get('/app/community/blog/{id}', [App\Http\Controllers\CommunityBlogController::class, 'show'])->whereNumber('id')->name('community.blog.show');
    Route::post('/app/community/blog/{id}/comment', [App\Http\Controllers\CommunityBlogController::class, 'comment'])->whereNumber('id')->name('community.blog.comment');
    Route::get('/app/community/mention-search', [App\Http\Controllers\CommunityConnectController::class, 'mentionSearch'])->name('community.mention-search');
    Route::post('/app/community/status', [App\Http\Controllers\CommunityConnectController::class, 'updateStatus'])->name('community.status.update');
    Route::post('/app/account/avatar', [App\Http\Controllers\AccountController::class, 'updateAvatar'])->name('account.avatar');

    // Direct messages (Messenger-style dock)
    Route::get('/app/community/messages', [App\Http\Controllers\CommunityMessageController::class, 'threads'])->name('community.messages.threads');
    Route::get('/app/community/messages/unread', [App\Http\Controllers\CommunityMessageController::class, 'unreadCount'])->name('community.messages.unread');
    Route::get('/app/community/messages/poll', [App\Http\Controllers\CommunityMessageController::class, 'poll'])->name('community.messages.poll');
    Route::get('/app/community/messages/{userId}', [App\Http\Controllers\CommunityMessageController::class, 'thread'])->whereNumber('userId')->name('community.messages.thread');
    Route::post('/app/community/messages/{userId}', [App\Http\Controllers\CommunityMessageController::class, 'send'])->whereNumber('userId')->name('community.messages.send');
    Route::post('/app/community/messages/{userId}/typing', [App\Http\Controllers\CommunityMessageController::class, 'typing'])->whereNumber('userId')->name('community.messages.typing');
    Route::get('/app/community-plan', [App\Http\Controllers\CommunityController::class, 'show'])->name('community.show');
    Route::post('/app/community-publish', [App\Http\Controllers\CommunityController::class, 'togglePublish'])->name('community.publish');
    Route::post('/app/community-comment', [App\Http\Controllers\CommunityController::class, 'comment'])->name('community.comment');
    Route::delete('/app/community-comment-delete', [App\Http\Controllers\CommunityController::class, 'deleteComment'])->name('community.comment.delete');
    Route::post('/app/community-rate', [App\Http\Controllers\CommunityController::class, 'rate'])->name('community.rate');

    // One post, on a page of its own — what a share's "view original"
    // points at, and what a link to a post can honestly be.
    Route::get('/app/community/post/{id}', [App\Http\Controllers\CommunityController::class, 'post'])->whereNumber('id')->name('community.post.show');

    // "This does not belong here" — taken here, read and acted on in the
    // mother app, which is where moderation lives.
    Route::post('/app/community/report', [App\Http\Controllers\CommunityReportController::class, 'store'])->name('community.report');

    // --- Community: groups (topics + replies) ---
    Route::get('/app/community/groups', [App\Http\Controllers\CommunityGroupController::class, 'index'])->name('community.groups.index');
    // The list's next page of cards, the way a room pages its posts.
    Route::get('/app/community/groups-page', [App\Http\Controllers\CommunityGroupController::class, 'groupsPage'])->name('community.groups.page');
    // Which rooms you are in — asked by a page the back button restored,
    // whose Join buttons cannot know what happened while it was away.
    Route::get('/app/community/groups-mine', [App\Http\Controllers\CommunityGroupController::class, 'myGroups'])->name('community.groups.mine');
    Route::post('/app/community/groups', [App\Http\Controllers\CommunityGroupController::class, 'store'])->name('community.groups.store');
    Route::post('/app/community/groups/{id}/edit', [App\Http\Controllers\CommunityGroupController::class, 'update'])->whereNumber('id')->name('community.groups.update');
    Route::get('/app/community/groups/{id}', [App\Http\Controllers\CommunityGroupController::class, 'show'])->whereNumber('id')->name('community.groups.show');
    Route::get('/app/community/groups/{id}/posts', [App\Http\Controllers\CommunityGroupController::class, 'posts'])->whereNumber('id')->name('community.groups.posts');
    Route::post('/app/community/groups/{id}/join', [App\Http\Controllers\CommunityGroupController::class, 'join'])->whereNumber('id')->name('community.groups.join');
    Route::post('/app/community/groups/{id}/leave', [App\Http\Controllers\CommunityGroupController::class, 'leave'])->whereNumber('id')->name('community.groups.leave');
    Route::post('/app/community/groups/{id}/post', [App\Http\Controllers\CommunityGroupController::class, 'storePost'])->whereNumber('id')->name('community.groups.post');
    Route::get('/app/community/groups/{id}/chat', [App\Http\Controllers\CommunityGroupController::class, 'chatMessages'])->whereNumber('id')->name('community.groups.chat');
    Route::get('/app/community/groups/{id}/chat-members', [App\Http\Controllers\CommunityGroupController::class, 'chatMembers'])->whereNumber('id')->name('community.groups.chat.members');
    Route::post('/app/community/groups/{id}/chat', [App\Http\Controllers\CommunityGroupController::class, 'chatSend'])->whereNumber('id')->name('community.groups.chat.send');
    // The door of a private discussion: who is waiting, who keeps the keys,
    // and who is shown out. Every one of these checks standing for itself.
    Route::get('/app/community/groups/{id}/requests', [App\Http\Controllers\CommunityGroupController::class, 'joinRequests'])->whereNumber('id')->name('community.groups.requests');
    Route::post('/app/community/groups/{id}/requests', [App\Http\Controllers\CommunityGroupController::class, 'decideRequest'])->whereNumber('id')->name('community.groups.requests.decide');
    Route::post('/app/community/groups/{id}/role', [App\Http\Controllers\CommunityGroupController::class, 'setRole'])->whereNumber('id')->name('community.groups.role');
    Route::post('/app/community/groups/{id}/remove', [App\Http\Controllers\CommunityGroupController::class, 'removeMember'])->whereNumber('id')->name('community.groups.remove');
    Route::post('/app/community/posts/{postId}/reply', [App\Http\Controllers\CommunityGroupController::class, 'storeReply'])->whereNumber('postId')->name('community.groups.reply');
    Route::delete('/app/community/posts/{postId}', [App\Http\Controllers\CommunityGroupController::class, 'deletePost'])->whereNumber('postId')->name('community.groups.post.delete');
    Route::post('/app/community/react', [App\Http\Controllers\CommunityGroupController::class, 'react'])->name('community.react');
    Route::get('/app/community/gif-search', [App\Http\Controllers\CommunityGroupController::class, 'gifSearch'])->name('community.gif-search');

    // --- Community: co-farmer connections (friend requests) ---
    Route::get('/app/community/members', [App\Http\Controllers\CommunityConnectController::class, 'members'])->name('community.connect.members');
    Route::get('/app/community/requests', [App\Http\Controllers\CommunityConnectController::class, 'requests'])->name('community.connect.requests');

    // Following, keeping and passing on.
    Route::post('/app/community/follow/{userId}', [App\Http\Controllers\CommunitySocialController::class, 'follow'])->whereNumber('userId')->name('community.follow');
    Route::post('/app/community/bookmark/{postId}', [App\Http\Controllers\CommunitySocialController::class, 'bookmark'])->whereNumber('postId')->name('community.bookmark');
    // Reels: sixty seconds, filling the phone.
    Route::post('/app/community/reels', [App\Http\Controllers\ReelController::class, 'store'])->name('community.reels.store');
    Route::get('/app/community/reels', [App\Http\Controllers\ReelController::class, 'feed'])->name('community.reels.feed');
    Route::get('/app/community/reel-music', [App\Http\Controllers\ReelController::class, 'music'])->name('community.reels.music');
    // Openly-licensed music, searched and fetched through us (see the controller).
    Route::get('/app/community/reel-music-search', [App\Http\Controllers\ReelController::class, 'musicSearch'])->name('community.reels.music.search');
    Route::post('/app/community/reel-music-grab', [App\Http\Controllers\ReelController::class, 'musicGrab'])->name('community.reels.music.grab');
    // How many times a thing has been looked at (see the controller).
    Route::post('/app/community/views', [App\Http\Controllers\CommunityViewController::class, 'count'])->name('community.views');
    Route::get('/app/community/suggestions', [App\Http\Controllers\CommunitySocialController::class, 'suggestions'])->name('community.suggestions');
    // The pictures this account already has here, for any composer that
    // wants to point at one instead of uploading it again.
    Route::get('/app/community/my-photos', [App\Http\Controllers\CommunityMyPhotosController::class, 'index'])->name('community.my-photos');
    Route::get('/app/community/ranking', [App\Http\Controllers\CommunityRankingController::class, 'index'])->name('community.ranking');
    Route::get('/app/community/saved', [App\Http\Controllers\CommunitySocialController::class, 'saved'])->name('community.saved');
    Route::get('/app/community/saved-more', [App\Http\Controllers\CommunitySocialController::class, 'savedMore'])->name('community.saved-more');
    Route::post('/app/community/share/{postId}/wall', [App\Http\Controllers\CommunitySocialController::class, 'shareToWall'])->whereNumber('postId')->name('community.share.wall');
    Route::post('/app/community/share/{postId}/message', [App\Http\Controllers\CommunitySocialController::class, 'shareToMessage'])->whereNumber('postId')->name('community.share.message');
    Route::post('/app/community/share/{postId}/link', [App\Http\Controllers\CommunitySocialController::class, 'publicLink'])->whereNumber('postId')->name('community.share.link');
    Route::get('/app/community/mutual', [App\Http\Controllers\CommunityConnectController::class, 'mutualWith'])->name('community.mutual');
    Route::get('/app/community/glance', [App\Http\Controllers\CommunityConnectController::class, 'glance'])->name('community.glance');
    Route::get('/app/community/members/{userId}', [App\Http\Controllers\CommunityConnectController::class, 'profile'])->whereNumber('userId')->name('community.connect.profile');
    Route::post('/app/community/profile/photos', [App\Http\Controllers\CommunityConnectController::class, 'uploadPhotos'])->name('community.profile.photos.store');
    Route::delete('/app/community/profile/photos/{photoId}', [App\Http\Controllers\CommunityConnectController::class, 'deletePhoto'])->whereNumber('photoId')->name('community.profile.photos.delete');
    Route::post('/app/community/profile/videos', [App\Http\Controllers\CommunityConnectController::class, 'uploadVideo'])->name('community.profile.videos.store');
    Route::delete('/app/community/profile/videos/{videoId}', [App\Http\Controllers\CommunityConnectController::class, 'deleteVideo'])->whereNumber('videoId')->name('community.profile.videos.delete');
    Route::post('/app/community/members/{userId}/connect', [App\Http\Controllers\CommunityConnectController::class, 'connect'])->whereNumber('userId')->name('community.connect.request');
    Route::post('/app/community/members/{userId}/accept', [App\Http\Controllers\CommunityConnectController::class, 'accept'])->whereNumber('userId')->name('community.connect.accept');
    Route::post('/app/community/members/{userId}/decline', [App\Http\Controllers\CommunityConnectController::class, 'decline'])->whereNumber('userId')->name('community.connect.decline');
    Route::delete('/app/community/members/{userId}/connection', [App\Http\Controllers\CommunityConnectController::class, 'disconnect'])->whereNumber('userId')->name('community.connect.disconnect');

    // --- Community: account walls ---
    Route::get('/app/community/members/{userId}/wall', [App\Http\Controllers\CommunityWallController::class, 'posts'])->whereNumber('userId')->name('community.wall.posts');
    Route::post('/app/community/members/{userId}/wall', [App\Http\Controllers\CommunityWallController::class, 'storePost'])->whereNumber('userId')->name('community.wall.post');
    Route::get('/app/community/wall/{postId}/comments', [App\Http\Controllers\CommunityWallController::class, 'comments'])->whereNumber('postId')->name('community.wall.comments');
    Route::post('/app/community/wall/{postId}/comment', [App\Http\Controllers\CommunityWallController::class, 'storeComment'])->whereNumber('postId')->name('community.wall.comment');
    Route::delete('/app/community/wall/{postId}', [App\Http\Controllers\CommunityWallController::class, 'deletePost'])->whereNumber('postId')->name('community.wall.post.delete');
    Route::delete('/app/community/wall-comment/{commentId}', [App\Http\Controllers\CommunityWallController::class, 'deleteComment'])->whereNumber('commentId')->name('community.wall.comment.delete');
    Route::delete('/app/community/reply/{replyId}', [App\Http\Controllers\CommunityGroupController::class, 'deleteReply'])->whereNumber('replyId')->name('community.groups.reply.delete');

    // Post-harvest observations
    Route::get('/app/sm-post-harvest', [App\Http\Controllers\Manager\PostHarvestController::class, 'page'])->name('sm.post-harvest');
    Route::post('/app/sm-post-harvest-store', [App\Http\Controllers\Manager\PostHarvestController::class, 'store'])->name('sm.post-harvest.store');
    Route::put('/app/sm-post-harvest-update', [App\Http\Controllers\Manager\PostHarvestController::class, 'update'])->name('sm.post-harvest.update');
    Route::delete('/app/sm-post-harvest-delete', [App\Http\Controllers\Manager\PostHarvestController::class, 'destroy'])->name('sm.post-harvest.destroy');
    Route::post('/app/sm-post-harvest-restore', [App\Http\Controllers\Manager\PostHarvestController::class, 'restore'])->name('sm.post-harvest.restore');
    Route::post('/app/sm-post-harvest-image-upload', [App\Http\Controllers\Manager\PostHarvestController::class, 'uploadImage'])->name('sm.post-harvest.image-upload');
    Route::get('/app/sm-revenue-report', [App\Http\Controllers\Manager\RevenueReportController::class, 'page'])->name('sm.revenue-report');
    Route::get('/app/sm-revenue-report-compute', [App\Http\Controllers\Manager\RevenueReportController::class, 'compute'])->name('sm.revenue-report.compute');
    Route::post('/app/sm-revenue-report-store', [App\Http\Controllers\Manager\RevenueReportController::class, 'store'])->name('sm.revenue-report.store');
    Route::delete('/app/sm-revenue-report-delete', [App\Http\Controllers\Manager\RevenueReportController::class, 'destroy'])->name('sm.revenue-report.destroy');
    Route::get('/app/sm-activities-labor', [App\Http\Controllers\Manager\ActivityController::class, 'laborSummary'])->name('sm.activities.labor');
    Route::get('/app/sm-labor-report', [App\Http\Controllers\Manager\ActivityController::class, 'laborReportPage'])->name('sm.labor.report');
    Route::get('/app/sm-reports', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'reports'])->name('sm.reports');
    Route::post('/app/sm-status', [App\Http\Controllers\Manager\CroppingScheduleController::class, 'setStatus'])->name('sm.status');
    Route::post('/app/sm-activities-date-note-save', [App\Http\Controllers\Manager\ActivityController::class, 'saveDateNote'])->name('sm.activities.date-note.save');
    Route::delete('/app/sm-activities-date-note-delete', [App\Http\Controllers\Manager\ActivityController::class, 'deleteDateNote'])->name('sm.activities.date-note.delete');
    Route::post('/app/sm-activities-inline-note-save', [App\Http\Controllers\Manager\ActivityController::class, 'inlineNoteSave'])->name('sm.activities.inline-note.save');
    Route::delete('/app/sm-activities-inline-note-delete', [App\Http\Controllers\Manager\ActivityController::class, 'inlineNoteDelete'])->name('sm.activities.inline-note.delete');
    // Money a day brought in - the mirror of the extra expenses below.
    // Who actually turned up, day by day.
    Route::get('/app/sm-attendance', [App\Http\Controllers\Manager\ActivityController::class, 'attendance'])->name('sm.attendance');
    Route::post('/app/sm-attendance-mark', [App\Http\Controllers\Manager\ActivityController::class, 'markAttendance'])->name('sm.attendance.mark');
    // Ticking one line of a reminder checklist (and the money it may carry).
    Route::post('/app/sm-reminder-toggle', [App\Http\Controllers\Manager\ActivityController::class, 'reminderToggle'])->name('sm.activities.reminder-toggle');
    // Things an activity points at: a drawing, a map, a note.
    Route::get('/app/sm-activity-taggables', [App\Http\Controllers\Manager\ActivityController::class, 'taggables'])->name('sm.activities.taggables');
    Route::post('/app/sm-activity-tag', [App\Http\Controllers\Manager\ActivityController::class, 'tagActivity'])->name('sm.activities.tag');
    Route::delete('/app/sm-activity-untag', [App\Http\Controllers\Manager\ActivityController::class, 'untagActivity'])->name('sm.activities.untag');
    Route::get('/app/sm-activities-day-incomes', [App\Http\Controllers\Manager\ActivityController::class, 'listDayIncomes'])->name('sm.activities.day-income.list');
    Route::post('/app/sm-activities-day-income-save', [App\Http\Controllers\Manager\ActivityController::class, 'saveDayIncome'])->name('sm.activities.day-income.save');
    Route::delete('/app/sm-activities-day-income-delete', [App\Http\Controllers\Manager\ActivityController::class, 'deleteDayIncome'])->name('sm.activities.day-income.delete');
    Route::get('/app/sm-activities-day-expenses', [App\Http\Controllers\Manager\ActivityController::class, 'listDayExpenses'])->name('sm.activities.day-expense.list');
    Route::post('/app/sm-activities-day-expense-save', [App\Http\Controllers\Manager\ActivityController::class, 'saveDayExpense'])->name('sm.activities.day-expense.save');
    Route::delete('/app/sm-activities-day-expense-delete', [App\Http\Controllers\Manager\ActivityController::class, 'deleteDayExpense'])->name('sm.activities.day-expense.delete');
    Route::post('/app/sm-activities-day-expense-reorder', [App\Http\Controllers\Manager\ActivityController::class, 'reorderDayExpenses'])->name('sm.activities.day-expense.reorder');
    Route::post('/app/sm-activities-day-income-reorder', [App\Http\Controllers\Manager\ActivityController::class, 'reorderDayIncomes'])->name('sm.activities.day-income.reorder');
    Route::post('/app/sm-activities-day-money-block-move', [App\Http\Controllers\Manager\ActivityController::class, 'moveDayMoneyBlock'])->name('sm.activities.day-money.block-move');

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

    // --- Printable / standalone documents ---
    Route::get('/app/sm-worker-presentation', [App\Http\Controllers\Manager\DocumentController::class, 'workerPresentation'])->name('sm.worker-presentation');
    Route::get('/app/sm-card-viewer', [App\Http\Controllers\Manager\DocumentController::class, 'cardViewer'])->name('sm.card-viewer');
});

/* An article as anee.io will show it, for the admin app's builder.
 *
 * Outside every auth group on purpose: the person looking is an
 * administrator on the other app and has no session here. The address is
 * signed instead, and the response carries a noindex header, so it is
 * reachable by the one person holding the link and by nobody's crawler. */
Route::get('/blog-preview/{id}', [App\Http\Controllers\BlogPreviewController::class, 'show'])
    ->whereNumber('id')->name('blog.preview');
