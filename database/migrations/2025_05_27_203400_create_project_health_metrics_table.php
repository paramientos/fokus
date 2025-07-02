<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_health_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('metric_date');
            $table->decimal('health_score', 5, 2); // 0-100 arası skor
            $table->json('risk_factors'); // Risk faktörleri array
            $table->json('bottlenecks'); // Tespit edilen darboğazlar
            $table->json('warnings'); // Uyarılar
            $table->integer('completed_tasks_count');
            $table->integer('overdue_tasks_count');
            $table->integer('blocked_tasks_count');
            $table->decimal('velocity', 8, 2)->nullable(); // Sprint velocity
            $table->decimal('burndown_rate', 8, 2)->nullable(); // Burndown oranı
            $table->integer('team_workload_score'); // 1-10 arası
            $table->timestamps();

            $table->unique(['project_id', 'metric_date']);
            $table->index(['project_id', 'metric_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_health_metrics');
    }
};
