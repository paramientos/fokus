<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $asset_id
 * @property string $user_id
 * @property string $assigned_by
 * @property \Illuminate\Support\Carbon $assigned_at
 * @property \Illuminate\Support\Carbon|null $returned_at
 * @property string|null $assignment_notes
 * @property string|null $return_notes
 * @property string $condition_on_assignment
 * @property string|null $condition_on_return
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Asset $asset
 * @property-read \App\Models\User $assignedBy
 * @property-read string $condition_color
 * @property-read string|null $duration
 * @property-read \App\Models\User $user
 *
 * @method static Builder<static>|AssetAssignment newModelQuery()
 * @method static Builder<static>|AssetAssignment newQuery()
 * @method static Builder<static>|AssetAssignment query()
 * @method static Builder<static>|AssetAssignment whereAssetId($value)
 * @method static Builder<static>|AssetAssignment whereAssignedAt($value)
 * @method static Builder<static>|AssetAssignment whereAssignedBy($value)
 * @method static Builder<static>|AssetAssignment whereAssignmentNotes($value)
 * @method static Builder<static>|AssetAssignment whereConditionOnAssignment($value)
 * @method static Builder<static>|AssetAssignment whereConditionOnReturn($value)
 * @method static Builder<static>|AssetAssignment whereCreatedAt($value)
 * @method static Builder<static>|AssetAssignment whereId($value)
 * @method static Builder<static>|AssetAssignment whereIsActive($value)
 * @method static Builder<static>|AssetAssignment whereReturnNotes($value)
 * @method static Builder<static>|AssetAssignment whereReturnedAt($value)
 * @method static Builder<static>|AssetAssignment whereUpdatedAt($value)
 * @method static Builder<static>|AssetAssignment whereUserId($value)
 *
 * @mixin \Eloquent
 */
class AssetAssignment extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'asset_id',
        'user_id',
        'assigned_by',
        'assigned_at',
        'returned_at',
        'assignment_notes',
        'return_notes',
        'condition_on_assignment',
        'condition_on_return',
        'is_active',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'returned_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('workspace', function (Builder $builder) {
            $builder->whereHas('asset', function (Builder|Asset $query) {
                $query->where('workspace_id', get_workspace_id());
            });
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function getConditionColorAttribute(): string
    {
        $condition = $this->condition_on_return ?? $this->condition_on_assignment;

        return match ($condition) {
            'excellent' => 'success',
            'good' => 'info',
            'fair' => 'warning',
            'poor' => 'error',
            default => 'secondary',
        };
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->assigned_at) {
            return null;
        }

        $endDate = $this->returned_at ?? now();
        $duration = $this->assigned_at->diffForHumans($endDate, true);

        return $duration;
    }
}
