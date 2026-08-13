<?php

namespace App\Models;

class SupportTicket extends BaseModel
{
    protected $table = 'as_support_tickets';

    protected $fillable = ['ticketNumber', 'userId', 'subject', 'category', 'status', 'lastReplyAt', 'deleteStatus'];

    protected $casts = ['lastReplyAt' => 'datetime'];

    public const CATEGORIES = [
        'general' => 'General question',
        'billing' => 'Billing / subscription',
        'technical' => 'Technical issue',
        'schedule' => 'Cropping schedule',
        'community' => 'Community',
    ];

    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'ticketId')->where('as_support_messages.deleteStatus', 1);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    /**
     * The next number, in the form a person can read down a phone: the year
     * and a running count within it. Not the row id — that leaks how many
     * tickets everyone else has raised.
     */
    public static function nextNumber(): string
    {
        $year = now()->format('Y');
        $n = static::where('ticketNumber', 'like', "AS-$year-%")->count() + 1;

        return sprintf('AS-%s-%04d', $year, $n);
    }
}
