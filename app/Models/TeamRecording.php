<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A video the Collab Room made: a shared-camera session, or a call.
 *
 * Belongs to the schedule's team rather than to a day, because that is how
 * people look for it afterwards — "the one where Juan showed us the pump",
 * not "the fourteenth".
 */
class TeamRecording extends Model
{
    protected $table = 'as_team_recordings';

    protected $fillable = [
        'scheduleId', 'userId', 'kind', 'title', 'description',
        'path', 'poster', 'seconds', 'deleteStatus',
    ];

    protected $casts = [
        'seconds' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('deleteStatus', 1);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
