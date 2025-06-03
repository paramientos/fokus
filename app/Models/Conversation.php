<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;
    
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
