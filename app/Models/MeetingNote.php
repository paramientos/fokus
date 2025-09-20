<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *
 *
 * @property int $id
 * @property int $meeting_id
 * @property int $user_id
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Meeting $meeting
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereMeetingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereUserId($value)
 * @mixin \Eloquent
 */
class MeetingNote extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'meeting_id',
        'user_id',
        'content',
    ];

    /**
     * Get the meeting that owns the note.
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * Get the user that created the note.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
