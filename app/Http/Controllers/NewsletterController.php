<?php

namespace App\Http\Controllers;

use App\Models\AsNlEmail;
use App\Models\AsSiteSetting;
use App\Services\AcumbamailService;
use App\Services\ResendMailer;
use App\Support\MediaStore;
use App\Support\Newsletter\Audience;
use App\Support\Newsletter\Demo;
use App\Support\Newsletter\NewsEmail;
use App\Support\Newsletter\NlSettings;
use App\Support\Newsletter\Planner;
use App\Support\Newsletter\Renderer;
use App\Support\Newsletter\Seg;
use App\Support\Newsletter\SequenceImport;
use App\Support\Newsletter\Theme;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * THE NEWSLETTER MODULE'S ONE DOOR (2026-10-17). The same screen runs in two
 * places (the owner: "the same module synced"): anee.io's admin panel
 * (/admin/newsletter, a signed-in admin) and the mother app's AniSystem >
 * Newsletter, whose server calls /mother-api/newsletter/* here with the
 * shared token. Both reach api(), so there is one set of rules for both.
 *
 * Nothing here sends to subscribers. The scheduler does (Planner); this only
 * edits the line, shows the plan, and sends tests to one address.
 */
class NewsletterController extends Controller
{
    /** anee.io's admin page. */
    public function page()
    {
        return view('admin.newsletter', ['kitVersion' => self::kitVersion()]);
    }

    /** The mother app's server, with the shared token. */
    public function mother(Request $request, string $path)
    {
        $token = (string) config('mother.media_token');
        if ($token === '' || ! hash_equals($token, (string) $request->header('X-Anee-Token'))) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }
        $who = 'mother: ' . (mb_substr(trim((string) $request->header('X-Anee-Actor', 'an admin')), 0, 80) ?: 'an admin');
        $email = trim((string) $request->header('X-Anee-Actor-Email', ''));

        return $this->api($request, $path, $who, filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '');
    }

    /** anee.io's admin panel. */
    public function admin(Request $request, string $path)
    {
        $u = Auth::user();

        return $this->api($request, $path, trim(($u->firstName ?? '') . ' ' . ($u->lastName ?? '')) ?: (string) $u->email, (string) $u->email);
    }

    /** The kit's files' version, so a deploy refreshes them in both apps. */
    public static function kitVersion(): string
    {
        $t = 0;
        foreach (['nl-kit/nl.js', 'nl-kit/nl.css'] as $f) {
            $t = max($t, (int) @filemtime(public_path($f)));
        }

        return substr(md5((string) $t), 0, 8);
    }

    private function api(Request $request, string $path, string $who, string $whoEmail)
    {
        if (! Schema::hasTable('as_nl_emails')) {
            return $this->fail('The newsletter tables are not on this database yet (the migration runs on deploy).', 503);
        }
        $path = trim($path, '/');
        $m = $request->method();
        try {
            return match (true) {
                $m === 'GET' && $path === 'boot' => $this->boot(),
                $m === 'GET' && $path === 'emails' => $this->emails($request),
                $m === 'GET' && (bool) preg_match('~^email/(\d+)$~', $path, $x) => $this->one((int) $x[1]),
                $m === 'POST' && $path === 'email' => $this->save($request, $who),
                $m === 'POST' && (bool) preg_match('~^email/(\d+)/(delete|duplicate|status|move|test)$~', $path, $x) => $this->act($request, (int) $x[1], $x[2], $who, $whoEmail),
                $m === 'POST' && $path === 'reorder' => $this->reorder($request),
                $m === 'POST' && $path === 'preview' => $this->preview($request),
                $m === 'GET' && $path === 'plan' => $this->ok($this->planData()),
                $m === 'GET' && $path === 'runs' => $this->runs($request),
                $m === 'POST' && $path === 'theme' => $this->ok(['theme' => Theme::save((array) $request->input('theme', []))], 'Theme saved. Every email not sent yet wears it.'),
                $m === 'POST' && $path === 'theme/reset' => $this->ok(['theme' => Theme::reset()], 'The anee.io look is back.'),
                $m === 'POST' && $path === 'settings' => $this->settings($request),
                $m === 'GET' && $path === 'audience' => $this->audience($request),
                $m === 'POST' && $path === 'audience/sync' => $this->sync(),
                $m === 'POST' && $path === 'import' => $this->import($who),
                $m === 'POST' && $path === 'demo' => $this->ok(['made' => Demo::run($who)], 'Samples added to the drafts.'),
                $m === 'POST' && $path === 'news-now' => $this->newsNow(),
                $m === 'POST' && $path === 'upload' => $this->upload($request),
                default => $this->fail('No such thing here.', 404),
            };
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), 422);
        }
    }

    /* =============================================================== reads */

    private function boot()
    {
        $counts = Audience::counts();
        $lists = Seg::lists();
        $segs = [];
        foreach (Seg::LABELS as $k => [$name, $line]) {
            $segs[] = ['key' => $k, 'name' => $name, 'line' => $line, 'listId' => $lists[$k] ?? null, 'count' => $counts[$k] ?? 0,
                'group' => $k === Seg::ALL ? 'all' : (in_array($k, Seg::FLAGS, true) ? 'flag' : 'tier')];
        }
        $tabs = [];
        foreach (AsNlEmail::PRIORITIES as $p) {
            $tabs[$p] = [
                'line' => AsNlEmail::active()->where('priority', $p)->whereIn('status', array_merge(AsNlEmail::LIVE, ['draft']))->count(),
                'queued' => AsNlEmail::active()->where('priority', $p)->whereIn('status', AsNlEmail::LIVE)->count(),
            ];
        }
        $tick = AsSiteSetting::get('newsletter.tick');

        return $this->ok([
            'settings' => array_diff_key(NlSettings::all(), ['theme' => 1]) + ['newsAuto' => NewsEmail::auto()],
            'theme' => Theme::get(),
            'themeDefaults' => Theme::DEFAULTS,
            'fonts' => array_map(fn ($f) => $f[0], Theme::FONTS),
            'faces' => Renderer::FACES,
            'faceBase' => 'https://anee.io/images/anee/emoji/',
            'segments' => $segs,
            'tabs' => $tabs,
            'acumbamail' => app(AcumbamailService::class)->tokenSet(),
            'tick' => $tick,
            'tickAlive' => $tick && Carbon::parse($tick)->gt(now()->subMinutes(25)),
            'syncedAt' => AsSiteSetting::get('newsletter.synced_at'),
            'today' => Planner::now()->toDateString(),
            'plan' => $this->planData(),
        ]);
    }

    private function emails(Request $request)
    {
        $p = in_array($request->query('priority'), AsNlEmail::PRIORITIES, true) ? $request->query('priority') : 'regular';
        $scope = $request->query('scope') === 'done' ? 'done' : 'line';
        $q = trim((string) $request->query('q', ''));
        $rows = AsNlEmail::active()->where('priority', $p)
            ->whereIn('status', $scope === 'done' ? ['sent', 'expired'] : ['draft', 'queued', 'partial'])
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', "%{$q}%")->orWhere('subject', 'like', "%{$q}%")))
            ->when($scope === 'done', fn ($w) => $w->orderByDesc('lastSentAt')->orderByDesc('id')->limit(80), fn ($w) => $w->orderBy('position')->orderBy('id'))
            ->get();

        return $this->ok(['rows' => $rows->map(fn ($e) => $this->row($e))->values()]);
    }

    private function one(int $id)
    {
        $e = AsNlEmail::active()->findOrFail($id);
        $campaigns = DB::table('as_nl_campaigns')->where('emailId', $id)->orderByDesc('id')->limit(20)->get()
            ->map(fn ($c) => ['at' => (string) $c->created_at, 'cells' => Seg::words((array) json_decode((string) $c->cells, true)),
                'status' => $c->status, 'campaignId' => $c->acumbaCampaignId, 'error' => $c->error]);

        return $this->ok(['email' => $this->row($e) + ['content' => Renderer::clean($e->blocks ?? []), 'campaigns' => $campaigns]]);
    }

    private function row(AsNlEmail $e): array
    {
        $aud = $e->aud();

        return [
            'id' => $e->id, 'priority' => $e->priority, 'position' => $e->position, 'kind' => $e->kind,
            'title' => $e->title, 'subject' => $e->subject, 'preheader' => $e->preheader,
            'audience' => $aud, 'audienceWords' => Seg::words($aud),
            'sendOn' => $e->sendOn?->toDateString(), 'sendAt' => $e->sendAt?->format('Y-m-d H:i'),
            'newsDate' => $e->newsDate?->toDateString(),
            'status' => $e->status, 'sentCells' => $e->sent(), 'sentWords' => Seg::words($e->sent()),
            'lastSentAt' => $e->lastSentAt?->format('Y-m-d H:i'),
            'note' => $e->note, 'acumbaTemplateId' => $e->acumbaTemplateId,
            'acumbaName' => $e->meta['acumbaName'] ?? null, 'demo' => (bool) ($e->meta['demo'] ?? false),
            'updatedAt' => optional($e->updated_at)->format('Y-m-d H:i'), 'updatedBy' => $e->updatedBy ?: $e->createdBy,
        ];
    }

    private function planData(): array
    {
        $now = Planner::now();
        $plan = Planner::plan($now, null, null, Planner::takenToday());
        $run = DB::table('as_nl_runs')->where('runDate', $now->toDateString())->where('kind', 'daily')->orderByDesc('id')->first();
        $timed = AsNlEmail::active()->where('priority', 'high')->whereIn('status', array_merge(AsNlEmail::LIVE, ['draft']))->whereNotNull('sendAt')
            ->where('sendAt', '>=', $now->copy()->subHours(6))->orderBy('sendAt')->limit(10)->get()
            ->map(fn ($e) => ['id' => $e->id, 'title' => $e->title, 'at' => $e->sendAt->format('Y-m-d H:i'), 'status' => $e->status, 'audienceWords' => Seg::words($e->aud())]);
        $send = Carbon::parse($now->toDateString() . ' ' . NlSettings::sendTime(), 'Asia/Manila');

        return [
            'date' => $now->toDateString(),
            'sendAt' => $send->format('g:i A'),
            'sendPassed' => $now->gte($send),
            'enabled' => NlSettings::enabled(),
            'today' => $plan,
            'run' => $run ? ['id' => $run->id, 'status' => $run->status, 'error' => $run->error, 'finishedAt' => $run->finishedAt,
                'campaigns' => DB::table('as_nl_campaigns')->where('runId', $run->id)->get(['emailId', 'cells', 'acumbaCampaignId', 'status', 'error'])
                    ->map(fn ($c) => ['emailId' => $c->emailId, 'cells' => (array) json_decode((string) $c->cells, true), 'campaignId' => $c->acumbaCampaignId, 'status' => $c->status, 'error' => $c->error])] : null,
            'timed' => $timed,
            'ahead' => Planner::forecast(6),
        ];
    }

    private function runs(Request $request)
    {
        $cursor = (int) $request->query('cursor', 0);
        $rows = DB::table('as_nl_runs')->when($cursor > 0, fn ($q) => $q->where('id', '<', $cursor))->orderByDesc('id')->limit(21)->get();
        $more = $rows->count() > 20;
        $rows = $rows->take(20);
        $camps = DB::table('as_nl_campaigns')->whereIn('runId', $rows->pluck('id'))->get()->groupBy('runId');
        $titles = AsNlEmail::whereIn('id', $camps->flatten()->pluck('emailId')->unique())->pluck('title', 'id');

        return $this->ok([
            'rows' => $rows->map(function ($r) use ($camps, $titles) {
                $plan = json_decode((string) $r->plan, true) ?: [];

                return [
                    'id' => $r->id, 'date' => $r->runDate, 'kind' => $r->kind, 'status' => $r->status, 'error' => $r->error,
                    'at' => (string) ($r->finishedAt ?: $r->updated_at),
                    'picks' => array_map(fn ($p) => ['title' => $p['title'] ?? '', 'priority' => $p['priority'] ?? '', 'cells' => Seg::words($p['send'] ?? []), 'people' => $p['people'] ?? null], $plan['picks'] ?? []),
                    'campaigns' => ($camps[$r->id] ?? collect())->map(fn ($c) => ['title' => $titles[$c->emailId] ?? ('#' . $c->emailId), 'cells' => Seg::words((array) json_decode((string) $c->cells, true)),
                        'campaignId' => $c->acumbaCampaignId, 'status' => $c->status, 'error' => $c->error])->values(),
                ];
            })->values(),
            'nextCursor' => $more ? $rows->last()->id : null,
        ]);
    }

    private function audience(Request $request)
    {
        $q = trim(strtolower((string) $request->query('q', '')));
        $seg = (string) $request->query('seg', '');
        $rows = DB::table('as_nl_audience')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('email', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%")))
            ->when(isset(Seg::LABELS[$seg]), fn ($w) => $w->where('cells', 'like', '%"' . $seg . '"%'))
            ->orderByDesc('updated_at')->limit(60)->get();

        return $this->ok([
            'counts' => Audience::counts(),
            'total' => DB::table('as_nl_audience')->count(),
            'unsubscribed' => DB::table('as_nl_audience')->where('unsubscribed', 1)->count(),
            'withErrors' => DB::table('as_nl_audience')->whereNotNull('error')->where('unsubscribed', 0)->count(),
            'syncedAt' => AsSiteSetting::get('newsletter.synced_at'),
            'rows' => $rows->map(fn ($r) => [
                'email' => $r->email, 'name' => $r->name, 'member' => (bool) $r->userId,
                'cells' => Seg::words((array) json_decode((string) $r->cells, true)),
                'unsubscribed' => (bool) $r->unsubscribed, 'error' => $r->error, 'syncedAt' => $r->syncedAt,
            ]),
        ]);
    }

    /* ============================================================== writes */

    private function save(Request $request, string $who)
    {
        $in = (array) $request->input('email', []);
        $e = ! empty($in['id']) ? AsNlEmail::active()->findOrFail((int) $in['id']) : new AsNlEmail(['deleteStatus' => 1, 'createdBy' => $who, 'status' => 'draft']);
        $priority = in_array($in['priority'] ?? '', AsNlEmail::PRIORITIES, true) ? $in['priority'] : ($e->priority ?: 'regular');
        if ($e->exists && $e->isTimed() && $e->status === 'sent') {
            throw new \RuntimeException('This one is already with Acumbamail for its time, so it can no longer change. Duplicate it to send something else.');
        }
        $kind = ($in['kind'] ?? $e->kind) === 'acumba' ? 'acumba' : 'builder';
        $subject = mb_substr(trim(strip_tags((string) ($in['subject'] ?? ''))), 0, 250);
        $title = mb_substr(trim(strip_tags((string) ($in['title'] ?? ''))), 0, 190) ?: ($subject ?: 'Untitled email');
        $today = Planner::now()->toDateString();

        $sendOn = $this->date($in['sendOn'] ?? null);
        $sendAt = $this->dateTime($in['sendAt'] ?? null);
        $newsDate = $this->date($in['newsDate'] ?? null);
        if ($priority === 'news') {
            $sendOn = null;
            $sendAt = null;
            $newsDate = $newsDate ?: $today;
        } elseif ($priority === 'regular') {
            $sendAt = null;
            $newsDate = null;
        } else {
            $newsDate = null;
            if ($sendAt) {
                $sendOn = null;
            }
        }
        $status = in_array($in['status'] ?? '', ['draft', 'queued'], true) ? $in['status'] : ($e->status ?: 'draft');
        if ($status === 'queued') {
            if ($kind === 'builder' && $subject === '') {
                throw new \RuntimeException('Give it a subject line before it joins the line.');
            }
            if ($sendOn && $sendOn < $today) {
                throw new \RuntimeException('Its day has passed. Pick a new day or clear it.');
            }
            if ($sendAt && Carbon::parse($sendAt, 'Asia/Manila')->lt(Planner::now())) {
                throw new \RuntimeException('Its time has passed. Pick a new time.');
            }
            if ($priority === 'news' && $newsDate !== $today) {
                throw new \RuntimeException('News goes only on its own day: set the day to today, or keep it as a draft.');
            }
        }
        // A partial email keeps going to the lists it has not reached; anything else starts over in the line.
        if ($status === 'queued' && $e->status === 'partial') {
            $status = 'partial';
        }

        $e->fill([
            'priority' => $priority, 'kind' => $kind, 'title' => $title, 'subject' => $subject,
            'preheader' => mb_substr(trim(strip_tags((string) ($in['preheader'] ?? ''))), 0, 250) ?: null,
            'audience' => Seg::normalize((array) ($in['audience'] ?? [])),
            'sendOn' => $sendOn, 'sendAt' => $sendAt, 'newsDate' => $newsDate,
            'status' => $status, 'updatedBy' => $who,
            'note' => array_key_exists('note', $in) ? (mb_substr(trim(strip_tags((string) $in['note'])), 0, 600) ?: null) : $e->note,
        ]);
        if ($kind === 'acumba') {
            $tid = (int) ($in['acumbaTemplateId'] ?? $e->acumbaTemplateId);
            if ($tid <= 0) {
                throw new \RuntimeException('An Acumbamail template needs its id.');
            }
            $e->acumbaTemplateId = $tid;
        } else {
            $e->blocks = Renderer::clean($in['content'] ?? []);
        }
        if (! $e->exists || $e->isDirty('priority')) {
            $e->position = $priority === 'news' ? 0
                : (int) AsNlEmail::active()->where('priority', $priority)->max('position') + 10;
        }
        $e->save();

        return $this->ok(['email' => $this->row($e->fresh()) + ['content' => Renderer::clean($e->blocks ?? [])]], $status === 'draft' ? 'Saved as a draft.' : 'Saved. It is in the line.');
    }

    private function act(Request $request, int $id, string $what, string $who, string $whoEmail)
    {
        $e = AsNlEmail::active()->findOrFail($id);
        switch ($what) {
            case 'delete':
                $e->update(['deleteStatus' => 0, 'updatedBy' => $who]);

                return $this->ok([], 'Removed.');
            case 'duplicate':
                $c = $e->replicate(['sentCells', 'lastSentAt', 'newsPageId']);
                $c->fill(['title' => mb_substr('Copy of ' . $e->title, 0, 190), 'status' => 'draft', 'createdBy' => $who, 'updatedBy' => $who,
                    'position' => (int) AsNlEmail::active()->where('priority', $e->priority)->max('position') + 10,
                    'meta' => array_diff_key((array) $e->meta, ['demo' => 1])]);
                $c->save();

                return $this->ok(['email' => $this->row($c)], 'Copied as a draft.');
            case 'status':
                $to = (string) $request->input('status');
                if (! in_array($to, ['draft', 'queued'], true)) {
                    throw new \RuntimeException('Only "in line" or "draft".');
                }
                if ($to === 'queued') {
                    return $this->save(new Request(['email' => array_merge($this->row($e), ['content' => $e->blocks, 'status' => 'queued'])]), $who);
                }
                $e->update(['status' => 'draft', 'updatedBy' => $who]);

                return $this->ok(['email' => $this->row($e)], 'Out of the line, kept as a draft.');
            case 'move':
                $to = (string) $request->input('priority');
                if (! in_array($to, AsNlEmail::PRIORITIES, true) || $to === $e->priority) {
                    throw new \RuntimeException('Pick another priority.');
                }
                $fields = ['priority' => $to, 'status' => 'draft', 'updatedBy' => $who,
                    'position' => $to === 'news' ? 0 : (int) AsNlEmail::active()->where('priority', $to)->max('position') + 10];
                if ($to === 'news') {
                    $fields += ['sendOn' => null, 'sendAt' => null, 'newsDate' => Planner::now()->toDateString()];
                } elseif ($to === 'regular') {
                    $fields += ['sendAt' => null, 'newsDate' => null];
                } else {
                    $fields += ['newsDate' => null];
                }
                $e->update($fields);

                return $this->ok(['email' => $this->row($e)], 'Moved, as a draft: check it and put it in the line.');
            case 'test':
                return $this->test($e, (string) $request->input('to', ''), $whoEmail, $request->input('content'));
        }

        return $this->fail('No such thing here.', 404);
    }

    /** A test send of one email to one address: Resend for the builder's, Acumbamail's test list for its own templates. */
    private function test(AsNlEmail $e, string $to, string $whoEmail, $content = null)
    {
        $to = trim($to) ?: (NlSettings::testTo() ?: $whoEmail);
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Type the address the test should go to.');
        }
        $subject = '[Test] ' . (trim((string) $e->subject) ?: $e->title);

        if ($e->kind === 'acumba') {
            $am = app(AcumbamailService::class);
            $list = $this->testList($am, $to);
            $res = $am->sendTemplateCampaign('anee.io test · ' . mb_substr($e->title, 0, 80), NlSettings::fromName(), NlSettings::fromEmail(), $subject, (int) $e->acumbaTemplateId, [$list]);
            if (! $res['id']) {
                throw new \RuntimeException('Acumbamail did not send the test: ' . $res['error']);
            }

            return $this->ok(['to' => $to], 'Test sent through Acumbamail to ' . $to . '. It can take a few minutes.');
        }

        $html = Renderer::html(['subject' => $subject, 'preheader' => $e->preheader, 'content' => $content !== null ? $content : $e->blocks], Theme::get(), 'test');
        $mailer = app(ResendMailer::class);
        if (! $mailer->send($to, '', $subject, $html)) {
            throw new \RuntimeException('The test did not go: ' . ($mailer->lastError() ?: 'the mail service refused it.'));
        }

        return $this->ok(['to' => $to], 'Test sent to ' . $to . '.');
    }

    /**
     * Acumbamail's own test list, holding only the test address: an Acumbamail
     * template can only be sent by Acumbamail, and only to a list.
     */
    private function testList(AcumbamailService $am, string $to): int
    {
        $id = (int) AsSiteSetting::get('newsletter.test_list', 0);
        if ($id <= 0) {
            $id = (int) $am->createList('anee_io_test_sends', NlSettings::fromEmail());
            if ($id <= 0) {
                throw new \RuntimeException('Acumbamail would not make the test list: ' . $am->lastError());
            }
            AsSiteSetting::put('newsletter.test_list', (string) $id);
        }
        $people = (array) json_decode((string) AsSiteSetting::get('newsletter.test_list_people', '[]'), true);
        foreach ($people as $old) {
            if (strcasecmp($old, $to) !== 0) {
                $am->listRemove($id, $old);
            }
        }
        if (! $am->listAdd($id, $to)) {
            throw new \RuntimeException('Acumbamail would not take the test address: ' . $am->lastError());
        }
        AsSiteSetting::put('newsletter.test_list_people', json_encode([$to]));

        return $id;
    }

    private function reorder(Request $request)
    {
        $p = (string) $request->input('priority');
        $ids = array_values(array_filter(array_map('intval', (array) $request->input('ids', []))));
        if (! in_array($p, AsNlEmail::PRIORITIES, true) || ! $ids) {
            throw new \RuntimeException('Nothing to order.');
        }
        $known = AsNlEmail::active()->where('priority', $p)->whereIn('id', $ids)->pluck('id')->all();
        DB::transaction(function () use ($ids, $known) {
            $pos = 10;
            foreach ($ids as $id) {
                if (in_array($id, $known, true)) {
                    AsNlEmail::whereKey($id)->update(['position' => $pos]);
                    $pos += 10;
                }
            }
        });

        return $this->ok(['plan' => $this->planData()], 'Order saved.');
    }

    private function preview(Request $request)
    {
        $in = (array) $request->input('email', []);
        $theme = $request->has('theme') ? Theme::clean(array_merge(Theme::get(), (array) $request->input('theme'))) : Theme::get();
        $html = Renderer::html([
            'subject' => (string) ($in['subject'] ?? ''),
            'preheader' => (string) ($in['preheader'] ?? ''),
            'content' => $in['content'] ?? [],
        ], $theme, $request->input('mode') === 'test' ? 'test' : 'preview', '', (string) $request->input('scheme', 'light'));

        return $this->ok(['html' => $html, 'text' => $request->boolean('withText') ? Renderer::text($html) : null]);
    }

    private function settings(Request $request)
    {
        $in = (array) $request->input('settings', []);
        $before = NlSettings::enabled();
        NlSettings::save($in);
        if (array_key_exists('newsAuto', $in)) {
            AsSiteSetting::put('newsletter.news_auto', $in['newsAuto'] ? '1' : '0');
        }
        $now = NlSettings::enabled();
        $say = $now === $before ? 'Settings saved.' : ($now ? 'Sending is ON: the next daily send makes real campaigns.' : 'Sending is OFF: the days are planned and shown, nothing goes.');

        return $this->ok(['settings' => array_diff_key(NlSettings::all(), ['theme' => 1]) + ['newsAuto' => NewsEmail::auto()]], $say);
    }

    private function sync()
    {
        $s = Audience::sync(null, 50);
        $say = "Lists checked: {$s['added']} added, {$s['removed']} moved off" . ($s['failed'] ? ", {$s['failed']} refused" : '') . '.';

        return $this->ok(['sync' => $s, 'counts' => Audience::counts()], $s['errors'] ? $say . ' ' . $s['errors'][0] : $say);
    }

    private function import(string $who)
    {
        $r = SequenceImport::run(false, $who);
        if ($r['error']) {
            throw new \RuntimeException('Acumbamail did not list its templates: ' . $r['error']);
        }

        return $this->ok(['added' => $r['added'], 'found' => $r['found']], $r['added'] ? $r['added'] . ' templates added to the regular line.' : 'Every template is already here.');
    }

    private function newsNow()
    {
        $e = NewsEmail::ensure();
        if (! $e) {
            throw new \RuntimeException('There is no farm news page from today yet. The news roundup writes one at 7:00 AM when the feeds have new stories.');
        }

        return $this->ok(['email' => $this->row($e)], 'Today\'s news email is ready.');
    }

    private function upload(Request $request)
    {
        $file = $request->file('file');
        if (! $file || ! $file->isValid()) {
            throw new \RuntimeException('No picture came with that upload.');
        }
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            throw new \RuntimeException('JPG, PNG or GIF: those show in every inbox.');
        }
        if ($file->getSize() > 6_000_000) {
            throw new \RuntimeException('Under 6 MB, please. Big pictures load slowly in an inbox.');
        }
        $path = MediaStore::putFile($file, 'newsletter');
        $url = $path ? MediaStore::url($path) : null;
        if (! $url) {
            throw new \RuntimeException('The picture could not be stored.');
        }
        if (! Str::startsWith($url, ['http://', 'https://'])) {
            $url = rtrim((string) config('app.url'), '/') . '/' . ltrim($url, '/');
        }

        return $this->ok(['url' => $url]);
    }

    /* ============================================================= helpers */

    private function date($v): ?string
    {
        $v = trim((string) $v);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
    }

    private function dateTime($v): ?string
    {
        $v = str_replace('T', ' ', trim((string) $v));

        return preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', substr($v, 0, 16)) ? substr($v, 0, 16) . ':00' : null;
    }

    private function ok(array $data, string $message = 'ok')
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    private function fail(string $message, int $status)
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
