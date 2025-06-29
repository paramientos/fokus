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
        Schema::create('git_pull_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained('git_repositories')->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'closed', 'merged'])->default('open');
            $table->string('source_branch');
            $table->string('target_branch');
            $table->foreignId('author_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('merged_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('url');
            $table->timestamps();
            
            $table->unique(['repository_id', 'number']);
            $table->index(['task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('git_pull_requests');
    }
};
