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
        Schema::create('git_commits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('repository_id')->constrained('git_repositories')->onDelete('cascade');
            $table->foreignUuid('branch_id')->nullable()->constrained('git_branches')->onDelete('set null');
            $table->foreignUuid('task_id')->nullable()->constrained()->onDelete('set null');
            $table->string('hash')->unique();
            $table->text('message');
            $table->string('author_name');
            $table->string('author_email');
            $table->timestamp('committed_date');
            $table->json('files_changed')->nullable();
            $table->integer('additions')->default(0);
            $table->integer('deletions')->default(0);
            $table->timestamps();

            $table->index(['repository_id', 'hash']);
            $table->index(['task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('git_commits');
    }
};
