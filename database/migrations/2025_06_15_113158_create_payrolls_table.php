<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('workspace_id')->constrained()->onDelete('cascade');
            $table->string('payroll_period'); // e.g., "2025-06", "Q1-2025"
            $table->date('pay_date');
            $table->decimal('base_salary', 10, 2);
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->decimal('overtime_rate', 8, 2)->default(0);
            $table->decimal('overtime_pay', 10, 2)->default(0);
            $table->decimal('bonus', 10, 2)->default(0);
            $table->decimal('allowances', 10, 2)->default(0);
            $table->decimal('gross_pay', 10, 2);
            $table->decimal('tax_deduction', 10, 2)->default(0);
            $table->decimal('social_security_deduction', 10, 2)->default(0);
            $table->decimal('health_insurance_deduction', 10, 2)->default(0);
            $table->decimal('other_deductions', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('net_pay', 10, 2);
            $table->enum('status', ['draft', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->json('deduction_details')->nullable(); // Detailed breakdown of deductions
            $table->json('allowance_details')->nullable(); // Detailed breakdown of allowances
            $table->timestamp('approved_at')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['employee_id', 'payroll_period']);
            $table->index(['workspace_id', 'payroll_period']);
            $table->index(['status', 'pay_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
