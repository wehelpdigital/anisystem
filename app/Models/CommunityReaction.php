<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A user's single reaction on a community post or reply.
 * Shared table (as_community_reactions) — the mother app may read it later.
 */
class CommunityReaction extends Model
{
    protected $table = 'as_community_reactions';

    // Stable storage keys — the mother app may read this table, so we never
    // rename them. 'love' surfaces as Heart, 'helpful' as Plant in the UI.
    public const REACTIONS = ['like', 'love', 'sad', 'helpful'];

    // Ordered display metadata (emoji + label), reused by every react bar.
    public const REACTION_META = [
        'like'    => ['emoji' => '👍', 'label' => 'Like'],
        'love'    => ['emoji' => '❤️', 'label' => 'Heart'],
        'sad'     => ['emoji' => '😢', 'label' => 'Sad'],
        'helpful' => ['emoji' => '🌱', 'label' => 'Plant'],
    ];

    protected $fillable = ['targetType', 'targetId', 'userId', 'reaction'];

    /**
     * Counts per reaction + the acting user's own choice, for a set of
     * targets: [targetId => ['counts' => [reaction => n], 'mine' => ?string]].
     */
    public static function summaryFor(string $targetType, array $targetIds, int $userId): array
    {
        if (empty($targetIds)) {
            return [];
        }

        $rows = static::where('targetType', $targetType)
            ->whereIn('targetId', $targetIds)
            ->get(['targetId', 'userId', 'reaction']);

        $out = [];
        foreach ($rows as $row) {
            $entry = &$out[$row->targetId];
            $entry['counts'][$row->reaction] = ($entry['counts'][$row->reaction] ?? 0) + 1;
            if ((int) $row->userId === $userId) {
                $entry['mine'] = $row->reaction;
            }
        }

        return $out;
    }

    /**
     * Attach a `reactionSummary` property to each model in a collection, in one
     * batched query. Used before rendering wall posts / comments / feed items.
     */
    public static function attach($items, string $targetType, int $userId): void
    {
        $items = collect($items)->filter();
        if ($items->isEmpty()) {
            return;
        }
        $map = static::summaryFor($targetType, $items->pluck('id')->all(), $userId);
        foreach ($items as $it) {
            $it->reactionSummary = $map[$it->id] ?? ['counts' => [], 'mine' => null];
        }
    }
}
