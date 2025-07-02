<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pomodoro_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->boolean('notification')->default(true);
            $table->boolean('sound')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'workspace_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('pomodoro_settings');
    }
};
