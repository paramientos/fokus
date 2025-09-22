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
        Schema::table('workspace_workflow_step_instances', function (Blueprint $table) {
            if (!Schema::hasColumn('workspace_workflow_step_instances', 'form_data')) {
                $table->json('form_data')->nullable();
            }

            if (!Schema::hasColumn('workspace_workflow_step_instances', 'comments')) {
                $table->text('comments')->nullable();
            }

            // PHPDoc'ta data ve notes olarak geçen sütunlar
            if (!Schema::hasColumn('workspace_workflow_step_instances', 'data')) {
                $table->json('data')->nullable();
            }

            if (!Schema::hasColumn('workspace_workflow_step_instances', 'notes')) {
                $table->text('notes')->nullable();
            }

            if (!Schema::hasColumn('workspace_workflow_step_instances', 'status')) {
                $table->string('status')->default('pending');
            }

            if (!Schema::hasColumn('workspace_workflow_step_instances', 'started_at')) {
                $table->timestamp('started_at')->nullable();
            }

            if (!Schema::hasColumn('workspace_workflow_step_instances', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspace_workflow_step_instances', function (Blueprint $table) {
            $table->dropColumn(['form_data', 'comments', 'data', 'notes', 'status', 'started_at', 'completed_at']);
        });
    }
};
