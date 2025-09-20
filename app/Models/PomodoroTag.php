<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 *
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string $color
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PomodoroSession> $sessions
 * @property-read int|null $sessions_count
 * @property-read \App\Models\Workspace $workspace
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroTag whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroTag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroTag whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroTag whereWorkspaceId($value)
 * @mixin \Eloquent
 */
class PomodoroTag extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'workspace_id',
        'name',
        'color',
    ];

    /**
     * Bu etiketin ait olduğu çalışma alanı
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Bu etikete sahip pomodoro oturumları
     */
    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(PomodoroSession::class, 'pomodoro_session_tag');
    }
}
