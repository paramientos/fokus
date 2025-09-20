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
        Schema::create('achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description');
            $table->string('icon')->default('fas.trophy');
            $table->enum('category', ['task', 'project', 'collaboration', 'learning', 'leadership', 'quality', 'streak', 'milestone']);
            $table->enum('type', ['bronze', 'silver', 'gold', 'platinum', 'diamond']);
            $table->integer('points')->default(0);
            $table->json('criteria'); // Achievement unlock criteria
            $table->boolean('is_active')->default(true);
            $table->boolean('is_repeatable')->default(false);
            $table->integer('max_level')->default(1); // For progressive achievements
            $table->string('badge_color')->default('#FFD700');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
