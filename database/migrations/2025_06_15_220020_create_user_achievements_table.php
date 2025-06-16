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
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained()->cascadeOnDelete();
            $table->integer('level')->default(1); // For progressive achievements
            $table->integer('progress')->default(0); // Current progress towards next level
            $table->integer('points_earned')->default(0);
            $table->timestamp('earned_at');
            $table->json('metadata')->nullable(); // Additional data like task_id, project_id etc.
            $table->timestamps();

            $table->unique(['user_id', 'achievement_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
    }
};
