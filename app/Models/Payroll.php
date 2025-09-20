<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Employee;
use App\Models\Workspace;
use App\Models\User;

/**
 *
 *
 * @property int $id
 * @property int $employee_id
 * @property int $workspace_id
 * @property string $payroll_period
 * @property \Illuminate\Support\Carbon $pay_date
 * @property numeric $base_salary
 * @property numeric $overtime_hours
 * @property numeric $overtime_rate
 * @property numeric $overtime_pay
 * @property numeric $bonus
 * @property numeric $allowances
 * @property numeric $gross_pay
 * @property numeric $tax_deduction
 * @property numeric $social_security_deduction
 * @property numeric $health_insurance_deduction
 * @property numeric $other_deductions
 * @property numeric $total_deductions
 * @property numeric $net_pay
 * @property string $status
 * @property string|null $notes
 * @property array<array-key, mixed>|null $deduction_details
 * @property array<array-key, mixed>|null $allowance_details
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $approver
 * @property-read Employee $employee
 * @property-read string $status_color
 * @property-read Workspace $workspace
 * @method static Builder<static>|Payroll byStatus(string $status)
 * @method static Builder<static>|Payroll forPeriod(string $period)
 * @method static Builder<static>|Payroll newModelQuery()
 * @method static Builder<static>|Payroll newQuery()
 * @method static Builder<static>|Payroll query()
 * @method static Builder<static>|Payroll whereAllowanceDetails($value)
 * @method static Builder<static>|Payroll whereAllowances($value)
 * @method static Builder<static>|Payroll whereApprovedAt($value)
 * @method static Builder<static>|Payroll whereApprovedBy($value)
 * @method static Builder<static>|Payroll whereBaseSalary($value)
 * @method static Builder<static>|Payroll whereBonus($value)
 * @method static Builder<static>|Payroll whereCreatedAt($value)
 * @method static Builder<static>|Payroll whereDeductionDetails($value)
 * @method static Builder<static>|Payroll whereEmployeeId($value)
 * @method static Builder<static>|Payroll whereGrossPay($value)
 * @method static Builder<static>|Payroll whereHealthInsuranceDeduction($value)
 * @method static Builder<static>|Payroll whereId($value)
 * @method static Builder<static>|Payroll whereNetPay($value)
 * @method static Builder<static>|Payroll whereNotes($value)
 * @method static Builder<static>|Payroll whereOtherDeductions($value)
 * @method static Builder<static>|Payroll whereOvertimeHours($value)
 * @method static Builder<static>|Payroll whereOvertimePay($value)
 * @method static Builder<static>|Payroll whereOvertimeRate($value)
 * @method static Builder<static>|Payroll wherePayDate($value)
 * @method static Builder<static>|Payroll wherePayrollPeriod($value)
 * @method static Builder<static>|Payroll whereSocialSecurityDeduction($value)
 * @method static Builder<static>|Payroll whereStatus($value)
 * @method static Builder<static>|Payroll whereTaxDeduction($value)
 * @method static Builder<static>|Payroll whereTotalDeductions($value)
 * @method static Builder<static>|Payroll whereUpdatedAt($value)
 * @method static Builder<static>|Payroll whereWorkspaceId($value)
 * @mixin \Eloquent
 */
class Payroll extends Model
{
    use HasUuids;

    protected $fillable = [
        'employee_id',
        'workspace_id',
        'payroll_period',
        'pay_date',
        'base_salary',
        'overtime_hours',
        'overtime_rate',
        'overtime_pay',
        'bonus',
        'allowances',
        'gross_pay',
        'tax_deduction',
        'social_security_deduction',
        'health_insurance_deduction',
        'other_deductions',
        'total_deductions',
        'net_pay',
        'status',
        'notes',
        'deduction_details',
        'allowance_details',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'pay_date' => 'date',
        'base_salary' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'bonus' => 'decimal:2',
        'allowances' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'tax_deduction' => 'decimal:2',
        'social_security_deduction' => 'decimal:2',
        'health_insurance_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'deduction_details' => 'array',
        'allowance_details' => 'array',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('workspace', function (Builder $query) {
            if (session('workspace_id')) {
                $query->where('workspace_id', session('workspace_id'));
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function calculateGrossPay(): void
    {
        $this->gross_pay = $this->base_salary + $this->overtime_pay + $this->bonus + $this->allowances;
    }

    public function calculateTotalDeductions(): void
    {
        $this->total_deductions = $this->tax_deduction + $this->social_security_deduction +
                                 $this->health_insurance_deduction + $this->other_deductions;
    }

    public function calculateNetPay(): void
    {
        $this->calculateGrossPay();
        $this->calculateTotalDeductions();
        $this->net_pay = $this->gross_pay - $this->total_deductions;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'warning',
            'approved' => 'info',
            'paid' => 'success',
            'cancelled' => 'error',
            default => 'secondary',
        };
    }

    public function scopeForPeriod(Builder $query, string $period): Builder
    {
        return $query->where('payroll_period', $period);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
