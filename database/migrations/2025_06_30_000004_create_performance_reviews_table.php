<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignUuid('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->date('review_date');
            $table->date('next_review_date');
            $table->json('goals');
            $table->json('strengths');
            $table->json('improvement_areas');
            $table->decimal('overall_rating', 3, 1);
            $table->text('feedback')->nullable();
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_reviews');
    }
};
