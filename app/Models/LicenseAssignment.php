<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $software_license_id
 * @property string $user_id
 * @property string $assigned_by
 * @property \Illuminate\Support\Carbon $assigned_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property string|null $assignment_notes
 * @property string|null $revocation_notes
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $assignedBy
 * @property-read string|null $duration
 * @property-read \App\Models\SoftwareLicense $softwareLicense
 * @property-read \App\Models\User $user
 *
 * @method static Builder<static>|LicenseAssignment newModelQuery()
 * @method static Builder<static>|LicenseAssignment newQuery()
 * @method static Builder<static>|LicenseAssignment query()
 * @method static Builder<static>|LicenseAssignment whereAssignedAt($value)
 * @method static Builder<static>|LicenseAssignment whereAssignedBy($value)
 * @method static Builder<static>|LicenseAssignment whereAssignmentNotes($value)
 * @method static Builder<static>|LicenseAssignment whereCreatedAt($value)
 * @method static Builder<static>|LicenseAssignment whereId($value)
 * @method static Builder<static>|LicenseAssignment whereIsActive($value)
 * @method static Builder<static>|LicenseAssignment whereRevocationNotes($value)
 * @method static Builder<static>|LicenseAssignment whereRevokedAt($value)
 * @method static Builder<static>|LicenseAssignment whereSoftwareLicenseId($value)
 * @method static Builder<static>|LicenseAssignment whereUpdatedAt($value)
 * @method static Builder<static>|LicenseAssignment whereUserId($value)
 *
 * @mixin \Eloquent
 */
class LicenseAssignment extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'software_license_id',
        'user_id',
        'assigned_by',
        'assigned_at',
        'revoked_at',
        'assignment_notes',
        'revocation_notes',
        'is_active',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'revoked_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('workspace', function (Builder $builder) {
            $builder->whereHas('softwareLicense', function (Builder|SoftwareLicense $query) {
                $query->where('workspace_id', get_workspace_id());
            });
        });
    }

    public function softwareLicense(): BelongsTo
    {
        return $this->belongsTo(SoftwareLicense::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->assigned_at) {
            return null;
        }

        $endDate = $this->revoked_at ?? now();
        $duration = $this->assigned_at->diffForHumans($endDate, true);

        return $duration;
    }
}
