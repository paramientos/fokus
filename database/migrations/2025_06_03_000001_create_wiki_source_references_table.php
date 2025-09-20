<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_source_references', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wiki_page_id')->constrained()->onDelete('cascade');
            $table->morphs('source'); // Task, Comment veya başka bir model olabilir
            $table->timestamps();

            $table->unique(['wiki_page_id', 'source_id', 'source_type'], 'wiki_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_source_references');
    }
};
