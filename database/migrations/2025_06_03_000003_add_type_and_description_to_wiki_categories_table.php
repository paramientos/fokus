<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::table('wiki_categories', function (Blueprint $table) {
            $table->string('type')->nullable()->after('slug')->comment('Kategori tipi (status, type, technical, user)');
            $table->text('description')->nullable()->after('type')->comment('Kategori açıklaması');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('wiki_categories', function (Blueprint $table) {
            $table->dropColumn(['type', 'description']);
        });
    }
};
