<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property string $id
 * @property string $meeting_id
 * @property string $user_id
 * @property bool $is_required
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Meeting $meeting
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingAttendee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingAttendee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingAttendee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingAttendee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingAttendee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingAttendee whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingAttendee whereMeetingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingAttendee whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingAttendee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingAttendee whereUserId($value)
 * @mixin \Eloquent
 */
class MeetingAttendee extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'meeting_id',
        'user_id',
        'is_required',
        'status',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    /**
     * Get the meeting that owns the attendee.
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * Get the user that owns the attendee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
