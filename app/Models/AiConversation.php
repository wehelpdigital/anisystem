<?php

namespace App\Models;

class AiConversation extends BaseModel
{
    protected $table = 'anisystem_ai_conversations';

    protected $fillable = ['userId', 'croppingScheduleId', 'title', 'deleteStatus'];

    protected $casts = ['deleteStatus' => 'integer'];

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
}
