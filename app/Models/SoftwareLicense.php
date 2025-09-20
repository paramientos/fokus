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
 * @property int $id
 * @property int $workspace_id
 * @property int $created_by
 * @property string $name
 * @property string $vendor
 * @property string|null $version
 * @property string $license_type
 * @property string|null $license_key
 * @property \Illuminate\Support\Carbon|null $purchase_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property numeric|null $cost
 * @property string|null $billing_cycle
 * @property int $total_licenses
 * @property int $used_licenses
 * @property string|null $description
 * @property string|null $notes
 * @property string $status
 * @property bool $auto_renewal
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LicenseAssignment> $activeAssignments
 * @property-read int|null $active_assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LicenseAssignment> $assignments
 * @property-read int|null $assignments_count
 * @property-read \App\Models\User $createdBy
 * @property-read int $available_licenses
 * @property-read int|null $days_until_expiry
 * @property-read bool $is_expired
 * @property-read bool $is_expiring_soon
 * @property-read string $status_color
 * @property-read string $status_label
 * @property-read float $usage_percentage
 * @property-read \App\Models\Workspace $workspace
 * @method static Builder<static>|SoftwareLicense newModelQuery()
 * @method static Builder<static>|SoftwareLicense newQuery()
 * @method static Builder<static>|SoftwareLicense query()
 * @method static Builder<static>|SoftwareLicense whereAutoRenewal($value)
 * @method static Builder<static>|SoftwareLicense whereBillingCycle($value)
 * @method static Builder<static>|SoftwareLicense whereCost($value)
 * @method static Builder<static>|SoftwareLicense whereCreatedAt($value)
 * @method static Builder<static>|SoftwareLicense whereCreatedBy($value)
 * @method static Builder<static>|SoftwareLicense whereDescription($value)
 * @method static Builder<static>|SoftwareLicense whereExpiryDate($value)
 * @method static Builder<static>|SoftwareLicense whereId($value)
 * @method static Builder<static>|SoftwareLicense whereIsActive($value)
 * @method static Builder<static>|SoftwareLicense whereLicenseKey($value)
 * @method static Builder<static>|SoftwareLicense whereLicenseType($value)
 * @method static Builder<static>|SoftwareLicense whereName($value)
 * @method static Builder<static>|SoftwareLicense whereNotes($value)
 * @method static Builder<static>|SoftwareLicense wherePurchaseDate($value)
 * @method static Builder<static>|SoftwareLicense whereStatus($value)
 * @method static Builder<static>|SoftwareLicense whereTotalLicenses($value)
 * @method static Builder<static>|SoftwareLicense whereUpdatedAt($value)
 * @method static Builder<static>|SoftwareLicense whereUsedLicenses($value)
 * @method static Builder<static>|SoftwareLicense whereVendor($value)
 * @method static Builder<static>|SoftwareLicense whereVersion($value)
 * @method static Builder<static>|SoftwareLicense whereWorkspaceId($value)
 * @mixin \Eloquent
 */
class SoftwareLicense extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'workspace_id',
        'created_by',
        'name',
        'vendor',
        'version',
        'license_type',
        'license_key',
        'purchase_date',
        'expiry_date',
        'cost',
        'billing_cycle',
        'total_licenses',
        'used_licenses',
        'description',
        'notes',
        'status',
        'auto_renewal',
        'is_active',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'expiry_date' => 'date',
        'cost' => 'decimal:2',
        'total_licenses' => 'integer',
        'used_licenses' => 'integer',
        'auto_renewal' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('workspace', function (Builder $builder) {
            $builder->where('workspace_id', get_workspace_id());
        });

        static::saving(function ($license) {
            $license->used_licenses = $license->assignments()->where('is_active', true)->count();
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LicenseAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->hasMany(LicenseAssignment::class)->where('is_active', true);
    }

    public function getAvailableLicensesAttribute(): int
    {
        return $this->total_licenses - $this->used_licenses;
    }

    public function getUsagePercentageAttribute(): float
    {
        if ($this->total_licenses === 0) {
            return 0;
        }

        return ($this->used_licenses / $this->total_licenses) * 100;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'trial' => 'info',
            'expired' => 'error',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'trial' => 'Trial',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isBefore(now()->addDays(30));
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        return $this->expiry_date ? now()->diffInDays($this->expiry_date, false) : null;
    }

    public function assignToUser(User $user, User $assignedBy, ?string $notes = null): ?LicenseAssignment
    {
        if ($this->available_licenses <= 0) {
            return null;
        }

        // Check if user already has this license
        $existingAssignment = $this->assignments()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if ($existingAssignment) {
            return null;
        }

        $assignment = $this->assignments()->create([
            'user_id' => $user->id,
            'assigned_by' => $assignedBy->id,
            'assigned_at' => now(),
            'assignment_notes' => $notes,
            'is_active' => true,
        ]);

        $this->increment('used_licenses');

        return $assignment;
    }

    public function revokeFromUser(User $user, User $revokedBy, ?string $notes = null): bool
    {
        $assignment = $this->assignments()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$assignment) {
            return false;
        }

        $assignment->update([
            'is_active' => false,
            'revoked_at' => now(),
            'revocation_notes' => $notes,
        ]);

        $this->decrement('used_licenses');

        return true;
    }

    public function assignTo(User $user, User $assignedBy, ?string $notes = null): ?LicenseAssignment
    {
        return $this->assignToUser($user, $assignedBy, $notes);
    }

    public function revokeFrom(User $user, User $revokedBy, ?string $notes = null): bool
    {
        return $this->revokeFromUser($user, $revokedBy, $notes);
    }
}
