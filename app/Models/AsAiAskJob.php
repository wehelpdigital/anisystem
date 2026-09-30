<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One chat question being answered (AiController::ask / askJob). The page
 * polls it until the answer lands; see the as_ai_ask_jobs migration.
 */
class AsAiAskJob extends Model
{
    protected $table = 'as_ai_ask_jobs';

    protected $fillable = ['userId', 'conversationId', 'messageId', 'status', 'payload', 'error'];

    protected $casts = [
        'userId' => 'integer',
        'conversationId' => 'integer',
        'messageId' => 'integer',
    ];
}
