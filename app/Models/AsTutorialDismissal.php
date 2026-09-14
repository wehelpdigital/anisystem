<?php

namespace App\Models;

/** One "don't show this again", for one person, on one screen. */
class AsTutorialDismissal extends BaseModel
{
    protected $table = 'as_tutorial_dismissals';

    protected $fillable = ['userId', 'tutorialKey'];

    protected $casts = ['userId' => 'integer'];
}
