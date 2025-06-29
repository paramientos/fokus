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
        Schema::table('git_repositories', function (Blueprint $table) {
            $table->timestamp('api_token_expires_at')->nullable()->after('api_token');
            $table->string('refresh_token')->nullable()->after('api_token_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('git_repositories', function (Blueprint $table) {
            $table->dropColumn(['api_token_expires_at', 'refresh_token']);
        });
    }
};
