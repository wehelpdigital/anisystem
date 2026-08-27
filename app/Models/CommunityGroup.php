<?php

namespace App\Models;

class CommunityGroup extends BaseModel
{
    protected $table = 'as_community_groups';

    public const PUBLIC = 'public';

    public const PRIVATE = 'private';

    /** A shared secret the organiser hands out. */
    public const BY_PASSWORD = 'password';

    /** The organiser lets people in one at a time. */
    public const BY_APPROVAL = 'approval';

    protected $fillable = [
        'name', 'slug', 'description', 'privacy', 'joinMode', 'joinPassword',
        'coverImagePath', 'bannerImagePath', 'createdByUserId', 'deleteStatus',
    ];

    protected $casts = [
        // Encrypted rather than hashed, because the organiser has to be able
        // to read it back to tell the next person (see the migration).
        'joinPassword' => 'encrypted',
    ];

    public function members()
    {
        return $this->hasMany(CommunityGroupMember::class, 'groupId')->where('as_community_group_members.deleteStatus', 1);
    }

    /**
     * Every reply under every topic in this discussion.
     *
     * hasManyThrough, so "how busy is this room" is one count query rather
     * than a walk over its posts — the list asks this for every card it draws.
     */
    public function replies()
    {
        return $this->hasManyThrough(
            CommunityGroupReply::class,
            CommunityGroupPost::class,
            'groupId',
            'postId',
        )->where('as_community_group_replies.deleteStatus', 1)
            ->where('as_community_group_posts.deleteStatus', 1);
    }

    public function posts()
    {
        return $this->hasMany(CommunityGroupPost::class, 'groupId')->where('as_community_group_posts.deleteStatus', 1);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'createdByUserId');
    }

    /* ------------------------------------------------------------------
     * The door.
     *
     * Every question about who may do what in a room is answered here, so
     * the controller, the views and the list query cannot drift apart on
     * it. A super admin is treated as being in every room: they answer for
     * the whole community and cannot be shut out of part of it.
     * ---------------------------------------------------------------- */

    public function isPrivate(): bool
    {
        return $this->privacy === self::PRIVATE;
    }

    public function asksForPassword(): bool
    {
        return $this->isPrivate() && $this->joinMode === self::BY_PASSWORD;
    }

    public function asksForApproval(): bool
    {
        return $this->isPrivate() && $this->joinMode === self::BY_APPROVAL;
    }

    /** The one who started it. */
    public function isCreator(?User $user): bool
    {
        return $user !== null && (int) $this->createdByUserId === (int) $user->id;
    }

    /**
     * May they see inside — the topics, the chat, who else is here?
     *
     * A public room is open to everyone signed in, as it always has been.
     * A private one is open to its members, its creator, and admins.
     */
    public function mayEnter(?User $user, bool $isMember): bool
    {
        if (! $this->isPrivate()) {
            return true;
        }

        return $isMember || $this->isCreator($user) || (bool) $user?->isSuperAdmin();
    }

    /**
     * May they keep order — let people in, put people out?
     *
     * The creator, their moderators, and admins. A moderator is a deputy,
     * so this is deliberately wider than mayGovern().
     */
    public function mayModerate(?User $user, ?string $role = null): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->isCreator($user)
            || $user->isSuperAdmin()
            || $role === CommunityGroupMember::MODERATOR
            || $role === CommunityGroupMember::OWNER;
    }

    /**
     * May they decide who holds the keys, and how the door works?
     *
     * The creator and admins only. A moderator can put a member out but
     * cannot appoint another moderator — a deputy does not hand out
     * deputies, or the room's ownership drifts away from the person who
     * started it.
     */
    public function mayGovern(?User $user): bool
    {
        return $user !== null && ($this->isCreator($user) || $user->isSuperAdmin());
    }
}
