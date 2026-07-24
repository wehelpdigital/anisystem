<?php

namespace App\Models;

class CommunityGroupMember extends BaseModel
{
    protected $table = 'as_community_group_members';

    protected $fillable = ['groupId', 'userId', 'role', 'deleteStatus'];
}
