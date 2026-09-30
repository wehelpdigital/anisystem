<?php

namespace App\Models;

/**
 * One visitor's free question to Anee, from the words they typed to the
 * article that answers it (App\Http\Controllers\AskAneeController).
 *
 * status: asked -> declined (not about farming) | details (Anee wants the
 * farm) -> ready (the farm is in, waiting for an email) -> emailed. The
 * article is written only when the emailed link is opened (answerStatus
 * working -> ready | failed), unless an existing article already answers
 * it (matchedPageId), which the link then opens.
 */
class AsAskQuestion extends BaseModel
{
    protected $table = 'as_ask_questions';

    protected $fillable = [
        'token', 'question', 'topic', 'lang', 'isAgri', 'reply', 'detectedCrop', 'matchedPageId',
        'farmSize', 'farmUnit', 'crop', 'cropLabel', 'country', 'province', 'town', 'email', 'status',
        'answerStatus', 'answerPhase', 'answerTry', 'answerStartedAt', 'answerBeatAt', 'answerError',
        'pageId', 'crmLeadId', 'listedAt', 'emailedAt', 'openedAt', 'ip', 'userAgent', 'source', 'deleteStatus',
    ];

    protected $casts = [
        'isAgri' => 'boolean',
        'farmSize' => 'float',
        'answerStartedAt' => 'datetime',
        'answerBeatAt' => 'datetime',
        'listedAt' => 'datetime',
        'emailedAt' => 'datetime',
        'openedAt' => 'datetime',
        'deleteStatus' => 'integer',
    ];

    /** "2 hectares", "500 square meters". */
    public function farmWords(): string
    {
        if (! $this->farmSize) {
            return '';
        }
        $n = rtrim(rtrim(number_format((float) $this->farmSize, 2, '.', ''), '0'), '.');
        $unit = $this->farmUnit === 'sqm' ? 'square meters' : ((float) $this->farmSize == 1.0 ? 'hectare' : 'hectares');

        return $n . ' ' . $unit;
    }

    /** "San Jose, Nueva Ecija". */
    public function placeWords(): string
    {
        return trim(implode(', ', array_filter([trim((string) $this->town), trim((string) $this->province)])), ', ');
    }
}
