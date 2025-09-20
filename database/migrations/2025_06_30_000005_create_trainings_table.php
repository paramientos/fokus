<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->enum('type', ['online', 'classroom', 'workshop', 'conference']);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('provider')->nullable();
            $table->string('location')->nullable();
            $table->integer('max_participants')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->json('prerequisites')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('trainings');
    }
};
