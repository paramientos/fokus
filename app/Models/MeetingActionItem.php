<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property int $meeting_id
 * @property int|null $assigned_to
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $assignee
 * @property-read \App\Models\Meeting $meeting
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingActionItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingActionItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingActionItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingActionItem whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingActionItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingActionItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingActionItem whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingActionItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingActionItem whereMeetingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingActionItem whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingActionItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MeetingActionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'assigned_to',
        'description',
        'due_date',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    /**
     * Get the meeting that owns the action item.
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * Get the user that the action item is assigned to.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
