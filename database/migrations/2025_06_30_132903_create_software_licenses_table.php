<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software_licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('created_by')->constrained('users')->onDelete('cascade');

            // License Information
            $table->string('name');
            $table->string('vendor');
            $table->string('version')->nullable();
            $table->enum('license_type', ['perpetual', 'subscription', 'trial', 'open_source'])->default('subscription');
            $table->string('license_key')->nullable();

            // Subscription Details
            $table->date('purchase_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly', 'one_time'])->nullable();

            // Usage Limits
            $table->integer('total_licenses')->default(1);
            $table->integer('used_licenses')->default(0);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            // Status
            $table->enum('status', ['active', 'expired', 'cancelled', 'trial'])->default('active');
            $table->boolean('auto_renewal')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software_licenses');
    }
};
