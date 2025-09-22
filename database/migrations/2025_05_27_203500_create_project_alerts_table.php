<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete(); // Alert sahibi
            $table->enum('type', [
                'deadline_risk',
                'resource_conflict',
                'bottleneck_detected',
                'velocity_drop',
                'overdue_tasks',
                'blocked_tasks',
                'budget_exceeded',
                'team_overload',
            ]);
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->string('title');
            $table->text('description');
            $table->json('metadata')->nullable(); // Ek bilgiler
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignUuid('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'type', 'is_resolved']);
            $table->index(['project_id', 'severity', 'is_resolved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_alerts');
    }
};
