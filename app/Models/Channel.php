<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 *
 *
 * @property-read \App\Models\User|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read int|null $members_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $messages
 * @property-read int|null $messages_count
 * @property-read \App\Models\Workspace|null $workspace
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel forUser($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel query()
 * @mixin \Eloquent
 */
class Channel extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'name',
        'description',
        'is_private',
        'workspace_id',
        'created_by'
    ];

    protected $casts = [
        'is_private' => 'boolean'
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'channel_members')
            ->withPivot(['is_admin', 'last_read_at'])
            ->withTimestamps();
    }

    public function admins()
    {
        return $this->members()->wherePivot('is_admin', true);
    }

    public function unreadMessagesCount($userId)
    {
        $lastRead = DB::table('channel_members')
            ->where('channel_id', $this->id)
            ->where('user_id', $userId)
            ->value('last_read_at');

        return $this->messages()
            ->when($lastRead, function ($query, $lastRead) {
                return $query->where('created_at', '>', $lastRead);
            })
            ->count();
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($query) use ($userId) {
            $query->where('is_private', false)
                ->orWhereHas('members', function ($query) use ($userId) {
                    $query->where('users.id', $userId);
                });
        });
    }
}
