<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *
 *
 * @property int $id
 * @property int $user_id
 * @property int $achievement_id
 * @property int $level
 * @property int $progress
 * @property int $points_earned
 * @property \Illuminate\Support\Carbon $earned_at
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Achievement $achievement
 * @property-read float $progress_percentage
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAchievement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAchievement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAchievement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAchievement whereAchievementId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAchievement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAchievement whereEarnedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAchievement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAchievement whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAchievement whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAchievement wherePointsEarned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAchievement whereProgress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAchievement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAchievement whereUserId($value)
 * @mixin \Eloquent
 */
class UserAchievement extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'user_id',
        'achievement_id',
        'level',
        'progress',
        'points_earned',
        'earned_at',
        'metadata'
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'metadata' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->achievement->max_level <= $this->level) {
            return 100.0;
        }

        // Calculate progress towards next level
        $nextLevelRequirement = $this->getNextLevelRequirement();
        if ($nextLevelRequirement <= 0) {
            return 100.0;
        }

        return min(100.0, ($this->progress / $nextLevelRequirement) * 100);
    }

    public function getNextLevelRequirement(): int
    {
        // This would be based on achievement criteria
        // For now, simple exponential growth
        return pow(2, $this->level) * 10;
    }

    public function canLevelUp(): bool
    {
        return $this->level < $this->achievement->max_level &&
               $this->progress >= $this->getNextLevelRequirement();
    }

    public function levelUp(): bool
    {
        if (!$this->canLevelUp()) {
            return false;
        }

        $this->level++;
        $this->progress = 0;
        $this->points_earned += $this->achievement->points * $this->level;
        $this->save();

        return true;
    }
}
