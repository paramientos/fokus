<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $asset_category_id
 * @property string|null $assigned_to
 * @property string $created_by
 * @property string $name
 * @property string $asset_tag
 * @property string|null $description
 * @property string $status
 * @property string|null $brand
 * @property string|null $model
 * @property string|null $serial_number
 * @property numeric|null $purchase_price
 * @property \Illuminate\Support\Carbon|null $purchase_date
 * @property \Illuminate\Support\Carbon|null $warranty_expiry
 * @property string|null $location
 * @property string|null $room
 * @property string|null $desk
 * @property \Illuminate\Support\Carbon|null $last_maintenance
 * @property \Illuminate\Support\Carbon|null $next_maintenance
 * @property string|null $maintenance_notes
 * @property array<array-key, mixed>|null $custom_fields
 * @property string|null $notes
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AssetAssignment> $activeAssignment
 * @property-read int|null $active_assignment_count
 * @property-read \App\Models\User|null $assignedTo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AssetAssignment> $assignments
 * @property-read int|null $assignments_count
 * @property-read \App\Models\AssetCategory $category
 * @property-read \App\Models\User $createdBy
 * @property-read int|null $days_until_maintenance
 * @property-read int|null $days_until_warranty_expiry
 * @property-read bool $is_maintenance_due
 * @property-read bool $is_warranty_expired
 * @property-read string $status_color
 * @property-read string $status_label
 * @property-read \App\Models\Workspace $workspace
 * @method static Builder<static>|Asset newModelQuery()
 * @method static Builder<static>|Asset newQuery()
 * @method static Builder<static>|Asset query()
 * @method static Builder<static>|Asset whereAssetCategoryId($value)
 * @method static Builder<static>|Asset whereAssetTag($value)
 * @method static Builder<static>|Asset whereAssignedTo($value)
 * @method static Builder<static>|Asset whereBrand($value)
 * @method static Builder<static>|Asset whereCreatedAt($value)
 * @method static Builder<static>|Asset whereCreatedBy($value)
 * @method static Builder<static>|Asset whereCustomFields($value)
 * @method static Builder<static>|Asset whereDescription($value)
 * @method static Builder<static>|Asset whereDesk($value)
 * @method static Builder<static>|Asset whereId($value)
 * @method static Builder<static>|Asset whereIsActive($value)
 * @method static Builder<static>|Asset whereLastMaintenance($value)
 * @method static Builder<static>|Asset whereLocation($value)
 * @method static Builder<static>|Asset whereMaintenanceNotes($value)
 * @method static Builder<static>|Asset whereModel($value)
 * @method static Builder<static>|Asset whereName($value)
 * @method static Builder<static>|Asset whereNextMaintenance($value)
 * @method static Builder<static>|Asset whereNotes($value)
 * @method static Builder<static>|Asset wherePurchaseDate($value)
 * @method static Builder<static>|Asset wherePurchasePrice($value)
 * @method static Builder<static>|Asset whereRoom($value)
 * @method static Builder<static>|Asset whereSerialNumber($value)
 * @method static Builder<static>|Asset whereStatus($value)
 * @method static Builder<static>|Asset whereUpdatedAt($value)
 * @method static Builder<static>|Asset whereWarrantyExpiry($value)
 * @method static Builder<static>|Asset whereWorkspaceId($value)
 * @mixin \Eloquent
 */
class Asset extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'workspace_id',
        'asset_category_id',
        'assigned_to',
        'created_by',
        'name',
        'asset_tag',
        'description',
        'status',
        'brand',
        'model',
        'serial_number',
        'purchase_price',
        'purchase_date',
        'warranty_expiry',
        'location',
        'room',
        'desk',
        'last_maintenance',
        'next_maintenance',
        'maintenance_notes',
        'custom_fields',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'last_maintenance' => 'date',
        'next_maintenance' => 'date',
        'custom_fields' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('workspace', function (Builder $builder) {
            $builder->where('workspace_id', get_workspace_id());
        });

        static::creating(function ($asset) {
            if (!$asset->asset_tag) {
                $asset->asset_tag = static::generateAssetTag($asset->asset_category_id);
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function activeAssignment(): HasMany
    {
        return $this->hasMany(AssetAssignment::class)->where('is_active', true);
    }

    public function currentAssignment(): ?AssetAssignment
    {
        return $this->assignments()->where('is_active', true)->first();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'available' => 'success',
            'assigned' => 'info',
            'maintenance' => 'warning',
            'retired' => 'secondary',
            'lost' => 'error',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'available' => 'Available',
            'assigned' => 'Assigned',
            'maintenance' => 'Maintenance',
            'retired' => 'Retired',
            'lost' => 'Lost',
            default => 'Unknown',
        };
    }

    public function getIsWarrantyExpiredAttribute(): bool
    {
        return $this->warranty_expiry && $this->warranty_expiry->isPast();
    }

    public function getIsMaintenanceDueAttribute(): bool
    {
        return $this->next_maintenance && $this->next_maintenance->isPast();
    }

    public function getDaysUntilWarrantyExpiryAttribute(): ?int
    {
        return $this->warranty_expiry ? now()->diffInDays($this->warranty_expiry, false) : null;
    }

    public function getDaysUntilMaintenanceAttribute(): ?int
    {
        return $this->next_maintenance ? now()->diffInDays($this->next_maintenance, false) : null;
    }

    public static function generateAssetTag(int $categoryId): string
    {
        $category = AssetCategory::find($categoryId);
        $prefix = strtoupper(substr($category->name, 0, 3));
        $count = static::where('asset_category_id', $categoryId)->count() + 1;

        return $prefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    public function assignTo(User $user, User $assignedBy, ?string $notes = null): AssetAssignment
    {
        // End current assignment if exists
        $this->assignments()->where('is_active', true)->update([
            'is_active' => false,
            'returned_at' => now(),
        ]);

        // Create new assignment
        $assignment = $this->assignments()->create([
            'user_id' => $user->id,
            'assigned_by' => $assignedBy->id,
            'assigned_at' => now(),
            'assignment_notes' => $notes,
            'is_active' => true,
        ]);

        // Update asset status and assigned_to
        $this->update([
            'status' => 'assigned',
            'assigned_to' => $user->id,
        ]);

        return $assignment;
    }

    public function returnAsset(User $returnedBy, ?string $notes = null, string $condition = 'good'): void
    {
        $assignment = $this->currentAssignment();

        if ($assignment) {
            $assignment->update([
                'is_active' => false,
                'returned_at' => now(),
                'return_notes' => $notes,
                'condition_on_return' => $condition,
            ]);
        }

        $this->update([
            'status' => 'available',
            'assigned_to' => null,
        ]);
    }
}
