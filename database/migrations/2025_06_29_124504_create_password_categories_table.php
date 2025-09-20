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
        Schema::create('password_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('password_vault_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('#4f46e5'); // Default indigo color
            $table->string('icon')->default('fas.folder'); // Default folder icon
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_categories');
    }
};
