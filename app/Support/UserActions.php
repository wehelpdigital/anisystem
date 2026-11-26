<?php

namespace App\Support;

use App\Http\Middleware\RecordsScheduleActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * What a member does in the app (2026-11-26, the owner: "track inside the app all the activities of the user, then
 * create a general report for it in the admin panel for us to know what are they trying to do? and check also per
 * user if we click on the user in the admin panel").
 *
 * Pages opened are App\Support\PageViews. This is the rest, one row on as_user_actions each:
 *   did      a thing done: a season started, a lot added, a task ticked, Anee asked (every write the server took)
 *   refused  a save the app said no to, with the reason it gave (a missing field, a wrong value)
 *   locked   a door the plan keeps shut, tapped anyway: the feature they wanted (from the upgrade sheet)
 *   opened   a window opened (an Add activity sheet, the Modules menu): what they started to do
 *   failed   the server broke (5xx), or the page's own script did
 * The server writes the first two and the server half of failed (RecordsUserActions middleware); the page sends the
 * rest in small batches (core.js aneeNote, POST /app/track). Never what they typed: no note, no question, no name.
 *
 * Counted for the same people as PageViews (the live site, members only, no staff, no staff looking through a
 * member's eyes, no test or system account, no scripted browser). Written after the answer has gone out; never throws.
 */
final class UserActions
{
    public const TABLE = 'as_user_actions';

    public const KINDS = ['did', 'refused', 'locked', 'opened', 'failed'];

    /** What the page may send. */
    private const CLIENT_KINDS = ['locked', 'opened', 'failed'];

    /**
     * Not deeds: the plumbing that writes on its own (live sync, typing, read marks, a video's middle pieces, the page
     * views themselves). A prefix ends in a dot.
     */
    private const QUIET = ['pageviews.seen', 'track', 'notifications.', 'sm.board.heartbeat', 'sm.board.push', 'sm.board.open',
        'sm.board.draft-open', 'sm.board.undo', 'sm.chat.seen', 'sm.chat.typing', 'community.messages.typing', 'community.views',
        'sm.map.push', 'sm.map.loc', 'sm.map.load', 'sm.map.trace', 'sm.photo.push', 'sm.photo.undo', 'sm.photo.set', 'sm.undo.',
        'sm.call.token', 'sm.call.mute', 'sm.call.end', 'clip-upload.part', 'clip-upload.drop', 'sm.media-picker.',
        'account.subscription.refresh', 'logout', 'sat.tiles', 'planport.check', 'landprep.check', 'sm.effect.applyCheck',
        'sm.effect.copyAnswers', 'verify.mine', 'mother.', 'admin.', 'site.preview.post', 'review.dismiss'];

    /** The deeds outside a season, said plainly (the season's own are the Logs diary's words). */
    private const SAYS = [
        'signup.attempt' => 'Signed up', 'login.attempt' => 'Logged in', 'verify.resend' => 'Asked for the confirmation email again',
        'password.email' => 'Asked for a password reset', 'password.update' => 'Set a new password',
        'account.password.update' => 'Changed their password', 'account.profile.update' => 'Updated their profile',
        'account.avatar' => 'Changed their photo', 'account.choose.apply' => 'Chose how they use the app',
        'worker.switch' => 'Switched to another farm', 'worker.invite.accept' => 'Accepted a worker invite',
        'ai.ask' => 'Asked Anee a question', 'ai.photo' => 'Showed Anee a photo', 'ai.photo.existing' => 'Showed Anee a photo',
        'ai.conversation.new' => 'Started a new chat with Anee', 'ai.conversation.delete' => 'Deleted a chat with Anee',
        'ai.conversation.rename' => 'Renamed a chat with Anee', 'ai.conversation.note' => 'Kept a note from a chat with Anee',
        'ai.conversation.global-note' => 'Kept a note from a chat with Anee', 'ai.conversation.link' => 'Linked a chat with Anee to a season',
        'ai.credits.submit' => 'Sent a payment for credits', 'sm.ai.group.ask' => 'Asked Anee in the season chat',
        'sm.anee.generate' => 'Asked Anee for a season report', 'sm.growth.realign' => 'Asked Anee to realign the growth stages',
        'auto.start' => 'Started Anee Auto', 'auto.paid' => 'Paid for Anee Auto', 'auto.done' => 'Anee Auto: marked a task done',
        'auto.cant' => 'Anee Auto: said a task cannot be done', 'auto.ask' => 'Anee Auto: asked a question',
        'auto.observe' => 'Anee Auto: sent an observation', 'auto.approve' => 'Anee Auto: approved the plan',
        'auto.generate' => 'Anee Auto: made the season plan', 'auto.analyze' => 'Anee Auto: read the lot', 'auto.answers' => 'Anee Auto: answered the questions',
        'auto.basics' => 'Anee Auto: filled in the basics', 'auto.fert' => 'Anee Auto: chose the fertilizer', 'auto.goal' => 'Anee Auto: set the goal',
        'auto.lot' => 'Anee Auto: picked a lot', 'auto.lot.add' => 'Anee Auto: added a lot', 'auto.newlot' => 'Anee Auto: made a new lot',
        'auto.rank' => 'Anee Auto: ranked the varieties', 'auto.workers' => 'Anee Auto: chose the workers', 'auto.imbalance' => 'Anee Auto: checked the soil balance',
        'auto.shed.add' => 'Anee Auto: added to the shed', 'auto.shed.price' => 'Anee Auto: priced the shed', 'auto.pay.rate' => 'Anee Auto: set the pay rate',
        'sm.activities.duplicate' => 'Copied a task', 'sm.activities.reorder' => 'Moved tasks around', 'sm.activities.set-date' => 'Moved a task to another day',
        'tools.open' => 'Opened a tool',
        'sm.lots.fromFarm' => 'Brought a farm lot into the season', 'sm.lots.toFarm' => 'Sent a season lot to Global Lots',
        'sm.lots.map.link' => 'Linked a lot to a map', 'sm.lots.map.detach' => 'Unlinked a lot from a map', 'sm.lots.pin' => 'Pinned a lot',
        'pb.notes' => 'Wrote notes on a protocol version', 'pb.rules' => 'Set the rules of a protocol version', 'pb.versions.use' => 'Chose a protocol version',
        'sm.draw.season.add' => 'Added a drawing to the season', 'sm.map.season.add' => 'Added a map to the season', 'sm.map.remove' => 'Removed something from a map',
        'sm.map.clear' => 'Cleared a map', 'sm.ai.group.session-create' => 'Started a season chat with Anee', 'sm.ai.group.session-note' => 'Kept a note from a season chat',
        'sm.inventory.batch.price' => 'Set a batch price',
        'sm.store' => 'Started a new season', 'sm.store.wizard' => 'Started a new season', 'sm.destroy' => 'Deleted a season',
        'sm.duplicate' => 'Copied a season', 'htp.port' => 'Made a season plan from How to Plant',
        'farmlots.save' => 'Added a farm lot', 'farmlots.update' => 'Edited a farm lot', 'farmlots.delete' => 'Deleted a farm lot',
        'farmlots.boundary' => 'Drew a lot boundary', 'farmlots.confirm' => 'Checked a lot\'s answers', 'farmlots.duplicate' => 'Copied a farm lot',
        'farmlots.pin' => 'Pinned a lot on the map', 'fieldprofile.save' => 'Answered the field questions', 'fieldprofile.update' => 'Answered the field questions',
        'workers.global.store' => 'Added a worker to the roster', 'workers.global.update' => 'Edited a worker on the roster',
        'workers.global.delete' => 'Deleted a worker from the roster', 'sm.workers.rules.save' => 'Saved the workers\' rules',
        'sm.attendance.mark' => 'Marked attendance', 'contacts.store' => 'Added a contact', 'contacts.update' => 'Edited a contact',
        'contacts.destroy' => 'Deleted a contact', 'notes.hub.store' => 'Wrote a note', 'notes.hub.destroy' => 'Deleted a note',
        'notes.hub.draw' => 'Saved a drawing on a note', 'notes.hub.image-upload' => 'Attached a photo to a note',
        'notes.hub.video-upload' => 'Attached a video to a note', 'quick-capture.gallery' => 'Took a photo for the Gallery',
        'quick-capture.notes' => 'Took a photo for Notes', 'clip-upload.start' => 'Started sending a video',
        'gallery.hub.import' => 'Imported photos or videos', 'gallery.hub.own.destroy' => 'Deleted from the Gallery',
        'wtp.generate' => 'Ran When to Plant', 'wtp.save' => 'Saved a When to Plant answer', 'whatp.generate' => 'Ran What to Plant',
        'vary.generate' => 'Ran Variety Research', 'proto.generate' => 'Ran Crop Protocol Analysis', 'proto.need' => 'Checked a crop\'s needs',
        'landprep.generate' => 'Ran the Land Prep Planner', 'landprep.port' => 'Put a land prep plan into a season',
        'landprep.season' => 'Made a season from a land prep plan', 'npk.save' => 'Saved an NPK Plus plan',
        'npk.analyze' => 'Asked Anee to read an NPK plan', 'npk.product' => 'Added a fertilizer product', 'npk.ph.use' => 'Used a soil pH reading',
        'sm.npk.generate' => 'Made an NPK plan for the season', 'lcc.generate' => 'Read a leaf with the LCC Checker',
        'lcc.manual' => 'Entered a leaf color by hand', 'sat.generate' => 'Ran Satellite Analysis', 'sky.generate' => 'Ran Satellite Weather',
        'compat.check' => 'Checked products for mixing', 'compat.anee' => 'Asked Anee about mixing products', 'compat.mine.save' => 'Saved a mixing check',
        'sm.compare.generate' => 'Ran Compare', 'sm.protocol.generate' => 'Made a crop protocol for the season', 'sm.protocol.save' => 'Saved the season\'s crop protocol',
        'sm.effect.run' => 'Ran the Coverage Planner', 'sm.effect.lookup' => 'Coverage Planner: had Anee look up a product',
        'pb.store' => 'Made a protocol', 'pb.save' => 'Saved a protocol', 'pb.analyze' => 'Asked Anee to review a protocol',
        'pb.port' => 'Put a protocol into a season', 'pb.duplicate' => 'Copied a protocol', 'pb.delete' => 'Deleted a protocol',
        'pb.versions.store' => 'Saved a protocol version', 'pb.coverage.run' => 'Ran the Coverage Planner on a protocol', 'pb.npk.generate' => 'Made an NPK plan for a protocol',
        'planport.builder' => 'Sent a plan to the Protocol Builder', 'planport.preview' => 'Previewed a plan for a season',
        'seedcalc.save' => 'Saved a Seed Count reading', 'hatian.save' => 'Saved a Hatian calculation', 'market.save' => 'Saved a price forecast',
        'bilad.save' => 'Saved a Benta o Bilad answer', 'sm.chat.send' => 'Sent a team chat message', 'sm.call.ring' => 'Called the team',
        'sm.board.save-notes' => 'Saved the whiteboard', 'sm.board.page-create' => 'Added a whiteboard page',
        'sm.email.day' => 'Emailed a day\'s plan', 'sm.email.activity' => 'Emailed a task', 'sm.quick-share.email' => 'Shared by email',
        'sm.digest.test' => 'Sent themselves a test daily email', 'support.store' => 'Asked for help', 'support.reply' => 'Replied to support',
        'trash.restore' => 'Restored from the Trash', 'trash.empty' => 'Emptied the Trash', 'trash.purge' => 'Deleted from the Trash for good',
        'tags.global.rename' => 'Renamed a tag', 'tags.global.delete' => 'Deleted a tag', 'tutorial.dismiss' => 'Closed a tutorial for good',
        'review.store' => 'Left a review', 'group.answer' => 'Answered the Facebook group popup', 'checkout.start' => 'Started a checkout',
        'checkout.proof' => 'Sent a payment proof', 'checkout.cancel' => 'Cancelled a checkout', 'purchase.submit' => 'Sent a plan purchase',
        'ask.farm' => 'Free question: filled in their farm', 'ask.question' => 'Free question: asked Anee', 'ask.send' => 'Free question: had the answer emailed',
        'ask.start' => 'Free question: opened the answer', 'contact.submit' => 'Sent the contact form', 'ph.contact.submit' => 'Sent the contact form',
    ];

    /** What a deed was done to, for the routes said by their verb alone (Deleted + a When to Plant answer). */
    private const THINGS = [
        'wtp' => 'a When to Plant answer', 'whatp' => 'a What to Plant answer', 'vary' => 'a Variety Research report',
        'lcc' => 'an LCC reading', 'bilad' => 'a Benta o Bilad answer', 'market' => 'a price forecast', 'hatian' => 'a Hatian calculation',
        'seedcalc' => 'a Seed Count reading', 'sky' => 'a Satellite Weather reading', 'landprep' => 'a land prep plan',
        'compat.mine' => 'a mixing check', 'npk.product' => 'a fertilizer product', 'npk' => 'an NPK Plus plan', 'sat' => 'a Satellite Analysis',
        'proto' => 'a Crop Protocol report', 'pb' => 'a protocol', 'pb.analyses' => 'a protocol review', 'pb.coverage' => 'a protocol coverage reading',
        'pb.versions' => 'a protocol version', 'pb.files' => 'a protocol file', 'sm.effect' => 'a Coverage Planner reading',
        'sm.compare' => 'a comparison', 'sm.anee' => 'a season report', 'sm.map' => 'a map', 'sm.draw' => 'a drawing',
        'sm.attachments' => 'an attachment', 'sm.activities' => 'a task', 'sm.activities.date-note' => 'a day note',
        'sm.activities.inline-note' => 'a note on a task', 'sm.activities.day-expense' => 'the day expenses',
        'sm.activities.day-income' => 'the day incomes', 'sm.activity-versions' => 'a plan version',
        'sm.default-groupings' => 'the default groupings', 'sm.critical-rules' => 'a warning rule', 'sm.markers' => 'a progress marker',
        'sm.tags' => 'a tag', 'sm.doc-tags' => 'a document tag', 'sm.gallery.album' => 'an album', 'sm.gallery.image' => 'a photo',
        'sm.inventory.batch' => 'a batch price', 'sm.workers' => 'a worker', 'sm.lots' => 'a lot', 'sm.notes' => 'a note',
        'community' => 'a community post', 'contacts' => 'a contact', 'compat' => 'a mixing check', 'tags.global' => 'a tag',
    ];

    /** Which part of the app a route belongs to: route name prefix => area, longest first. */
    private const AREAS = [
        'sm.activities' => 'Season board', 'sm.activity-versions' => 'Season board', 'sm.markers' => 'Season board',
        'sm.critical-rules' => 'Season board', 'sm.tags' => 'Season board', 'sm.default-groupings' => 'Season board',
        'sm.board' => 'Team', 'sm.chat' => 'Team', 'sm.call' => 'Team', 'sm.collab' => 'Team', 'sm.email' => 'Team',
        'sm.quick-share' => 'Team', 'sm.digest' => 'Team',
        'sm.lots' => 'Lots', 'farmlots' => 'Lots', 'fieldprofile' => 'Lots',
        'sm.workers' => 'Workers', 'workers.global' => 'Workers', 'sm.attendance' => 'Workers',
        'sm.inventory' => 'Inventory',
        'sm.notes' => 'Notes and records', 'notes.hub' => 'Notes and records', 'quick-voice' => 'Notes and records',
        'quick-record' => 'Notes and records', 'quick-capture' => 'Notes and records', 'sm.post-harvest' => 'Notes and records',
        'sm.attachments' => 'Notes and records', 'sm.doc-entries' => 'Notes and records', 'sm.doc-tags' => 'Notes and records',
        'clip-upload' => 'Notes and records',
        'sm.gallery' => 'Gallery', 'gallery.hub' => 'Gallery', 'sm.photo' => 'Gallery',
        'sm.map' => 'Maps and drawing', 'sm.draw' => 'Maps and drawing',
        'ai.' => 'Anee', 'sm.ai' => 'Anee', 'sm.anee' => 'Anee', 'sm.growth' => 'Anee', 'ask.' => 'Anee',
        'auto.' => 'Anee Auto',
        'wtp' => 'Tools', 'whatp' => 'Tools', 'vary' => 'Tools', 'proto' => 'Tools', 'landprep' => 'Tools', 'npk' => 'Tools',
        'sm.npk' => 'Tools', 'lcc' => 'Tools', 'sat' => 'Tools', 'sky' => 'Tools', 'compat' => 'Tools', 'sm.compare' => 'Tools',
        'sm.protocol' => 'Tools', 'sm.effect' => 'Tools', 'pb' => 'Tools', 'planport' => 'Tools', 'htp' => 'Tools',
        'seedcalc' => 'Tools', 'hatian' => 'Tools', 'market' => 'Tools', 'bilad' => 'Tools', 'sm.report' => 'Tools',
        'sm.' => 'Seasons', 'sm' => 'Seasons',
        'account' => 'Account', 'worker' => 'Account', 'password' => 'Account', 'verify' => 'Account', 'signup' => 'Account', 'login' => 'Account',
        'checkout' => 'Buying', 'purchase' => 'Buying', 'ai.credits' => 'Buying',
        'community' => 'Community', 'support' => 'Support', 'contacts' => 'Contacts', 'trash' => 'Housekeeping', 'tags' => 'Housekeeping',
        'tutorial' => 'Prompts', 'review' => 'Prompts', 'group' => 'Prompts', 'contact' => 'Support', 'ph.contact' => 'Support',
    ];

    /* ------------------------------------------------------------------ writing */

    /** After a write was answered (the RecordsUserActions middleware). */
    public static function note(Request $request, Response $response): void
    {
        try {
            if ($request->isMethodSafe()) {
                return;     // reading is PageViews' business
            }
            $name = (string) optional($request->route())->getName();
            if ($name === '' || self::quiet($name)) {
                return;
            }
            $user = Auth::user();
            if (! $user || ! PageViews::countsFor($request, $user, '/' . ltrim($request->path(), '/'))) {
                return;
            }
            $status = $response->getStatusCode();
            $json = self::json($response);
            // A door the plan keeps shut: the page's upgrade sheet notes it, with the words it showed.
            if ($status === 403 && ! empty($json['tierLock'])) {
                return;
            }
            $kind = match (true) {
                $status >= 500 => 'failed',
                $status >= 400 => 'refused',
                $status >= 300 && $request->hasSession() && $request->session()->has('errors') => 'refused',
                default => 'did',
            };
            $detail = match ($kind) {
                'refused' => self::reason($request, $json, $status),
                'failed' => 'The server could not finish it (' . $status . ')',
                default => null,
            };
            $schedule = (int) ($request->attributes->get('auditScheduleId') ?: 0);
            if (! $schedule && str_starts_with($name, 'sm.')) {
                $schedule = (int) ($request->query('scheduleId') ?: $request->input('scheduleId') ?: 0);
            }
            $farm = WorkerContext::inWorkerContext() ? WorkerContext::effectiveOwnerId() : 0;
            $row = [
                'userId' => (int) $user->id,
                'farmId' => $farm && $farm !== (int) $user->id ? $farm : null,
                'scheduleId' => $schedule ?: null,
                'kind' => $kind,
                'name' => Str::limit($name, 118, ''),
                'label' => Str::limit(self::say($name), 158, ''),
                'area' => self::area($name),
                'path' => self::page($request),
                'status' => $status,
                'ms' => defined('LARAVEL_START') ? (int) round((microtime(true) - LARAVEL_START) * 1000) : null,
                'detail' => $detail !== null ? Str::limit($detail, 250, '') : null,
                'device' => self::device((string) $request->userAgent()),
                'createdAt' => now(),
            ];
            app()->terminating(fn () => self::write([$row]));
        } catch (\Throwable $e) {
            // Never a broken save for a count.
        }
    }

    /** What the page noticed (POST /app/track): windows opened, locked doors, its own errors. A batch of 25 at most. */
    public static function client(Request $request): void
    {
        try {
            $user = Auth::user();
            if (! $user || ! PageViews::countsFor($request, $user, '/app')) {
                return;
            }
            $events = $request->input('e');
            if (! is_array($events)) {
                return;
            }
            $rows = [];
            $device = self::device((string) $request->userAgent());
            foreach (array_slice($events, 0, 25) as $e) {
                if (! is_array($e) || ! in_array($e['k'] ?? null, self::CLIENT_KINDS, true)) {
                    continue;
                }
                $kind = $e['k'];
                $raw = (string) ($e['p'] ?? '');
                $parts = parse_url($raw) ?: [];
                $path = PageViews::fold('/' . ltrim((string) ($parts['path'] ?? ''), '/'));
                parse_str((string) ($parts['query'] ?? ''), $q);
                $module = isset($q['module']) && is_string($q['module']) && preg_match('/^[a-z_-]{2,20}$/', $q['module']) ? $q['module'] : null;
                $page = $path !== null ? Str::limit($path . ($module ? '?module=' . $module : ''), 190, '') : null;
                $topic = $page !== null ? PageViews::classify($path, $module ? ['module' => $module] : [])['topic'] : null;
                $clean = fn ($v, $n) => trim(Str::limit(preg_replace('/\s+/u', ' ', strip_tags((string) $v)), $n, ''));
                $label = $clean($e['l'] ?? '', 158);
                $name = $clean($e['n'] ?? '', 118);
                if ($name === '') {
                    continue;
                }
                $ago = max(0, min(900, (int) ($e['ago'] ?? 0)));
                $rows[] = [
                    'userId' => (int) $user->id,
                    'farmId' => null,
                    'scheduleId' => null,
                    'kind' => $kind,
                    'name' => $name,
                    'label' => $label !== '' ? $label : ucfirst(str_replace(['sheet:', 'lock:', '-', '_'], ['', '', ' ', ' '], $name)),
                    'area' => $topic !== null ? Str::limit(PageViews::toolLabel($topic), 38, '') : null,
                    'path' => $page,
                    'status' => null,
                    'ms' => null,
                    'detail' => ($d = $clean($e['d'] ?? '', 250)) !== '' ? $d : null,
                    'device' => $device,
                    'createdAt' => now()->subSeconds($ago),
                ];
            }
            if ($rows) {
                app()->terminating(fn () => self::write($rows));
            }
        } catch (\Throwable $e) {
            // A lost note is a lost note.
        }
    }

    /** Whether this page should send its notes at all (the layout prints the address only then). */
    public static function watching(Request $request): bool
    {
        try {
            $user = Auth::user();

            return $user && PageViews::countsFor($request, $user, '/' . ltrim($request->path(), '/'));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Every row after 400 days, like the page views. */
    public static function prune(): int
    {
        return DB::table(self::TABLE)->where('createdAt', '<', now()->subDays(400))->delete();
    }

    private static function write(array $rows): void
    {
        try {
            DB::table(self::TABLE)->insert($rows);
        } catch (\Throwable $e) {
            // A lost row is a lost row (and before the table exists, every row is).
        }
    }

    private static function quiet(string $name): bool
    {
        foreach (self::QUIET as $q) {
            if (str_ends_with($q, '.') ? str_starts_with($name, $q) : $name === $q) {
                return true;
            }
        }

        return false;
    }

    /** The deed in plain words: ours, then the season diary's, then the route name made readable. */
    public static function say(string $name): string
    {
        if (isset(self::SAYS[$name])) {
            return self::SAYS[$name];
        }
        if (isset(RecordsScheduleActivity::SAYS[$name])) {
            return RecordsScheduleActivity::SAYS[$name];
        }
        $parts = explode('.', $name);
        $verb = array_pop($parts);
        $says = ['store' => 'Added', 'save' => 'Saved', 'update' => 'Edited', 'destroy' => 'Deleted', 'delete' => 'Deleted',
            'generate' => 'Made', 'meta' => 'Renamed', 'rename' => 'Renamed', 'reorder' => 'Reordered', 'restore' => 'Restored',
            'duplicate' => 'Copied', 'move' => 'Moved'];
        // The thing it was done to, by the longest name it goes by.
        for ($i = count($parts); $i > 0; $i--) {
            $thing = self::THINGS[implode('.', array_slice($parts, 0, $i))] ?? null;
            if ($thing !== null && isset($says[$verb])) {
                return $says[$verb] . ' ' . $thing;
            }
        }
        $what = trim(str_replace(['-', '_'], ' ', implode(' ', array_filter($parts, fn ($p) => $p !== 'sm'))));
        $verbWords = str_replace(['-', '_'], ' ', $verb);
        if ($parts && $parts[0] === 'auto') {
            return 'Anee Auto: ' . $verbWords;
        }
        if (isset($says[$verb])) {
            return $says[$verb] . ($what !== '' ? ' ' . $what : '');
        }

        return Str::ucfirst(trim($what . ' ' . $verbWords));
    }

    public static function area(string $name): string
    {
        $best = '';
        foreach (self::AREAS as $prefix => $area) {
            $hit = str_ends_with($prefix, '.') ? str_starts_with($name, $prefix) : ($name === $prefix || str_starts_with($name, $prefix . '.'));
            if ($hit && strlen($prefix) > strlen($best)) {
                $best = $prefix;
            }
        }

        return $best !== '' ? self::AREAS[$best] : 'Other';
    }

    /** The page the deed was done from: the address that sent it, ours only, folded like a page view's. */
    private static function page(Request $request): ?string
    {
        $ref = (string) $request->headers->get('referer', '');
        $p = $ref !== '' ? parse_url($ref) : null;
        if ($p && isset($p['host']) && strtolower($p['host']) === strtolower($request->getHost())) {
            $path = PageViews::fold('/' . ltrim((string) ($p['path'] ?? ''), '/'));
            parse_str((string) ($p['query'] ?? ''), $q);
            $module = isset($q['module']) && is_string($q['module']) && preg_match('/^[a-z_-]{2,20}$/', $q['module']) ? $q['module'] : null;

            return $path !== null ? Str::limit($path . ($module ? '?module=' . $module : ''), 190, '') : null;
        }

        return PageViews::fold('/' . ltrim($request->path(), '/'));
    }

    private static function json(Response $response): array
    {
        $type = (string) $response->headers->get('Content-Type', '');
        if (! str_contains($type, 'json')) {
            return [];
        }
        $d = json_decode((string) $response->getContent(), true);

        return is_array($d) ? $d : [];
    }

    /** Why the app said no, in the words it said it (our own messages, never what they typed). */
    private static function reason(Request $request, array $json, int $status): string
    {
        $first = null;
        if (! empty($json['errors']) && is_array($json['errors'])) {
            $f = reset($json['errors']);
            $first = is_array($f) ? (string) reset($f) : (string) $f;
        } elseif ($request->hasSession() && ($bag = $request->session()->get('errors')) && method_exists($bag, 'first')) {
            $first = (string) $bag->first();
        }
        $msg = $first ?: (string) ($json['message'] ?? '');

        return $msg !== '' ? $msg : match ($status) {
            401, 419 => 'Their login had run out',
            403 => 'Not allowed',
            404 => 'Not found',
            409 => 'Changed by someone else first',
            413 => 'The file was too big',
            429 => 'Too many tries at once',
            default => 'Refused (' . $status . ')',
        };
    }

    private static function device(string $ua): string
    {
        return preg_match('/iPad|Tablet|Android(?!.*Mobile)/i', $ua) ? 'Tablet'
            : (preg_match('/Mobi|iPhone|iPod|Android.*Mobile|Windows Phone/i', $ua) ? 'Phone' : 'Computer');
    }

    /* ------------------------------------------------------------------ reading */

    /**
     * One member's pages and deeds together, newest first, 40 at a time (the client sheet). Each line carries its
     * time to the second, so the sheet can split visits where they were away half an hour or more.
     */
    public static function timeline(int $userId, int $offset = 0, int $limit = 40): array
    {
        $offset = max(0, $offset);
        try {
            $rows = DB::select(
                'SELECT * FROM ('
                . " (SELECT 'view' t, id, viewedAt at, title label, area, topic, path, query, NULL status, NULL detail, NULL name FROM " . PageViews::TABLE . ' WHERE userId = ?)'
                . " UNION ALL (SELECT kind t, id, createdAt at, label, area, NULL topic, path, NULL query, status, detail, name FROM " . self::TABLE . ' WHERE userId = ?)'
                . ') x ORDER BY at DESC, id DESC LIMIT ? OFFSET ?',
                [$userId, $userId, $limit + 1, $offset]
            );
        } catch (\Throwable $e) {
            // Before the table exists: the pages alone.
            $rows = DB::table(PageViews::TABLE)->where('userId', $userId)->orderByDesc('viewedAt')->orderByDesc('id')->offset($offset)->limit($limit + 1)
                ->get(['id', 'viewedAt as at', 'title as label', 'area', 'topic', 'path', 'query'])
                ->map(fn ($r) => (object) ((array) $r + ['t' => 'view', 'status' => null, 'detail' => null, 'name' => null]))->all();
        }
        $more = count($rows) > $limit;
        $signed = DB::table('anisystem_users')->where('id', $userId)->value('created_at');

        return [
            'rows' => array_map(function ($r) use ($signed) {
                $at = Carbon::parse($r->at);
                $isView = $r->t === 'view';

                return [
                    't' => $r->t,
                    'ts' => $at->timestamp,
                    'day' => $at->isToday() ? 'Today' : ($at->isYesterday() ? 'Yesterday' : $at->format('D, M j')),
                    'time' => $at->format('g:i A'),
                    'label' => $isView ? ($r->area === 'app' ? PageViews::toolLabel($r->topic) : ($r->label ?: $r->path)) : $r->label,
                    'sub' => $isView ? ($r->area === 'app' ? null : $r->label) : $r->area,
                    'path' => $r->path . ($isView && $r->query ? '?' . $r->query : ''),
                    'detail' => $r->detail,
                    'status' => $r->status !== null ? (int) $r->status : null,
                    'before' => $signed && $at->lt(Carbon::parse($signed)),
                ];
            }, array_slice($rows, 0, $limit)),
            'next' => $more ? $offset + $limit : null,
        ];
    }

    /** One member at a glance: what they did, what stopped them, and when they were last here. */
    public static function profile(int $userId): array
    {
        try {
            $base = fn () => DB::table(self::TABLE)->where('userId', $userId);
            $by = $base()->selectRaw('kind, COUNT(*) n, MAX(createdAt) last')->groupBy('kind')->get()->keyBy('kind');
            $first = $base()->min('createdAt');
            $top = $base()->where('kind', 'did')->selectRaw('label, COUNT(*) n')->groupBy('label')->orderByDesc('n')->limit(5)->get();
            $locks = $base()->where('kind', 'locked')->selectRaw('label, COUNT(*) n')->groupBy('label')->orderByDesc('n')->limit(4)->get();
            $stuck = $base()->whereIn('kind', ['refused', 'failed'])->selectRaw('label, detail, COUNT(*) n')->groupBy('label', 'detail')->orderByDesc('n')->limit(4)->get();
        } catch (\Throwable $e) {
            return ['ready' => false];
        }
        $n = fn ($k) => (int) ($by[$k]->n ?? 0);
        $lastDid = $by['did']->last ?? null;

        return [
            'ready' => true,
            'since' => $first ? Carbon::parse($first)->format('M j, Y') : null,
            'did' => $n('did'), 'refused' => $n('refused'), 'locked' => $n('locked'), 'opened' => $n('opened'), 'failed' => $n('failed'),
            'lastDid' => $lastDid ? Carbon::parse($lastDid)->diffForHumans() : null,
            'top' => $top->map(fn ($r) => ['label' => $r->label, 'n' => (int) $r->n])->all(),
            'locks' => $locks->map(fn ($r) => ['label' => $r->label, 'n' => (int) $r->n])->all(),
            'stuck' => $stuck->map(fn ($r) => ['label' => $r->label . ($r->detail ? ': ' . $r->detail : ''), 'n' => (int) $r->n])->all(),
        ];
    }
}
