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
        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('time_spent')->nullable()->default(0)->comment('Time spent in minutes');
            $table->integer('time_estimate')->nullable()->default(0)->comment('Time estimate in minutes');
            $table->timestamp('started_at')->nullable()->comment('When the task was started');
            $table->timestamp('completed_at')->nullable()->comment('When the task was completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['time_spent', 'time_estimate', 'started_at', 'completed_at']);
        });
    }
};
