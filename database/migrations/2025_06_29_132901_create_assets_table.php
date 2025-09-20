<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('asset_category_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('created_by')->constrained('users')->onDelete('cascade');

            // Basic Information
            $table->string('name');
            $table->string('asset_tag')->unique(); // Unique identifier like "LAP-001"
            $table->text('description')->nullable();
            $table->enum('status', ['available', 'assigned', 'maintenance', 'retired', 'lost'])->default('available');

            // Asset Details
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();

            // Location
            $table->string('location')->nullable(); // Office, Remote, Storage etc.
            $table->string('room')->nullable();
            $table->string('desk')->nullable();

            // Maintenance
            $table->date('last_maintenance')->nullable();
            $table->date('next_maintenance')->nullable();
            $table->text('maintenance_notes')->nullable();

            // Additional Fields
            $table->json('custom_fields')->nullable(); // For flexible additional data
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['assigned_to']);
            $table->index(['asset_category_id']);
            $table->index(['warranty_expiry']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
