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
 * @property string $pomodoro_session_id
 * @property string $type
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property bool $completed
 * @property int $duration
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PomodoroSession $session
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroLog whereCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroLog whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroLog whereEndedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroLog whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroLog wherePomodoroSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroLog whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroLog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PomodoroLog extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'pomodoro_session_id',
        'type',
        'started_at',
        'ended_at',
        'completed',
        'duration',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'completed' => 'boolean',
    ];

    /**
     * Bu kaydın ait olduğu pomodoro oturumu
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PomodoroSession::class, 'pomodoro_session_id');
    }

    /**
     * Çalışma kaydı mı?
     */
    public function isWork(): bool
    {
        return $this->type === 'work';
    }

    /**
     * Mola kaydı mı?
     */
    public function isBreak(): bool
    {
        return $this->type === 'break';
    }

    /**
     * Uzun mola kaydı mı?
     */
    public function isLongBreak(): bool
    {
        return $this->type === 'long_break';
    }

    /**
     * Süre formatı (dakika:saniye)
     */
    public function formattedDuration(): string
    {
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
