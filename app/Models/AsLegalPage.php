<?php

namespace App\Models;

class AsLegalPage extends BaseModel
{
    protected $table = 'as_legal_pages';

    protected $fillable = ['slug', 'title', 'body', 'sortOrder', 'isPublished', 'deleteStatus'];

    protected $casts = [
        'isPublished' => 'boolean',
        'sortOrder' => 'integer',
    ];

    public function scopePublished($q)
    {
        return $q->where('isPublished', 1);
    }
}
