<?php

namespace App\Models;

/**
 * "This does not belong here."
 *
 * A report is a message to the people who run the place, not an action on the
 * content: nothing is hidden because somebody objected. The house reads these
 * in the mother app and decides.
 *
 * `targetType` is a type rather than a table, so reporting a story or a room
 * costs a row and not a migration.
 */
class CommunityReport extends BaseModel
{
    protected $table = 'as_community_reports';

    protected $fillable = [
        'reporterUserId', 'targetType', 'targetId', 'targetUserId',
        'reason', 'details', 'snapshot',
        'status', 'note', 'reviewedByUserId', 'reviewedAt', 'deleteStatus',
    ];

    protected $casts = ['reviewedAt' => 'datetime'];

    /** What can be reported. */
    public const TYPES = ['post', 'comment', 'topic', 'reply', 'story', 'group'];

    /**
     * The reasons offered, in the order they are shown.
     *
     * Words a farmer would use, not policy language — and "Something else"
     * last, because a list that cannot say what happened sends everybody to
     * the free-text box.
     *
     * @return array<string,string>
     */
    public static function reasons(): array
    {
        return [
            'spam' => 'Spam or advertising',
            'scam' => 'Scam or fake selling',
            'false' => 'False or misleading advice',
            'harassment' => 'Bullying or harassment',
            'hate' => 'Hateful or abusive language',
            'sexual' => 'Nudity or sexual content',
            'violence' => 'Violence or something dangerous',
            'other' => 'Something else',
        ];
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporterUserId');
    }

    public function scopeOpen($q)
    {
        return $q->where('status', 'open');
    }
}
