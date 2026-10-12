<?php

namespace App\Models;

/** A line of an Anee Auto season's story: what Anee said, what the farmer did. The post harvest reports read these. */
class AsAutoEvent extends BaseModel
{
    protected $table = 'as_auto_events';

    protected $fillable = ['autoId', 'lotId', 'taskId', 'userId', 'role', 'kind', 'body', 'data', 'media'];

    protected $casts = ['data' => 'array', 'media' => 'array'];

    public static function say(int $autoId, string $kind, ?string $body, array $data = [], array $extra = []): self
    {
        return self::create(['autoId' => $autoId, 'role' => $extra['role'] ?? 'anee', 'kind' => $kind, 'body' => $body, 'data' => $data ?: null,
            'lotId' => $extra['lotId'] ?? null, 'taskId' => $extra['taskId'] ?? null, 'userId' => $extra['userId'] ?? null, 'media' => $extra['media'] ?? null]);
    }
}
