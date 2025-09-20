<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * 
 *
 * @property string $id
 * @property string $employee_id
 * @property string $certification_id
 * @property \Illuminate\Support\Carbon $obtained_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property string|null $certificate_number
 * @property numeric|null $score
 * @property string $status
 * @property string|null $notes
 * @property string|null $certificate_file_path
 * @property \Illuminate\Support\Carbon|null $renewal_reminder_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Certification $certification
 * @property-read \App\Models\Employee $employee
 * @property-read int|null $days_until_expiry
 * @property-read string $status_color
 * @method static Builder<static>|EmployeeCertification active()
 * @method static Builder<static>|EmployeeCertification expired()
 * @method static Builder<static>|EmployeeCertification expiringSoon(int $days = 30)
 * @method static Builder<static>|EmployeeCertification newModelQuery()
 * @method static Builder<static>|EmployeeCertification newQuery()
 * @method static Builder<static>|EmployeeCertification query()
 * @method static Builder<static>|EmployeeCertification whereCertificateFilePath($value)
 * @method static Builder<static>|EmployeeCertification whereCertificateNumber($value)
 * @method static Builder<static>|EmployeeCertification whereCertificationId($value)
 * @method static Builder<static>|EmployeeCertification whereCreatedAt($value)
 * @method static Builder<static>|EmployeeCertification whereEmployeeId($value)
 * @method static Builder<static>|EmployeeCertification whereExpiryDate($value)
 * @method static Builder<static>|EmployeeCertification whereId($value)
 * @method static Builder<static>|EmployeeCertification whereNotes($value)
 * @method static Builder<static>|EmployeeCertification whereObtainedDate($value)
 * @method static Builder<static>|EmployeeCertification whereRenewalReminderDate($value)
 * @method static Builder<static>|EmployeeCertification whereScore($value)
 * @method static Builder<static>|EmployeeCertification whereStatus($value)
 * @method static Builder<static>|EmployeeCertification whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EmployeeCertification extends Model
{
    use HasUuids;

    protected $fillable = [
        'employee_id',
        'certification_id',
        'obtained_date',
        'expiry_date',
        'certificate_number',
        'score',
        'status',
        'notes',
        'certificate_file_path',
        'renewal_reminder_date',
    ];

    protected $casts = [
        'obtained_date' => 'date',
        'expiry_date' => 'date',
        'renewal_reminder_date' => 'date',
        'score' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function certification(): BelongsTo
    {
        return $this->belongsTo(Certification::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && $this->expiry_date->diffInDays(now()) <= $days && !$this->isExpired();
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return $this->expiry_date->diffInDays(now(), false);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => $this->isExpiringSoon() ? 'warning' : 'success',
            'expired' => 'error',
            'revoked' => 'error',
            'pending_renewal' => 'info',
            default => 'secondary',
        };
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'expired')
                    ->orWhere(function ($q) {
                        $q->where('expiry_date', '<', now())
                          ->where('status', '!=', 'expired');
                    });
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
                    ->where('expiry_date', '>', now())
                    ->where('status', 'active');
    }

    protected static function booted(): void
    {
        static::saving(function ($model) {
            // Auto-update status based on expiry date
            if ($model->expiry_date && $model->expiry_date->isPast() && $model->status === 'active') {
                $model->status = 'expired';
            }
        });
    }
}
