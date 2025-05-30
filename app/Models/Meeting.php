<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 
 *
 * @property int $id
 * @property int $project_id
 * @property int $created_by
 * @property string $title
 * @property string|null $description
 * @property string $meeting_type
 * @property \Illuminate\Support\Carbon $scheduled_at
 * @property int $duration
 * @property bool $is_recurring
 * @property string|null $recurrence_pattern
 * @property string $status
 * @property string|null $meeting_link
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MeetingActionItem> $actionItems
 * @property-read int|null $action_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MeetingAttendee> $attendees
 * @property-read int|null $attendees_count
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MeetingNote> $notes
 * @property-read int|null $notes_count
 * @property-read \App\Models\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting daily()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting today()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting upcoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereEndedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereIsRecurring($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereMeetingLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereMeetingType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereRecurrencePattern($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereScheduledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'created_by',
        'title',
        'description',
        'meeting_type',
        'scheduled_at',
        'duration',
        'is_recurring',
        'recurrence_pattern',
        'status',
        'meeting_link',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_recurring' => 'boolean',
    ];

    /**
     * Get the project that owns the meeting.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who created the meeting.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the attendees for the meeting.
     */
    public function attendees(): HasMany
    {
        return $this->hasMany(MeetingAttendee::class);
    }

    /**
     * Get the notes for the meeting.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(MeetingNote::class);
    }

    /**
     * Get the action items for the meeting.
     */
    public function actionItems(): HasMany
    {
        return $this->hasMany(MeetingActionItem::class);
    }

    /**
     * Get the users attending the meeting.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'meeting_attendees')
            ->withPivot('is_required', 'status')
            ->withTimestamps();
    }

    /**
     * Scope a query to only include daily meetings.
     */
    public function scopeDaily($query)
    {
        return $query->where('meeting_type', 'daily');
    }

    /**
     * Scope a query to only include upcoming meetings.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at');
    }

    /**
     * Scope a query to only include today's meetings.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', now()->toDateString())
            ->orderBy('scheduled_at');
    }

    /**
     * Generate a unique meeting room name
     */
    public function getMeetingRoomName()
    {
        return 'projecta-meeting-' . $this->id . '-' . str_replace(' ', '-', strtolower($this->title));
    }
    
    /**
     * Check if the meeting can be joined
     */
    public function canBeJoined()
    {
        return $this->status === 'scheduled' || $this->status === 'in_progress';
    }
    
    /**
     * Check if the meeting is in progress
     */
    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }
    
    /**
     * Check if the meeting is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }
}
