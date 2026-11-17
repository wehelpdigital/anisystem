<?php

namespace App\Models;

/**
 * A free-form note attached to a cropping schedule — a title, rich-text body
 * and an optional photo. Distinct from per-date activity notes; these are the
 * grower's own running notebook for the plan.
 */
class AsScheduleNote extends BaseModel
{
    use Concerns\HeldByFarm;

    protected $table = 'as_schedule_notes';

    protected $fillable = [
        'croppingScheduleId',
        'userId',
        'title',
        'body',
        'imagePath',
        'media',
        'tags',
        'sortOrder',
        'deleteStatus',
        // The day the note is about and the season lot it pertains to (2026-11-18, the owner). Null lot = the season.
        'noteDate',
        'lotId',
    ];

    protected $casts = [
        'media' => 'array',
        'tags' => 'array',
        'sortOrder' => 'integer',
        'deleteStatus' => 'integer',
        'noteDate' => 'date:Y-m-d',
        'lotId' => 'integer',
    ];

    /** Whether the date and lot columns are in (their migration runs on deploy). Kept ten minutes once they are. */
    public static function datesReady(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }
        if (\Illuminate\Support\Facades\Cache::get('notes:dateLot') === 1) {
            return $ready = true;
        }
        $ready = rescue(fn () => \Illuminate\Support\Facades\Schema::hasColumn('as_schedule_notes', 'noteDate')
            && \Illuminate\Support\Facades\Schema::hasColumn('as_schedule_notes', 'lotId'), false, false);
        if ($ready) {
            \Illuminate\Support\Facades\Cache::put('notes:dateLot', 1, 600);
        }

        return $ready;
    }

    /** A note made anywhere (Quick Capture, the board, the Notes module) is about today unless it says otherwise. */
    protected static function booted(): void
    {
        static::creating(function (self $note) {
            if (empty($note->noteDate) && self::datesReady()) {
                $note->noteDate = now('Asia/Manila')->toDateString();
            }
        });
    }

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }
}
