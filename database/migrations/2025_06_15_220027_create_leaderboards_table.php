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
        Schema::create('leaderboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('period', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'all_time']);
            $table->enum('category', ['overall', 'tasks', 'projects', 'collaboration', 'learning', 'quality']);
            $table->integer('total_points')->default(0);
            $table->integer('achievements_count')->default(0);
            $table->integer('tasks_completed')->default(0);
            $table->integer('projects_completed')->default(0);
            $table->decimal('quality_score', 5, 2)->default(0);
            $table->integer('streak_days')->default(0);
            $table->integer('rank')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamps();

            $table->unique(['workspace_id', 'user_id', 'period', 'category', 'period_start'], 'leaderboard_unique_constraint');
            $table->index(['workspace_id', 'period', 'category', 'rank'], 'leaderboard_ranking_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaderboards');
    }
};
