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
        Schema::create('api_endpoint_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_endpoint_id')->constrained()->onDelete('cascade');
            $table->string('request_url');
            $table->string('request_method');
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->integer('response_status_code');
            $table->json('response_headers')->nullable();
            $table->json('response_body')->nullable();
            $table->integer('execution_time_ms');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_endpoint_histories');
    }
};
