<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $conversation_id
 * @property string $user_id
 * @property bool $is_admin
 * @property \Illuminate\Support\Carbon|null $last_read_at
 * @property \Illuminate\Support\Carbon $joined_at
 * @property \Illuminate\Support\Carbon|null $left_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Conversation $conversation
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConversationParticipant active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConversationParticipant admins()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConversationParticipant forConversation($conversationId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConversationParticipant forUser($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConversationParticipant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConversationParticipant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConversationParticipant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConversationParticipant whereConversationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConversationParticipant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConversationParticipant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConversationParticipant whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConversationParticipant whereJoinedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConversationParticipant whereLastReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConversationParticipant whereLeftAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConversationParticipant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConversationParticipant whereUserId($value)
 *
 * @mixin \Eloquent
 */
class ConversationParticipant extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'conversation_id',
        'user_id',
        'is_admin',
        'last_read_at',
        'joined_at',
        'left_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_admin' => 'boolean',
        'last_read_at' => 'datetime',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    /**
     * Get the conversation that the participant belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user that is participating in the conversation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active participants.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('left_at');
    }

    /**
     * Scope a query to only include admin participants.
     */
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    /**
     * Scope a query to only include participants for a specific conversation.
     */
    public function scopeForConversation($query, $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    /**
     * Scope a query to only include participants for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
