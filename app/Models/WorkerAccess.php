<?php

namespace App\Models;

use Illuminate\Support\Collection;

/**
 * What a worker may open, for one scope (2026-10-22, the owner: "in the global
 * workers, the farm owner can manage what can a user access, but can specify
 * by cropping schedule, or all").
 *
 * A login (WorkerGrant) is who the person is and whether they can sign in;
 * these rows are where they may work and with which rights. The row with no
 * croppingScheduleId is "all cropping schedules"; a row naming a season is
 * that season's own setting and comes first there (WorkerGrant::rightsFor).
 * The columns are the ones WorkerGrant::MODULES names, plus the board's own
 * scheduleAccess, so one vocabulary still runs from the panel to the gate.
 */
class WorkerAccess extends BaseModel
{
    protected $table = 'as_worker_access';

    /** What a new setting starts with when there is nothing to copy: the plan and the notebook to read, the owner's tools shut. */
    public const DEFAULTS = [
        'scheduleAccess' => 'view',
        'notesAccess' => 'view',
        'reportsAccess' => 'view',
        'inventoryAccess' => 'none',
        'observationsAccess' => 'none',
        'moneyAccess' => 'none',
        'mapsAccess' => 'none',
        'drawAccess' => 'none',
        'aiAccess' => false,
        'cameraAccess' => false,
        'videoAccess' => false,
        'voiceAccess' => false,
    ];

    private const RANK = ['none' => 0, 'view' => 1, 'edit' => 2];

    protected $fillable = [
        'grantId', 'bossUserId', 'croppingScheduleId',
        'scheduleAccess', 'notesAccess', 'reportsAccess', 'inventoryAccess', 'observationsAccess', 'moneyAccess', 'mapsAccess', 'drawAccess',
        'aiAccess', 'cameraAccess', 'videoAccess', 'voiceAccess', 'deleteStatus',
    ];

    protected $casts = [
        'grantId' => 'integer',
        'bossUserId' => 'integer',
        'croppingScheduleId' => 'integer',
        'aiAccess' => 'boolean',
        'cameraAccess' => 'boolean',
        'videoAccess' => 'boolean',
        'voiceAccess' => 'boolean',
        'deleteStatus' => 'integer',
    ];

    public function grant()
    {
        return $this->belongsTo(WorkerGrant::class, 'grantId');
    }

    public function season()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    /** Every cropping schedule of the farm, rather than one. */
    public function isAll(): bool
    {
        return $this->croppingScheduleId === null;
    }

    /** The rights this row carries, by column (the shape the panel reads and the endpoints write). */
    public function rights(): array
    {
        $out = [];
        foreach (self::DEFAULTS as $col => $default) {
            $out[$col] = is_bool($default) ? (bool) $this->{$col} : (string) ($this->{$col} ?: $default);
        }
        // Activities has no None: a setting is a place to work, so the least it gives is a look.
        if (! in_array($out['scheduleAccess'], ['view', 'edit'], true)) {
            $out['scheduleAccess'] = 'view';
        }

        return $out;
    }

    /**
     * What this setting says about one module: 'none', 'view' or 'edit', in
     * the words WorkerGrant::moduleAccess always answered in. 'activities' is
     * the board itself.
     */
    public function moduleAccess(string $key): string
    {
        if ($key === 'activities') {
            return $this->rights()['scheduleAccess'];
        }
        $spec = WorkerGrant::MODULES[$key] ?? null;
        if (! $spec) {
            return 'none';
        }
        if ($spec['shape'] === 'open') {
            return $this->{$spec['column']} ? 'edit' : 'none';
        }
        $level = (string) ($this->{$spec['column']} ?? 'none');

        return isset(self::RANK[$level]) ? $level : 'none';
    }

    /**
     * The most a set of settings gives, as one unsaved row: what a worker may
     * reach somewhere on the farm. Asked where no one season is in question
     * yet (WorkerGrant::rightsFor), never saved.
     *
     * @param  Collection<int, self>  $rows
     */
    public static function merge(Collection $rows): self
    {
        $out = new self(self::DEFAULTS);
        foreach (self::DEFAULTS as $col => $default) {
            if (is_bool($default)) {
                $out->{$col} = $rows->contains(fn ($r) => (bool) $r->{$col});
            } else {
                $best = $col === 'scheduleAccess' ? 'view' : 'none';
                foreach ($rows as $r) {
                    $level = $r->rights()[$col];
                    if (self::RANK[$level] > self::RANK[$best]) {
                        $best = $level;
                    }
                }
                $out->{$col} = $best;
            }
        }

        return $out;
    }

    /** A short line of what it gives, for a list: "Activities: can edit · Notes · Money: view". */
    public function summary(): string
    {
        $r = $this->rights();
        $parts = ['Activities: ' . ($r['scheduleAccess'] === 'edit' ? 'can edit' : 'view only')];
        $names = ['notesAccess' => 'Notes', 'reportsAccess' => 'Reports', 'inventoryAccess' => 'Inventory', 'observationsAccess' => 'Observations',
            'moneyAccess' => 'Money', 'mapsAccess' => 'Maps', 'drawAccess' => 'Draw', 'aiAccess' => 'Anee', 'cameraAccess' => 'Camera',
            'videoAccess' => 'Video', 'voiceAccess' => 'Voice'];
        foreach ($names as $col => $name) {
            $v = $r[$col];
            if ($v === true || $v === 'view' || $v === 'edit') {
                $parts[] = $name . ($v === 'view' ? ' (view)' : '');
            }
        }

        return implode(' · ', $parts);
    }
}
