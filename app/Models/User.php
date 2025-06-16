<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 *
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $date_of_birth
 * @property string|null $gender
 * @property string|null $nationality
 * @property string|null $national_id
 * @property string|null $passport_number
 * @property string|null $tax_id
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string|null $country
 * @property string|null $emergency_contact_relationship
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property int|null $current_workspace_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Achievement> $achievements
 * @property-read int|null $achievements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $assignedTasks
 * @property-read int|null $assigned_tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation> $conversations
 * @property-read int|null $conversations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation> $createdConversations
 * @property-read int|null $created_conversations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Meeting> $createdMeetings
 * @property-read int|null $created_meetings_count
 * @property-read int $current_streak
 * @property-read int $level
 * @property-read float $level_progress
 * @property-read int $points_to_next_level
 * @property-read int $total_points
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Leaderboard> $leaderboards
 * @property-read int|null $leaderboards_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Meeting> $meetings
 * @property-read int|null $meetings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $messages
 * @property-read int|null $messages_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects
 * @property-read int|null $projects_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $reportedTasks
 * @property-read int|null $reported_tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserAchievement> $userAchievements
 * @property-read int|null $user_achievements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Workspace> $workspaceMembers
 * @property-read int|null $workspace_members_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User query()
 * @method static Builder<static>|User whereAddress($value)
 * @method static Builder<static>|User whereCity($value)
 * @method static Builder<static>|User whereCountry($value)
 * @method static Builder<static>|User whereCreatedAt($value)
 * @method static Builder<static>|User whereCurrentWorkspaceId($value)
 * @method static Builder<static>|User whereDateOfBirth($value)
 * @method static Builder<static>|User whereEmail($value)
 * @method static Builder<static>|User whereEmailVerifiedAt($value)
 * @method static Builder<static>|User whereEmergencyContactRelationship($value)
 * @method static Builder<static>|User whereGender($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User whereName($value)
 * @method static Builder<static>|User whereNationalId($value)
 * @method static Builder<static>|User whereNationality($value)
 * @method static Builder<static>|User wherePassportNumber($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User wherePhone($value)
 * @method static Builder<static>|User wherePostalCode($value)
 * @method static Builder<static>|User whereRememberToken($value)
 * @method static Builder<static>|User whereState($value)
 * @method static Builder<static>|User whereTaxId($value)
 * @method static Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'current_workspace_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the tasks assigned to the user.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    /**
     * Get the tasks reported by the user.
     */
    public function reportedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'reporter_id');
    }

    /**
     * Kullanıcıya atanmış görevler
     */
    public function assignedTasks()
    {
        return $this->hasMany(\App\Models\Task::class, 'user_id');
    }

    /**
     * Kullanıcının katıldığı toplantılar
     */
    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class, 'meeting_attendees')
            ->withPivot(['status'])
            ->withTimestamps();
    }

    /**
     * Kullanıcının oluşturduğu toplantılar
     */
    public function createdMeetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'creator_id');
    }

    /**
     * Kullanıcının üye olduğu projeler
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Kullanıcının katıldığı konuşmalar
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['is_admin', 'last_read_at', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    /**
     * Kullanıcının oluşturduğu konuşmalar
     */
    public function createdConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'created_by');
    }

    /**
     * Kullanıcının gönderdiği mesajlar
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Kullanıcının okunmamış mesajlarını getir
     */
    public function unreadMessages()
    {
        return $this->messages()->whereNull('read_at');
    }

    /**
     * Kullanıcının aktif olduğu konuşmaları getir
     */
    public function activeConversations()
    {
        return $this->conversations()
            ->whereHas('participants', function (Builder $query) {
                $query->where('user_id', $this->id)
                    ->whereNull('left_at');
            });
    }

    /**
     * Kullanıcının üye olduğu iş alanları
     */
    public function workspaceMembers(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_members')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Kullanıcının kazandığı başarılar
     */
    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    /**
     * Kullanıcının başarıları (many-to-many)
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot(['level', 'progress', 'points_earned', 'earned_at', 'metadata'])
            ->withTimestamps();
    }

    /**
     * Kullanıcının liderlik tablosu kayıtları
     */
    public function leaderboards(): HasMany
    {
        return $this->hasMany(Leaderboard::class);
    }

    /**
     * Kullanıcının toplam puanını getir
     */
    public function getTotalPointsAttribute(): int
    {
        return $this->userAchievements()->sum('points_earned');
    }

    /**
     * Kullanıcının aktif streak'ini getir
     */
    public function getCurrentStreakAttribute(): int
    {
        // Bu basit bir implementasyon, gerçekte daha karmaşık olabilir
        $lastActivity = $this->tasks()
            ->whereNotNull('completed_at')
            ->orderByDesc('updated_at')
            ->first();

        if (!$lastActivity) {
            return 0;
        }

        // Son aktiviteden bu yana geçen gün sayısını hesapla
        $daysSinceLastActivity = now()->diffInDays($lastActivity->updated_at);

        if ($daysSinceLastActivity > 1) {
            return 0;
        }

        // Basit streak hesaplama - gerçekte daha detaylı olmalı
        return $this->tasks()
            ->whereNotNull('completed_at')
            ->where('updated_at', '>=', now()->subDays(30))
            ->groupBy(\DB::raw('DATE(updated_at)'))
            ->get()
            ->count();
    }

    /**
     * Kullanıcının seviyesini getir
     */
    public function getLevelAttribute(): int
    {
        $totalPoints = $this->total_points;

        // Basit seviye hesaplama: her 100 puan = 1 seviye
        return intval($totalPoints / 100) + 1;
    }

    /**
     * Kullanıcının bir sonraki seviye için gereken puanı getir
     */
    public function getPointsToNextLevelAttribute(): int
    {
        $currentLevel = $this->level;
        $nextLevelRequirement = $currentLevel * 100;
        $currentPoints = $this->total_points;

        return max(0, $nextLevelRequirement - $currentPoints);
    }

    /**
     * Kullanıcının seviye ilerlemesini yüzde olarak getir
     */
    public function getLevelProgressAttribute(): float
    {
        $currentLevel = $this->level;
        $currentLevelStart = ($currentLevel - 1) * 100;
        $nextLevelStart = $currentLevel * 100;
        $currentPoints = $this->total_points;

        if ($currentPoints >= $nextLevelStart) {
            return 100.0;
        }

        $progressInLevel = $currentPoints - $currentLevelStart;
        $levelRange = $nextLevelStart - $currentLevelStart;

        return ($progressInLevel / $levelRange) * 100;
    }
}
