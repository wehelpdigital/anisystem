<?php

namespace App\Models;

class WorkerGrant extends BaseModel
{
    protected $table = 'as_worker_grants';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVOKED = 'revoked';

    /**
     * The modules a grant answers for, and the shape of each answer.
     *
     * 'level' modules take the schedule's own none/view/edit; 'open' modules
     * are had or not had. One list, so the form, the validator, the payload
     * and the gate cannot drift into knowing different modules — which is how
     * a permission ends up being a setting that does nothing.
     */
    public const MODULES = [
        'notes'     => ['column' => 'notesAccess',     'shape' => 'level'],
        'reports'   => ['column' => 'reportsAccess',   'shape' => 'level'],
        'inventory' => ['column' => 'inventoryAccess', 'shape' => 'level'],
        // What came off the season: yields, prices, problems, lessons.
        'observations' => ['column' => 'observationsAccess', 'shape' => 'level'],
        // The season's pesos on the board: task costs, pay, the day's cash, expenses, income, prices (MoneyView).
        'money'     => ['column' => 'moneyAccess',     'shape' => 'level'],
        'maps'      => ['column' => 'mapsAccess',      'shape' => 'level'],
        'draw'      => ['column' => 'drawAccess',      'shape' => 'level'],
        'ai'        => ['column' => 'aiAccess',        'shape' => 'open'],
        'camera'    => ['column' => 'cameraAccess',    'shape' => 'open'],
        'video'     => ['column' => 'videoAccess',     'shape' => 'open'],
        // Speaking a note is its own errand: a farmer with their hands full
        // says a sentence, and that is not the same as being handed a camera.
        'voice'     => ['column' => 'voiceAccess',     'shape' => 'open'],
    ];

    protected $fillable = [
        'bossUserId', 'workerUserId', 'scheduleWorkerId', 'globalWorkerId', 'invitedEmail',
        'inviteToken', 'scheduleAccess', 'canAddNotes', 'communityAccess', 'status',
        'notesAccess', 'reportsAccess', 'inventoryAccess', 'observationsAccess', 'moneyAccess', 'mapsAccess', 'drawAccess', 'aiAccess',
        'cameraAccess', 'videoAccess', 'voiceAccess', 'toolsOff',
        'acceptedAt', 'deleteStatus',
    ];

    protected $casts = [
        'canAddNotes' => 'boolean',
        'communityAccess' => 'boolean',
        'aiAccess' => 'boolean',
        'cameraAccess' => 'boolean',
        'videoAccess' => 'boolean',
        'voiceAccess' => 'boolean',
        // The home screen's tools this login has switched off (WorkerTools keys); empty is all on.
        'toolsOff' => 'array',
        'acceptedAt' => 'datetime',
        'deleteStatus' => 'integer',
    ];

    public function boss()
    {
        return $this->belongsTo(User::class, 'bossUserId');
    }

    public function workerUser()
    {
        return $this->belongsTo(User::class, 'workerUserId');
    }

    public function scopeActive($q)
    {
        return $q->where('as_worker_grants.deleteStatus', 1);
    }

    /**
     * The one grant that is this person, out of a boss's grants.
     *
     * Asked by the farm's roster (Global Workers) and by every season's
     * Workers page, in the same order, so both pages always point at the
     * same grant: the roster person it names (globalWorkerId), else one of
     * their season cards (scheduleWorkerId), else the address it was sent to.
     * A season card passes its own id and its roster person; the roster
     * passes the person and all of their cards.
     *
     * @param  \Illuminate\Support\Collection<int, self>  $grants
     * @param  array<int, int>  $cardIds
     */
    public static function findFor($grants, ?int $globalWorkerId, array $cardIds, ?string $email): ?self
    {
        if ($globalWorkerId) {
            $g = $grants->first(fn ($x) => (int) $x->globalWorkerId === $globalWorkerId);
            if ($g) {
                return $g;
            }
        }
        $cardIds = array_map('intval', array_filter($cardIds));
        if ($cardIds) {
            $g = $grants->first(fn ($x) => $x->scheduleWorkerId && in_array((int) $x->scheduleWorkerId, $cardIds, true));
            if ($g) {
                return $g;
            }
        }
        $email = mb_strtolower(trim((string) $email));

        return $email === '' ? null : $grants->first(fn ($x) => mb_strtolower((string) $x->invitedEmail) === $email);
    }

    /** A grant that names neither a season card nor a roster person: it was made farm wide. */
    public function isFarmWide(): bool
    {
        return blank($this->scheduleWorkerId) && blank($this->globalWorkerId);
    }

    /*
     * WHERE THEY MAY WORK, AND WITH WHAT (2026-10-22, the owner: "the farm
     * owner can manage what can a user access, but can specify by cropping
     * schedule, or all").
     *
     * The rights left this row for WorkerAccess: one setting for "all
     * cropping schedules" and any number for one season each, a season's own
     * coming first there. Every question below takes the season it is about:
     *   an id      that season's own setting, else the "all" one, else none;
     *   null       the farm as a whole (the home screen, Global Notes, a tool
     *              run): the "all" setting when there is one, else the most
     *              the season settings give somewhere;
     *   'pending'  a season page whose season is not read yet: the most any
     *              setting gives, so the gate does not refuse what the season
     *              may allow (WorkerContext::enterSeason asks again once known).
     * The old rights columns on this row stay for the migration's sake only.
     */

    /** @var \Illuminate\Support\Collection<int, WorkerAccess>|null */
    private $accessCache = null;

    /** This login's access settings (cached on the row for the request). */
    public function accessRows()
    {
        return $this->accessCache ??= WorkerAccess::active()->where('grantId', (int) $this->id)->get();
    }

    /** Forget the cached settings, after one was written. */
    public function forgetAccess(): void
    {
        $this->accessCache = null;
    }

    /** The "all cropping schedules" setting, if they have one. */
    public function accessAll(): ?WorkerAccess
    {
        return $this->accessRows()->first(fn ($r) => $r->isAll());
    }

    /** The setting that answers for a season (see the note above), or null for no access. */
    public function rightsFor($season = null): ?WorkerAccess
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return null;
        }
        $rows = $this->accessRows();
        if ($rows->isEmpty()) {
            return null;
        }
        if (is_int($season)) {
            return $rows->first(fn ($r) => $r->croppingScheduleId === $season) ?? $this->accessAll();
        }
        if ($season === 'pending') {
            return WorkerAccess::merge($rows);
        }

        return $this->accessAll() ?? WorkerAccess::merge($rows);
    }

    /**
     * The seasons this login opens: null for every one of the farm (an "all"
     * setting), else the ones with a setting of their own.
     *
     * @return array<int, int>|null
     */
    public function seasonIds(): ?array
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return [];
        }
        if ($this->accessAll()) {
            return null;
        }

        return $this->accessRows()->pluck('croppingScheduleId')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /** Where they may work, in words: "every season, can edit the plan" / "2 seasons" / "no season yet". */
    public function reachSay(): string
    {
        $all = $this->accessAll();
        if ($all) {
            return 'every season, ' . ($all->rights()['scheduleAccess'] === 'edit' ? 'can edit the plan' : 'view only');
        }
        // Counted from the settings, not seasonIds(): an invite not yet accepted already has its places.
        $n = $this->accessRows()->pluck('croppingScheduleId')->filter()->unique()->count();

        return $n === 0 ? 'no season yet' : ($n === 1 ? '1 season' : $n . ' seasons');
    }

    public function canEditSchedules($season = null): bool
    {
        return $this->moduleAccess('activities', $season) === 'edit';
    }

    /**
     * What this login says about one module, in a season: 'none', 'view' or
     * 'edit'. An open/shut module answers in the same words so a caller never
     * has to know which shape it is: shut is 'none', open is 'edit'. No
     * setting for the season is no access to anything in it.
     */
    public function moduleAccess(string $key, $season = null): string
    {
        $rights = $this->rightsFor($season);

        return $rights ? $rights->moduleAccess($key) : 'none';
    }

    /** May they open this module at all? */
    public function mayUseModule(string $key, $season = null): bool
    {
        return $this->moduleAccess($key, $season) !== 'none';
    }

    /** May they add to it, not only look at it? */
    public function mayWriteModule(string $key, $season = null): bool
    {
        return $this->moduleAccess($key, $season) === 'edit';
    }

    /**
     * May they see and open one of the home screen's tools on this farm?
     *
     * The owner switches tools off, not on (2026-10-22): a login keeps every
     * tool until one is turned off, so a tool added to the home screen later
     * reaches the crew without anybody revisiting their switches. A login
     * question, not a season's: the home screen is the farm's.
     */
    public function mayUseTool(string $key): bool
    {
        return ! in_array($key, (array) ($this->toolsOff ?? []), true);
    }

    /**
     * May this worker write the day's notes?
     *
     * Separate from editing the plan on purpose: recording what happened in
     * a field is not the same act as changing what is supposed to happen,
     * and most farms want the first without the second.
     */
    public function canAddNotes($season = null): bool
    {
        return $this->mayWriteModule('notes', $season);
    }

    /** May they see this season (or, with null, any season of the farm)? */
    public function canViewSchedules($season = null): bool
    {
        return $this->rightsFor($season) !== null;
    }
}
