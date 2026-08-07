<?php

namespace App\Models;

class AiConversation extends BaseModel
{
    protected $table = 'anisystem_ai_conversations';

    protected $fillable = ['userId', 'croppingScheduleId', 'linkedDate', 'linkedActivityId', 'title', 'deleteStatus'];

    protected $casts = ['deleteStatus' => 'integer', 'linkedDate' => 'date'];

    public function messages()
    {
        return $this->hasMany(AiMessage::class, 'conversationId')
            ->where('anisystem_ai_messages.deleteStatus', 1)
            ->orderBy('id');
    }

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function linkedActivity()
    {
        return $this->belongsTo(AsScheduleActivity::class, 'linkedActivityId');
    }

    /** A short human chip for the day/activity this thread is pinned to. */
    public function getLinkLabelAttribute(): ?string
    {
        if ($this->linkedActivityId) {
            $title = $this->linkedActivity?->activityTitle;

            return '🌱 ' . \Illuminate\Support\Str::limit($title ?: 'Activity', 36, '');
        }
        if ($this->linkedDate) {
            return '📅 ' . $this->linkedDate->format('M j, Y');
        }

        return null;
    }
}
