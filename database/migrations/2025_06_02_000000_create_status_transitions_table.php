<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('status_transitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_status_id')->constrained('statuses')->onDelete('cascade');
            $table->foreignId('to_status_id')->constrained('statuses')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['project_id', 'from_status_id', 'to_status_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('status_transitions');
    }
};
