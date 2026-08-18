<?php

namespace App\Models;

class AiMessage extends BaseModel
{
    protected $table = 'anisystem_ai_messages';

    protected $fillable = [
        'conversationId', 'role', 'content', 'imagePath', 'imagePaths',
        'tokensIn', 'tokensOut', 'creditsCharged', 'isRefusal', 'deleteStatus',
    ];

    protected $casts = [
        // Every photo on the turn; the legacy imagePath keeps the first one.
        'imagePaths' => 'array',
        'tokensIn' => 'integer',
        'tokensOut' => 'integer',
        'creditsCharged' => 'decimal:2',
        'isRefusal' => 'boolean',
        'deleteStatus' => 'integer',
    ];

    public function conversation()
    {
        return $this->belongsTo(AiConversation::class, 'conversationId');
    }
}
