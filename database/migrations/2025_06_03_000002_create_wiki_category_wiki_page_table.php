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
        Schema::create('wiki_category_wiki_page', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_category_id')->constrained()->onDelete('cascade');
            $table->foreignId('wiki_page_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Aynı kategori-sayfa ilişkisinin tekrarlanmaması için unique index
            $table->unique(['wiki_category_id', 'wiki_page_id'], 'wiki_cat_page_unique');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_category_wiki_page');
    }
};
