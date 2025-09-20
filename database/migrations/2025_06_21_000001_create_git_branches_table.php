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
        Schema::create('git_branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('repository_id')->constrained('git_repositories')->onDelete('cascade');
            $table->foreignUuid('task_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->enum('status', ['active', 'merged', 'deleted'])->default('active');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('last_commit_hash')->nullable();
            $table->timestamp('last_commit_date')->nullable();
            $table->timestamps();

            $table->unique(['repository_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('git_branches');
    }
};
