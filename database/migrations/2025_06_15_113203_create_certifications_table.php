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
        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('issuing_organization');
            $table->string('category')->nullable(); // e.g., 'Technical', 'Management', 'Safety'
            $table->integer('validity_months')->nullable(); // How many months the certification is valid
            $table->decimal('cost', 8, 2)->nullable();
            $table->string('certification_url')->nullable();
            $table->json('requirements')->nullable(); // Prerequisites, experience needed
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['workspace_id', 'category']);
            $table->index(['is_active', 'is_mandatory']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certifications');
    }
};
