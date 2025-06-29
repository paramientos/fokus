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
        Schema::create('password_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('password_vault_id')->constrained()->cascadeOnDelete();
            $table->foreignId('password_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('username')->nullable();
            $table->text('password_encrypted');
            $table->string('url')->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedTinyInteger('security_level')->default(0); // 0-5 güvenlik seviyesi
            $table->timestamps();
            $table->softDeletes(); // Çöp kutusu özelliği için
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_entries');
    }
};
