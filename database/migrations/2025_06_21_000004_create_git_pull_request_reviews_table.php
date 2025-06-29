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
        Schema::create('git_pull_request_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pull_request_id')->constrained('git_pull_requests')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('state', ['approved', 'changes_requested', 'commented'])->default('commented');
            $table->text('body')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
            
            $table->unique(['pull_request_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('git_pull_request_reviews');
    }
};
