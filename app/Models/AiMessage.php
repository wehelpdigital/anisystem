<?php

namespace App\Models;

class AiMessage extends BaseModel
{
    protected $table = 'anisystem_ai_messages';

    protected $fillable = [
        'conversationId', 'role', 'content', 'imagePath',
        'tokensIn', 'tokensOut', 'creditsCharged', 'isRefusal', 'deleteStatus',
    ];

    protected $casts = [
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
