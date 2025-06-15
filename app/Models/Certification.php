<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * 
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string|null $description
 * @property string $issuing_organization
 * @property string|null $category
 * @property int|null $validity_months
 * @property numeric|null $cost
 * @property string|null $certification_url
 * @property array<array-key, mixed>|null $requirements
 * @property bool $is_mandatory
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmployeeCertification> $employeeCertifications
 * @property-read int|null $employee_certifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @property-read string $status_color
 * @property-read \App\Models\Workspace $workspace
 * @method static Builder<static>|Certification active()
 * @method static Builder<static>|Certification byCategory(string $category)
 * @method static Builder<static>|Certification mandatory()
 * @method static Builder<static>|Certification newModelQuery()
 * @method static Builder<static>|Certification newQuery()
 * @method static Builder<static>|Certification query()
 * @method static Builder<static>|Certification whereCategory($value)
 * @method static Builder<static>|Certification whereCertificationUrl($value)
 * @method static Builder<static>|Certification whereCost($value)
 * @method static Builder<static>|Certification whereCreatedAt($value)
 * @method static Builder<static>|Certification whereDescription($value)
 * @method static Builder<static>|Certification whereId($value)
 * @method static Builder<static>|Certification whereIsActive($value)
 * @method static Builder<static>|Certification whereIsMandatory($value)
 * @method static Builder<static>|Certification whereIssuingOrganization($value)
 * @method static Builder<static>|Certification whereName($value)
 * @method static Builder<static>|Certification whereRequirements($value)
 * @method static Builder<static>|Certification whereUpdatedAt($value)
 * @method static Builder<static>|Certification whereValidityMonths($value)
 * @method static Builder<static>|Certification whereWorkspaceId($value)
 * @mixin \Eloquent
 */
class Certification extends Model
{
    protected $fillable = [
        'workspace_id',
        'name',
        'description',
        'issuing_organization',
        'category',
        'validity_months',
        'cost',
        'certification_url',
        'requirements',
        'is_mandatory',
        'is_active',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'requirements' => 'array',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('workspace', function (Builder $query) {
            if (session('workspace_id')) {
                $query->where('workspace_id', session('workspace_id'));
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function employeeCertifications(): HasMany
    {
        return $this->hasMany(EmployeeCertification::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_certifications')
                    ->withPivot(['obtained_date', 'expiry_date', 'status', 'score'])
                    ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeMandatory(Builder $query): Builder
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function getStatusColorAttribute(): string
    {
        if (!$this->is_active) {
            return 'error';
        }
        
        return $this->is_mandatory ? 'warning' : 'success';
    }
}
