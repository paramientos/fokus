<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $user_id
 * @property string $role
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMember newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMember newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMember query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMember whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMember whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMember whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMember whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMember whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMember whereWorkspaceId($value)
 * @mixin \Eloquent
 */
class WorkspaceMember extends Model
{
    use HasUuids;

    protected $guarded = [];
}
