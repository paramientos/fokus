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
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUuid('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('status_id')->constrained()->onDelete('restrict');
            $table->foreignUuid('sprint_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignUuid('user_id')->nullable()->constrained()->onDelete('set null'); // Assigned to
            $table->foreignUuid('reporter_id')->constrained('users')->onDelete('cascade'); // Created by
            $table->string('task_type')->default('task'); // task, bug, story, epic
            $table->integer('priority')->default(3); // 1-5 (highest to lowest)
            $table->integer('story_points')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
