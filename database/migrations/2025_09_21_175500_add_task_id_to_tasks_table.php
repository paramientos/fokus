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
            $table->unsignedInteger('task_id')->nullable()->after('id');
        });

        // Mevcut görevlere task_id atama
        DB::statement('UPDATE tasks SET task_id = id');

        // task_id'nin proje bazında benzersiz olmasını sağlama
        Schema::table('tasks', function (Blueprint $table) {
            $table->unique(['project_id', 'task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'task_id']);
            $table->dropColumn('task_id');
        });
    }
};
