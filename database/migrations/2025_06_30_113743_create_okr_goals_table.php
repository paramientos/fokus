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
        Schema::create('okr_goals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('workspace_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('performance_review_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['objective', 'key_result'])->default('objective');
            $table->foreignUuid('parent_id')->nullable()->constrained('okr_goals')->onDelete('cascade'); // For key results under objectives
            $table->decimal('target_value', 10, 2)->nullable(); // Numeric target for key results
            $table->decimal('current_value', 10, 2)->default(0); // Current progress
            $table->string('unit')->nullable(); // e.g., '%', 'count', 'hours'
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['not_started', 'in_progress', 'on_track', 'at_risk', 'completed', 'cancelled'])->default('not_started');
            $table->integer('progress_percentage')->default(0); // 0-100
            $table->text('notes')->nullable();
            $table->json('milestones')->nullable(); // Key milestones and deadlines
            $table->timestamps();

            $table->index(['employee_id', 'type']);
            $table->index(['workspace_id', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('okr_goals');
    }
};
