<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_license_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            
            $table->datetime('assigned_at');
            $table->datetime('revoked_at')->nullable();
            $table->text('assignment_notes')->nullable();
            $table->text('revocation_notes')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->index(['software_license_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
            $table->unique(['software_license_id', 'user_id', 'is_active'], 'unique_active_license_assignment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_assignments');
    }
};
