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
        Schema::table('workspace_workflow_instances', function (Blueprint $table) {
            if (!Schema::hasColumn('workspace_workflow_instances', 'description')) {
                $table->text('description')->nullable()->after('name');
            }

            if (!Schema::hasColumn('workspace_workflow_instances', 'status')) {
                $table->string('status')->default('pending')->after('custom_fields');
            }

            if (!Schema::hasColumn('workspace_workflow_instances', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('workspace_workflow_instances', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspace_workflow_instances', function (Blueprint $table) {
            $table->dropColumn(['description', 'status', 'started_at', 'completed_at']);
        });
    }
};
