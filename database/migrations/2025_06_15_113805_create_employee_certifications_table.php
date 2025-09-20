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
        Schema::create('employee_certifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('certification_id')->constrained()->onDelete('cascade');
            $table->date('obtained_date');
            $table->date('expiry_date')->nullable();
            $table->string('certificate_number')->nullable();
            $table->decimal('score', 5, 2)->nullable(); // Exam score if applicable
            $table->enum('status', ['active', 'expired', 'revoked', 'pending_renewal'])->default('active');
            $table->text('notes')->nullable();
            $table->string('certificate_file_path')->nullable(); // Path to uploaded certificate
            $table->date('renewal_reminder_date')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'certification_id', 'obtained_date'], 'emp_cert_unique');
            $table->index(['status', 'expiry_date']);
            $table->index('renewal_reminder_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_certifications');
    }
};
