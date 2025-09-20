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
        Schema::create('git_repositories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('project_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('provider'); // github, gitlab, bitbucket
            $table->string('repository_url');
            $table->string('api_token')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->string('default_branch')->default('main');
            $table->string('branch_prefix')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'project_id', 'repository_url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('git_repositories');
    }
};
