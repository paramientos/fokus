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
        // Önce mevcut verileri yedekleyelim
        $references = DB::table('wiki_source_references')->get();
        
        // Unique kısıtlamasını kaldıralım
        Schema::table('wiki_source_references', function (Blueprint $table) {
            $table->dropUnique('wiki_source_unique');
        });
        
        // source_id alanını UUID'ye uyumlu hale getirelim
        Schema::table('wiki_source_references', function (Blueprint $table) {
            $table->dropColumn('source_id');
        });
        
        Schema::table('wiki_source_references', function (Blueprint $table) {
            $table->uuid('source_id')->after('source_type')->nullable();
        });
        
        // Unique kısıtlamasını tekrar ekleyelim
        Schema::table('wiki_source_references', function (Blueprint $table) {
            $table->unique(['wiki_page_id', 'source_id', 'source_type'], 'wiki_source_unique');
        });
        
        // Yedeklenen verileri geri yükleyelim
        foreach ($references as $reference) {
            // Sadece Task tipindeki kayıtları UUID olarak ekleyelim
            if ($reference->source_type === 'App\\Models\\Task') {
                DB::table('wiki_source_references')->where('id', $reference->id)->update([
                    'source_id' => $reference->source_id,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Önce mevcut verileri yedekleyelim
        $references = DB::table('wiki_source_references')->get();
        
        // Unique kısıtlamasını kaldıralım
        Schema::table('wiki_source_references', function (Blueprint $table) {
            $table->dropUnique('wiki_source_unique');
        });
        
        // source_id alanını bigint'e geri çevirelim
        Schema::table('wiki_source_references', function (Blueprint $table) {
            $table->dropColumn('source_id');
        });
        
        Schema::table('wiki_source_references', function (Blueprint $table) {
            $table->unsignedBigInteger('source_id')->after('source_type')->nullable();
        });
        
        // Unique kısıtlamasını tekrar ekleyelim
        Schema::table('wiki_source_references', function (Blueprint $table) {
            $table->unique(['wiki_page_id', 'source_id', 'source_type'], 'wiki_source_unique');
        });
        
        // Yedeklenen verileri geri yükleyelim (Task tipindeki kayıtlar hariç)
        foreach ($references as $reference) {
            if ($reference->source_type !== 'App\\Models\\Task') {
                DB::table('wiki_source_references')->where('id', $reference->id)->update([
                    'source_id' => $reference->source_id,
                ]);
            }
        }
    }
};
