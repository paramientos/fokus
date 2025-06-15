<?php

use App\Models\Payroll;
use App\Models\Employee;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public $employee_id = '';
    public $payroll_period = '';
    public $pay_date = '';
    public $base_salary = '';
    public $overtime_hours = 0;
    public $overtime_rate = '';
    public $overtime_pay = 0;
    public $bonus = 0;
    public $allowances = 0;
    public $tax_deduction = 0;
    public $social_security_deduction = 0;
    public $health_insurance_deduction = 0;
    public $other_deductions = 0;
    public $total_deductions = 0;
    public $gross_pay = 0;
    public $net_pay = 0;
    public $status = 'draft';
    public $notes = '';

    public array $statusOptions = [
        'draft' => 'Draft',
        'approved' => 'Approved',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled'
    ];

    public function mount()
    {
        // Set default payroll period (current month)
        $this->payroll_period = now()->format('Y-m');
        $this->pay_date = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatedEmployeeId()
    {
        if ($this->employee_id) {
            $employee = Employee::find($this->employee_id);
            if ($employee && $employee->salary) {
                $this->base_salary = $employee->salary;
                $this->calculatePay();
            }
        }
    }

    public function updatedBaseSalary()
    {
        $this->calculatePay();
    }

    public function updatedOvertimeHours()
    {
        $this->calculateOvertimePay();
        $this->calculatePay();
    }

    public function updatedOvertimeRate()
    {
        $this->calculateOvertimePay();
        $this->calculatePay();
    }

    public function updatedBonus()
    {
        $this->calculatePay();
    }

    public function updatedAllowances()
    {
        $this->calculatePay();
    }

    public function updatedTaxDeduction()
    {
        $this->calculateTotalDeductions();
        $this->calculatePay();
    }

    public function updatedSocialSecurityDeduction()
    {
        $this->calculateTotalDeductions();
        $this->calculatePay();
    }

    public function updatedHealthInsuranceDeduction()
    {
        $this->calculateTotalDeductions();
        $this->calculatePay();
    }

    public function updatedOtherDeductions()
    {
        $this->calculateTotalDeductions();
        $this->calculatePay();
    }

    public function calculateOvertimePay()
    {
        $this->overtime_pay = (float) $this->overtime_hours * (float) $this->overtime_rate;
    }

    public function calculateTotalDeductions()
    {
        $this->total_deductions = (float) $this->tax_deduction + 
                                 (float) $this->social_security_deduction + 
                                 (float) $this->health_insurance_deduction + 
                                 (float) $this->other_deductions;
    }

    public function calculatePay()
    {
        $this->calculateOvertimePay();
        $this->calculateTotalDeductions();
        
        $basic = (float) $this->base_salary;
        $overtime = (float) $this->overtime_pay;
        $bonus = (float) $this->bonus;
        $allowances = (float) $this->allowances;
        
        $this->gross_pay = $basic + $overtime + $bonus + $allowances;
        $this->net_pay = $this->gross_pay - $this->total_deductions;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'payroll_period' => 'required|string',
            'pay_date' => 'required|date',
            'base_salary' => 'required|numeric|min:0',
            'overtime_hours' => 'nullable|numeric|min:0',
            'overtime_rate' => 'nullable|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'tax_deduction' => 'nullable|numeric|min:0',
            'social_security_deduction' => 'nullable|numeric|min:0',
            'health_insurance_deduction' => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,approved,paid,cancelled',
            'notes' => 'nullable|string'
        ];
    }

    public function save()
    {
        $this->validate();

        try {
            // Check for duplicate payroll for same employee and period
            $exists = Payroll::where('workspace_id', session('workspace_id'))
                ->where('employee_id', $this->employee_id)
                ->where('payroll_period', $this->payroll_period)
                ->exists();

            if ($exists) {
                $this->error('Payroll already exists for this employee and pay period.');
                return;
            }

            Payroll::create([
                'workspace_id' => session('workspace_id'),
                'employee_id' => $this->employee_id,
                'payroll_period' => $this->payroll_period,
                'pay_date' => $this->pay_date,
                'base_salary' => $this->base_salary,
                'overtime_hours' => $this->overtime_hours,
                'overtime_rate' => $this->overtime_rate,
                'overtime_pay' => $this->overtime_pay,
                'bonus' => $this->bonus,
                'allowances' => $this->allowances,
                'gross_pay' => $this->gross_pay,
                'tax_deduction' => $this->tax_deduction,
                'social_security_deduction' => $this->social_security_deduction,
                'health_insurance_deduction' => $this->health_insurance_deduction,
                'other_deductions' => $this->other_deductions,
                'total_deductions' => $this->total_deductions,
                'net_pay' => $this->net_pay,
                'status' => $this->status,
                'notes' => $this->notes,
            ]);

            $this->success('Payroll created successfully!');
            return redirect()->route('hr.payroll.index');
        } catch (\Exception $e) {
            $this->error('Failed to create payroll: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        return redirect()->route('hr.payroll.index');
    }

    public function with()
    {
        $workspaceId = session('workspace_id');

        $employees = Employee::where('workspace_id', $workspaceId)
            ->with('user')
            ->get()
            ->map(fn($emp) => ['id' => $emp->id, 'name' => $emp->user->name . ' - ' . ($emp->position ?? 'N/A')]);

        return [
            'employees' => $employees,
            'statusOptions' => collect($this->statusOptions)->map(fn($name, $value) => ['id' => $value, 'name' => $name])
        ];
    }
}
?>

<div>
    <x-header title="Create Payroll" separator>
        <x-slot:middle class="!justify-end">
            <x-button label="Cancel" link="{{ route('hr.payroll.index') }}" />
        </x-slot:middle>
    </x-header>

    <x-form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-6">
                <x-card title="Basic Information">
                    <div class="space-y-4">
                        <x-select
                            label="Employee"
                            wire:model.live="employee_id"
                            :options="$employees"
                            placeholder="Select employee"
                            required
                        />

                        <div class="grid grid-cols-2 gap-4">
                            <x-input
                                label="Payroll Period"
                                wire:model="payroll_period"
                                type="month"
                                required
                                hint="e.g., 2025-06"
                            />

                            <x-input
                                label="Pay Date"
                                wire:model="pay_date"
                                type="date"
                                required
                            />
                        </div>

                        <x-select
                            label="Status"
                            wire:model="status"
                            :options="$statusOptions"
                            required
                        />
                    </div>
                </x-card>

                <x-card title="Earnings">
                    <div class="space-y-4">
                        <x-input
                            label="Base Salary"
                            wire:model.live="base_salary"
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                            prefix="$"
                            required
                        />

                        <div class="grid grid-cols-2 gap-4">
                            <x-input
                                label="Overtime Hours"
                                wire:model.live="overtime_hours"
                                type="number"
                                step="0.5"
                                placeholder="0"
                            />

                            <x-input
                                label="Overtime Rate (per hour)"
                                wire:model.live="overtime_rate"
                                type="number"
                                step="0.01"
                                placeholder="0.00"
                                prefix="$"
                            />
                        </div>

                        <x-input
                            label="Overtime Pay"
                            wire:model="overtime_pay"
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                            prefix="$"
                            readonly
                            class="bg-gray-50"
                        />

                        <x-input
                            label="Bonus"
                            wire:model.live="bonus"
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                            prefix="$"
                        />

                        <x-input
                            label="Allowances"
                            wire:model.live="allowances"
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                            prefix="$"
                        />
                    </div>
                </x-card>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <x-card title="Deductions">
                    <div class="space-y-4">
                        <x-input
                            label="Tax Deduction"
                            wire:model.live="tax_deduction"
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                            prefix="$"
                        />

                        <x-input
                            label="Social Security Deduction"
                            wire:model.live="social_security_deduction"
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                            prefix="$"
                        />

                        <x-input
                            label="Health Insurance Deduction"
                            wire:model.live="health_insurance_deduction"
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                            prefix="$"
                        />

                        <x-input
                            label="Other Deductions"
                            wire:model.live="other_deductions"
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                            prefix="$"
                        />

                        <x-input
                            label="Total Deductions"
                            wire:model="total_deductions"
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                            prefix="$"
                            readonly
                            class="bg-gray-50"
                        />
                    </div>
                </x-card>

                <x-card title="Pay Summary" class="bg-gray-50">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="font-medium text-gray-700">Gross Pay:</span>
                            <span class="text-lg font-semibold text-green-600">${{ number_format($gross_pay, 2) }}</span>
                        </div>

                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="font-medium text-gray-700">Total Deductions:</span>
                            <span class="text-lg font-semibold text-red-600">${{ number_format($total_deductions, 2) }}</span>
                        </div>

                        <div class="flex justify-between items-center py-3 border-t-2 border-gray-300">
                            <span class="text-lg font-bold text-gray-900">Net Pay:</span>
                            <span class="text-xl font-bold text-blue-600">${{ number_format($net_pay, 2) }}</span>
                        </div>
                    </div>
                </x-card>

                <x-card title="Additional Information">
                    <div class="space-y-4">
                        <x-textarea
                            label="Notes"
                            wire:model="notes"
                            placeholder="Any additional notes or comments..."
                            rows="4"
                        />

                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="flex items-start space-x-2">
                                <x-icon name="fas.info-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                                <div class="text-sm text-blue-800">
                                    <p class="font-medium">Payroll Calculation:</p>
                                    <ul class="list-disc list-inside mt-1 space-y-1">
                                        <li>Gross Pay = Base Salary + Overtime Pay + Bonus + Allowances</li>
                                        <li>Net Pay = Gross Pay - Total Deductions</li>
                                        <li>All calculations are updated automatically</li>
                                        <li>Duplicate payrolls for same period are not allowed</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" wire:click="cancel" />
            <x-button label="Create Payroll" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-form>
</div>
