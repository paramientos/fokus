<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $used_bytes
 * @property int $limit_bytes
 * @property string $plan_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $formatted_limit
 * @property-read string $formatted_used
 * @property-read int $usage_percent
 * @property-read float $usage_percentage
 * @property-read \App\Models\Workspace $workspace
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageUsage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageUsage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageUsage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageUsage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageUsage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageUsage whereLimitBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageUsage wherePlanName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageUsage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageUsage whereUsedBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageUsage whereWorkspaceId($value)
 * @mixin \Eloquent
 */
class StorageUsage extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'workspace_id',
        'used_bytes',
        'limit_bytes',
        'plan_name',
    ];

    /**
     * Get the workspace that owns the storage usage.
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Check if the workspace has enough storage space left
     *
     * @param int $bytes
     * @return bool
     */
    public function hasEnoughSpace(int $bytes): bool
    {
        return ($this->used_bytes + $bytes) <= $this->limit_bytes;
    }

    /**
     * Add bytes to the used storage
     *
     * @param int $bytes
     * @return bool
     */
    public function addUsage(int $bytes): bool
    {
        $this->used_bytes += $bytes;
        return $this->save();
    }

    /**
     * Remove bytes from the used storage
     *
     * @param int $bytes
     * @return bool
     */
    public function removeUsage(int $bytes): bool
    {
        $this->used_bytes = max(0, $this->used_bytes - $bytes);
        return $this->save();
    }

    /**
     * Get the usage percentage
     *
     * @return float
     */
    public function getUsagePercentageAttribute(): float
    {
        if ($this->limit_bytes <= 0) {
            return 100;
        }

        return min(100, round(($this->used_bytes / $this->limit_bytes) * 100, 2));
    }

    /**
     * Get the formatted used bytes
     *
     * @return string
     */
    public function getFormattedUsedAttribute(): string
    {
        return $this->formatBytes($this->used_bytes);
    }

    /**
     * Get the formatted limit bytes
     *
     * @return string
     */
    public function getFormattedLimitAttribute(): string
    {
        return $this->formatBytes($this->limit_bytes);
    }

    /**
     * Get the storage usage percent (0-100)
     *
     * @return int
     */
    public function getUsagePercentAttribute(): int
    {
        if ($this->limit_bytes <= 0) {
            return 0;
        }
        return min(100, (int) round($this->used_bytes / $this->limit_bytes * 100));
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
