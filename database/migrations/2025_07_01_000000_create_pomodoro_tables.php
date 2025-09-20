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
        Schema::create('pomodoro_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('workspace_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->integer('work_duration')->default(25); // Dakika cinsinden
            $table->integer('break_duration')->default(5); // Dakika cinsinden
            $table->integer('long_break_duration')->default(15); // Dakika cinsinden
            $table->integer('long_break_interval')->default(4); // Kaç pomodoro sonrası uzun mola
            $table->integer('completed_pomodoros')->default(0);
            $table->integer('target_pomodoros')->default(4);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['not_started', 'work', 'break', 'long_break', 'completed', 'paused'])->default('not_started');
            $table->timestamps();
        });

        Schema::create('pomodoro_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pomodoro_session_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['work', 'break', 'long_break']);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->boolean('completed')->default(false);
            $table->integer('duration')->default(0); // Saniye cinsinden gerçek süre
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('pomodoro_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('color')->default('#3b82f6'); // Varsayılan mavi
            $table->timestamps();
        });

        Schema::create('pomodoro_session_tag', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pomodoro_session_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('pomodoro_tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pomodoro_session_tag');
        Schema::dropIfExists('pomodoro_tags');
        Schema::dropIfExists('pomodoro_logs');
        Schema::dropIfExists('pomodoro_sessions');
    }
};
