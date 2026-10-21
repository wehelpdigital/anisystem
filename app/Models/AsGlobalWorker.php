<?php

namespace App\Models;

/**
 * A farm's own worker, above the seasons (2026-10-12): added to any season
 * as a card of that season (AsScheduleWorker, globalWorkerId), where the
 * activities, pay and login live. See App\Http\Controllers\GlobalWorkerController.
 */
class AsGlobalWorker extends BaseModel
{
    protected $table = 'as_global_workers';

    protected $fillable = ['userId', 'workerName', 'email', 'phone', 'costPerHalfDay', 'skills', 'offWeekdays', 'notes', 'personKey', 'deleteStatus'];

    protected $casts = [
        'costPerHalfDay' => 'decimal:2',
        'skills' => 'array',
        'offWeekdays' => 'array',
        'deleteStatus' => 'integer',
    ];

    /** Who this is, for finding the same person again: the email, else the name and phone digits. */
    public static function personKey(string $name, ?string $email, ?string $phone): string
    {
        $email = mb_strtolower(trim((string) $email));
        if ($email !== '') {
            return 'e:' . $email;
        }

        return 'n:' . mb_strtolower(trim(preg_replace('/\s+/', ' ', $name))) . '|' . preg_replace('/\D+/', '', (string) $phone);
    }

    /** Whether a season already has a card that is this person (linked or not). */
    public function inSeason(int $scheduleId): bool
    {
        return AsScheduleWorker::active()->where('croppingScheduleId', $scheduleId)->get(['id', 'workerName', 'email', 'phone', 'globalWorkerId'])
            ->contains(fn ($c) => (int) $c->globalWorkerId === (int) $this->id || self::personKey((string) $c->workerName, $c->email, $c->phone) === $this->personKey);
    }

    public function cards()
    {
        return $this->hasMany(AsScheduleWorker::class, 'globalWorkerId')->where('deleteStatus', 1);
    }

    /**
     * The global worker for a season's card: found by who they are, or made.
     * Called when a card is added or edited, so the farm's roster fills itself.
     */
    public static function forCard(AsScheduleWorker $card, int $ownerId): self
    {
        $key = self::personKey((string) $card->workerName, $card->email, $card->phone);
        $g = $card->globalWorkerId ? self::where('id', $card->globalWorkerId)->where('userId', $ownerId)->first() : null;
        if ($g && (int) $g->deleteStatus !== 1) {
            return $g;   // taken off the roster by the owner: their old cards do not put them back
        }
        $g ??= self::where('userId', $ownerId)->where('personKey', $key)->where('deleteStatus', 1)->first();
        if (! $g) {
            $g = self::create(['userId' => $ownerId, 'workerName' => $card->workerName, 'email' => $card->email, 'phone' => $card->phone,
                'costPerHalfDay' => $card->costPerHalfDay ?? 0, 'skills' => $card->skills, 'notes' => $card->notes, 'personKey' => $key, 'deleteStatus' => 1]);
        } else {
            // The card was just written by hand: who they are follows it.
            $g->update(['workerName' => $card->workerName, 'email' => $card->email, 'phone' => $card->phone, 'skills' => $card->skills, 'personKey' => $key]);
            // And so do their cards on the other open seasons (2026-10-22, the owner: "all workers in
            // the cropping schedule are synced to the main worker module"). Pay stays each season's own.
            $g->spread((int) $card->id);
        }
        if ((int) $card->globalWorkerId !== (int) $g->id) {
            $card->forceFill(['globalWorkerId' => $g->id])->save();
        }

        return $g;
    }

    /**
     * Who they are, onto every card of theirs on an open season (a closed
     * season is a record and keeps what it said): the name, contact and
     * skills. Pay and days off stay each season's own business, written there.
     */
    public function spread(int $exceptCardId = 0): void
    {
        $open = AsCroppingSchedule::active()->where('anisystemUserId', (int) $this->userId)
            ->whereNotIn('status', [AsCroppingSchedule::STATUS_COMPLETED, AsCroppingSchedule::STATUS_ARCHIVED])->pluck('id');
        AsScheduleWorker::active()->where('globalWorkerId', (int) $this->id)->whereIn('croppingScheduleId', $open)
            ->when($exceptCardId > 0, fn ($q) => $q->where('id', '!=', $exceptCardId))
            ->update(['workerName' => $this->workerName, 'email' => $this->email, 'phone' => $this->phone,
                'skills' => $this->skills ? json_encode(array_values((array) $this->skills)) : null, 'updated_at' => now()]);
    }
}
