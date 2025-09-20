<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 
 *
 * @property string $id
 * @property string $user_id
 * @property string|null $workspace_id
 * @property string|null $title
 * @property string|null $description
 * @property int $work_duration
 * @property int $break_duration
 * @property int $long_break_duration
 * @property int $long_break_interval
 * @property int $completed_pomodoros
 * @property int $target_pomodoros
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PomodoroLog> $logs
 * @property-read int|null $logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PomodoroTag> $tags
 * @property-read int|null $tags_count
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Workspace|null $workspace
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession whereBreakDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession whereCompletedPomodoros($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession whereLongBreakDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession whereLongBreakInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession whereTargetPomodoros($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession whereWorkDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSession whereWorkspaceId($value)
 * @mixin \Eloquent
 */
class PomodoroSession extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'user_id',
        'workspace_id',
        'title',
        'description',
        'work_duration',
        'break_duration',
        'long_break_duration',
        'long_break_interval',
        'completed_pomodoros',
        'target_pomodoros',
        'started_at',
        'completed_at',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Pomodoro oturumunun sahibi olan kullanıcı
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pomodoro oturumunun ait olduğu çalışma alanı
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Pomodoro oturumuna ait kayıtlar
     */
    public function logs(): HasMany
    {
        return $this->hasMany(PomodoroLog::class);
    }

    /**
     * Pomodoro oturumuna ait etiketler
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(PomodoroTag::class, 'pomodoro_session_tag');
    }

    /**
     * Aktif bir pomodoro oturumu mu?
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['work', 'break', 'long_break']);
    }

    /**
     * Tamamlanmış bir pomodoro oturumu mu?
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Duraklatılmış bir pomodoro oturumu mu?
     */
    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    /**
     * Henüz başlamamış bir pomodoro oturumu mu?
     */
    public function isNotStarted(): bool
    {
        return $this->status === 'not_started';
    }

    /**
     * Çalışma modunda mı?
     */
    public function isWorkMode(): bool
    {
        return $this->status === 'work';
    }

    /**
     * Mola modunda mı?
     */
    public function isBreakMode(): bool
    {
        return $this->status === 'break' || $this->status === 'long_break';
    }

    /**
     * Uzun mola modunda mı?
     */
    public function isLongBreakMode(): bool
    {
        return $this->status === 'long_break';
    }

    /**
     * İlerleme yüzdesi
     */
    public function progressPercentage(): int
    {
        if ($this->target_pomodoros === 0) {
            return 0;
        }

        return min(100, round(($this->completed_pomodoros / $this->target_pomodoros) * 100));
    }
}
