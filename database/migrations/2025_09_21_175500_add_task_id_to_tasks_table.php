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

        // Mevcut görevlere task_id atama - her proje için sıralı ID'ler oluştur
        $projects = DB::table('projects')->get(['id']);

        foreach ($projects as $project) {
            $tasks = DB::table('tasks')
                ->where('project_id', $project->id)
                ->orderBy('created_at')
                ->get(['id']);

            $counter = 1;
            foreach ($tasks as $task) {
                DB::table('tasks')
                    ->where('id', $task->id)
                    ->update(['task_id' => $counter]);
                $counter++;
            }
        }

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
