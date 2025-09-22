<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string|null $title
 * @property string|null $description
 * @property string $project_id
 * @property string $created_by
 * @property string $type
 * @property string|null $context_id
 * @property string|null $context_type
 * @property bool $is_private
 * @property \Illuminate\Support\Carbon|null $last_message_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Model|\Eloquent|null $context
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\Message|null $lastMessage
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $messages
 * @property-read int|null $messages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ConversationParticipant> $participants
 * @property-read int|null $participants_count
 * @property-read \App\Models\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation forContext($contextType, $contextId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation forProject($projectId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation ofType($type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereContextId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereContextType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereIsPrivate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereLastMessageAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Conversation extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'project_id',
        'created_by',
        'type',
        'context_id',
        'context_type',
        'is_private',
        'last_message_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_private' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the project that owns the conversation.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who created the conversation.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the messages for the conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the participants for the conversation.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /**
     * Get the users participating in the conversation.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['is_admin', 'last_read_at', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    /**
     * Get the context model (Task, Sprint, etc.)
     */
    public function context(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the last message in the conversation.
     */
    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    /**
     * Scope a query to only include conversations for a specific project.
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope a query to only include conversations for a specific context.
     */
    public function scopeForContext($query, $contextType, $contextId)
    {
        return $query->where('context_type', $contextType)
            ->where('context_id', $contextId);
    }

    /**
     * Scope a query to only include conversations for a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}
