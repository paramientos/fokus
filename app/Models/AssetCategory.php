<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $color
 * @property string $icon
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Asset> $activeAssets
 * @property-read int|null $active_assets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Asset> $assets
 * @property-read int $assets_count
 * @property-read int $assigned_assets_count
 * @property-read int $available_assets_count
 * @property-read \App\Models\Workspace $workspace
 * @method static Builder<static>|AssetCategory newModelQuery()
 * @method static Builder<static>|AssetCategory newQuery()
 * @method static Builder<static>|AssetCategory query()
 * @method static Builder<static>|AssetCategory whereColor($value)
 * @method static Builder<static>|AssetCategory whereCreatedAt($value)
 * @method static Builder<static>|AssetCategory whereDescription($value)
 * @method static Builder<static>|AssetCategory whereIcon($value)
 * @method static Builder<static>|AssetCategory whereId($value)
 * @method static Builder<static>|AssetCategory whereIsActive($value)
 * @method static Builder<static>|AssetCategory whereName($value)
 * @method static Builder<static>|AssetCategory whereSlug($value)
 * @method static Builder<static>|AssetCategory whereUpdatedAt($value)
 * @method static Builder<static>|AssetCategory whereWorkspaceId($value)
 * @mixin \Eloquent
 */
class AssetCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('workspace', function (Builder $builder) {
            $builder->where('workspace_id', get_workspace_id());
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function activeAssets(): HasMany
    {
        return $this->hasMany(Asset::class)->where('is_active', true);
    }

    public function getAssetsCountAttribute(): int
    {
        return $this->assets()->count();
    }

    public function getAvailableAssetsCountAttribute(): int
    {
        return $this->assets()->where('status', 'available')->count();
    }

    public function getAssignedAssetsCountAttribute(): int
    {
        return $this->assets()->where('status', 'assigned')->count();
    }
}
