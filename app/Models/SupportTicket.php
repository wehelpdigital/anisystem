<?php

namespace App\Models;

class SupportTicket extends BaseModel
{
    protected $table = 'as_support_tickets';

    protected $fillable = ['userId', 'subject', 'category', 'status', 'lastReplyAt', 'deleteStatus'];

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
}
