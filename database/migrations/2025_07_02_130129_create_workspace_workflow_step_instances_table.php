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
        Schema::create('workspace_workflow_step_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_workflow_instance_id')
                ->constrained('workspace_workflow_instances', 'id', 'wf_instance_fk')
                ->onDelete('cascade');
            $table->foreignId('workspace_workflow_step_id')
                ->constrained('workspace_workflow_steps', 'id', 'wf_step_fk')
                ->onDelete('cascade');
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users', 'id', 'wf_step_assigned_fk')
                ->onDelete('set null');
            $table->foreignId('completed_by')
                ->nullable()
                ->constrained('users', 'id', 'wf_step_completed_fk')
                ->onDelete('set null');
            $table->string('status')->default('pending'); // pending, in_progress, completed, rejected
            $table->json('data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_workflow_step_instances');
    }
};
