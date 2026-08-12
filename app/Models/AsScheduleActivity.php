<?php

namespace App\Models;

class AsScheduleActivity extends BaseModel
{
    protected $table = 'as_schedule_activities';

    /**
     * Canonical catalog of activity types. Keys are the slugs stored in the
     * activityType column, values are the human-readable labels rendered in
     * the UI. Single source of truth — controller validation, view rendering,
     * the modal select, and the auto-categorizer all read from here.
     */
    public const ACTIVITY_TYPES = [
        'equipment_prep' => 'Equipment Preparation',
        'land_prep'      => 'Land Preparation',
        'seed_treatment' => 'Seed Treatment',
        'planting'       => 'Planting',
        'irrigation'     => 'Irrigation',
        'service'        => 'Service',
        'fertilizer'     => 'Fertilizer (Granular)',
        'foliar_spray'   => 'Foliar Spray',
        'herbicide'      => 'Herbicide',
        'pesticide'      => 'Pesticide / Insecticide',
        'copper_fungicide' => 'Copper-based Fungicide / Bactericide',
        'fungicide'      => 'Fungicide',
        'microbial'      => 'Microbial / Bio',
        'harvest'        => 'Harvest',
        'monitoring'     => 'Monitoring',
        // Paid work priced per worker rather than by the length of the task:
        // who was there, for how much of the day, and at what rate.
        'worker_payroll' => 'Worker Checklist',
        // Things a day needs that are nobody's task and nobody's wage: a
        // permit to collect, a delivery to chase, a payment to make. Each
        // line can carry money, and ticking it is what makes that money real.
        'reminder_checklist' => 'Reminder Checklist',
        'other'          => 'Other',
    ];

    /**
     * Water-task catalog for irrigation-type activities (activityType =
     * 'irrigation'). slug => label, plus a color for the card badge.
     */
    public const WATER_TASKS = [
        'irrigate'      => 'Irrigate',
        'maintain'      => 'Maintain water',
        'overflow'      => 'Overflow',
        'drain'         => 'Drain',
        'drain_water'   => 'Drain water',
        'no_irrigation' => 'No irrigation',
        'let_subside'   => 'Let subside',
    ];

    public const WATER_TASK_COLORS = [
        'irrigate'      => '#2f8fd8',
        'maintain'      => '#1aa3a3',
        'overflow'      => '#7c6bd6',
        'drain'         => '#c1873b',
        'drain_water'   => '#c1873b',
        'no_irrigation' => '#8a95a8',
        'let_subside'   => '#5a8f4c',
    ];

    protected $fillable = [
        'croppingScheduleId',
        'versionId',
        'sourceActivityId',
        'activityTitle',
        'targetDate',
        'targetEndDate',
        'priority',
        'activityType',
        'waterTask',
        'servicePrice',
        'isDayZero',
        'isTransplant',
        'isDraft',
        'isHidden',
        'isDone',
        'description',
        'imagePath',
        'imagePaths',
        'tags',
        'reminders',
        'timeRequired',
        'sequenceOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'targetDate' => 'date:Y-m-d',
        'targetEndDate' => 'date:Y-m-d',
        'servicePrice' => 'decimal:2',
        'imagePaths' => 'array',
        'tags' => 'array',
        'reminders' => 'array',
        'isDayZero' => 'boolean',
        'isTransplant' => 'boolean',
        'isDraft' => 'boolean',
        'isHidden' => 'boolean',
        'isDone' => 'boolean',
        'sequenceOrder' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    /**
     * The checklist, cleaned up: every line has text, a kind and an amount,
     * whatever shape it was stored in. Reading it anywhere else can then be
     * a loop and nothing more.
     *
     * @return array<int, array{text: string, kind: string, amount: float, done: bool}>
     */
    public function reminderList(): array
    {
        $out = [];
        foreach ((array) ($this->reminders ?? []) as $row) {
            $text = trim((string) ($row['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $kind = in_array($row['kind'] ?? '', ['expense', 'income'], true) ? $row['kind'] : 'none';
            $out[] = [
                'text' => $text,
                'kind' => $kind,
                'amount' => $kind === 'none' ? 0.0 : round((float) ($row['amount'] ?? 0), 2),
                'done' => (bool) ($row['done'] ?? false),
            ];
        }

        return $out;
    }

    /** What the ticked lines add up to, one way or the other. */
    public function reminderTotal(string $kind): float
    {
        return (float) array_sum(array_map(
            fn ($r) => $r['done'] && $r['kind'] === $kind ? $r['amount'] : 0,
            $this->reminderList()
        ));
    }

    public function isReminderChecklist(): bool
    {
        return $this->activityType === 'reminder_checklist';
    }

    /** What one worker on this activity is owed. */
    public function workerPay(AsScheduleWorker $worker): float
    {
        $pivot = $worker->pivot ?? null;
        $custom = $pivot?->salaryAmount;
        if ($custom !== null && $custom !== '') {
            return (float) $custom;          // an agreed figure beats any rule
        }

        $half = (float) ($worker->costPerHalfDay ?? 0);

        // A worker checklist is a day's attendance, not a slice of a task:
        // being on the list means a day's pay. Half and whole simply do not
        // apply there, which is why the checklist never asks.
        if ($this->activityType === 'worker_payroll') {
            return $half * 2;
        }

        $part = $this->dayPartFor($worker);
        if ($part === null) {
            return 0.0;                      // no basis to work one out
        }

        return $part === 'half' ? $half : $half * 2;
    }

    /**
     * Half a day, a whole one, or neither.
     *
     * Their own answer where the checklist has one; otherwise however long the
     * task itself is. A task marked "N/A" has no length to inherit — that is
     * what N/A means — so nothing is assumed and nothing is charged. Guessing
     * half a day there quietly put money on days nobody had costed.
     */
    public function dayPartFor(AsScheduleWorker $worker): ?string
    {
        $chosen = $worker->pivot->dayPart ?? null;
        if ($chosen === 'half' || $chosen === 'whole') {
            return $chosen;
        }

        return in_array($this->timeRequired, ['half', 'whole'], true) ? $this->timeRequired : null;
    }

    /** The wage bill for this activity. */
    public function labourTotal(): float
    {
        return (float) $this->workers->sum(fn ($w) => $this->workerPay($w));
    }

    public function version()
    {
        return $this->belongsTo(AsScheduleActivityVersion::class, 'versionId');
    }

    public function sourceActivity()
    {
        return $this->belongsTo(self::class, 'sourceActivityId');
    }

    public function scopeForVersion($q, $versionId)
    {
        return $q->where('versionId', $versionId);
    }

    public function items()
    {
        return $this->hasMany(AsScheduleActivityItem::class, 'activityId')->where('as_schedule_activity_items.deleteStatus', 1);
    }

    public function lots()
    {
        return $this->belongsToMany(
            AsScheduleLot::class,
            'as_schedule_activity_lots',
            'activityId',
            'lotId'
        );
    }

    public function workers()
    {
        return $this->belongsToMany(
            AsScheduleWorker::class,
            'as_schedule_activity_workers',
            'activityId',
            'workerId'
        // Whether they were on it for a whole day or a half, and what was
        // agreed if it was not their usual rate.
        )->withPivot(['dayPart', 'salaryAmount']);
    }

    /**
     * Public URL for the activity's reference image (or null if none).
     * Path is stored relative to the `public` disk so asset('storage/...')
     * gives the publicly-accessible URL after `storage:link` has run.
     */
    public function imageUrl(): ?string
    {
        if (empty($this->imagePath)) return null;
        return asset('storage/' . ltrim($this->imagePath, '/'));
    }

    /**
     * Absolute filesystem path for embedding via base64 (used by
     * server-rendered printable documents where remote URLs would
     * round-trip via headless Chrome). Returns null if file is missing.
     */
    public function imageAbsolutePath(): ?string
    {
        if (empty($this->imagePath)) return null;
        $full = storage_path('app/public/' . ltrim($this->imagePath, '/'));
        return file_exists($full) ? $full : null;
    }

    /**
     * All reference-image paths — the JSON list if present, else the single
     * legacy imagePath. Always an array (possibly empty).
     *
     * @return array<int, string>
     */
    public function imagePathList(): array
    {
        if (! empty($this->imagePaths) && is_array($this->imagePaths)) {
            return array_values(array_filter($this->imagePaths));
        }
        return array_values(array_filter([$this->imagePath]));
    }

    /**
     * Reference images as [{path, url}] for the front-end.
     *
     * @return array<int, array{path:string, url:string}>
     */
    public function imageList(): array
    {
        return array_map(fn ($p) => [
            'path' => $p,
            'url' => asset('storage/' . ltrim($p, '/')),
        ], $this->imagePathList());
    }

    /** Label + color for an irrigation activity's water task (or null). */
    public function waterTaskMeta(): ?array
    {
        if ($this->activityType !== 'irrigation') return null;
        $slug = $this->waterTask && isset(self::WATER_TASKS[$this->waterTask]) ? $this->waterTask : 'irrigate';
        return [
            'slug' => $slug,
            'label' => self::WATER_TASKS[$slug],
            'color' => self::WATER_TASK_COLORS[$slug] ?? '#2f8fd8',
        ];
    }
}
