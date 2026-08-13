<?php

namespace App\Models;

/** What a grower thinks of the app: one rating per person, asked once. */
class AsAppReview extends BaseModel
{
    protected $table = 'as_app_reviews';

    protected $fillable = ['userId', 'rating', 'review', 'device', 'dismissals', 'deleteStatus'];

    protected $casts = ['userId' => 'integer', 'rating' => 'integer', 'dismissals' => 'integer', 'deleteStatus' => 'integer'];

    public function author()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
