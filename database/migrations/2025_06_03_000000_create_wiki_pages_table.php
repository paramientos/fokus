<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->index();
            $table->text('content');
            $table->json('source_references')->nullable(); // Hangi task ve yorumlardan oluşturulduğu
            $table->boolean('is_auto_generated')->default(true);
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
        });

        Schema::create('wiki_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->index();
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
        });

        Schema::create('wiki_page_category', function (Blueprint $table) {
            $table->foreignUuid('wiki_page_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('wiki_category_id')->constrained()->onDelete('cascade');
            $table->primary(['wiki_page_id', 'wiki_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_page_category');
        Schema::dropIfExists('wiki_categories');
        Schema::dropIfExists('wiki_pages');
    }
};
