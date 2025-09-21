<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Önce mevcut verileri yedekleyelim
        $references = DB::table('wiki_source_references')->get();
        
        // MySQL ve PostgreSQL için farklı yaklaşımlar
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        
        if ($driver === 'mysql') {
            // MySQL için: Yeni bir tablo oluştur, verileri taşı ve tabloları değiştir
            Schema::create('wiki_source_references_new', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('wiki_page_id');
                $table->string('source_type');
                $table->uuid('source_id')->nullable();
                $table->timestamps();
                
                $table->unique(['wiki_page_id', 'source_id', 'source_type'], 'wiki_source_unique');
                
                // MySQL'de foreign key kısıtlaması eklemek için
                $table->foreign('wiki_page_id')->references('id')->on('wiki_pages')->onDelete('cascade');
            });
            
            // Mevcut verileri yeni tabloya kopyalayalım
            foreach ($references as $reference) {
                // Sadece Task tipindeki kayıtları UUID olarak ekleyelim
                if ($reference->source_type === 'App\\Models\\Task') {
                    DB::table('wiki_source_references_new')->insert([
                        'id' => $reference->id,
                        'wiki_page_id' => $reference->wiki_page_id,
                        'source_type' => $reference->source_type,
                        'source_id' => $reference->source_id,
                        'created_at' => $reference->created_at,
                        'updated_at' => $reference->updated_at
                    ]);
                }
            }
            
            // Eski tabloyu kaldır ve yeni tabloyu yeniden adlandır
            Schema::dropIfExists('wiki_source_references');
            Schema::rename('wiki_source_references_new', 'wiki_source_references');
        } else {
            // PostgreSQL için: Orijinal yaklaşım
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // MySQL ve PostgreSQL için farklı yaklaşımlar
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        
        if ($driver === 'mysql') {
            // MySQL için: Yeni bir tablo oluştur, verileri taşı ve tabloları değiştir
            Schema::create('wiki_source_references_new', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('wiki_page_id');
                $table->string('source_type');
                $table->unsignedBigInteger('source_id')->nullable();
                $table->timestamps();
                
                $table->unique(['wiki_page_id', 'source_id', 'source_type'], 'wiki_source_unique');
                
                // MySQL'de foreign key kısıtlaması eklemek için
                $table->foreign('wiki_page_id')->references('id')->on('wiki_pages')->onDelete('cascade');
            });
            
            // Eski tabloyu kaldır ve yeni tabloyu yeniden adlandır
            Schema::dropIfExists('wiki_source_references');
            Schema::rename('wiki_source_references_new', 'wiki_source_references');
        } else {
            // PostgreSQL için: Orijinal yaklaşım
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
    }
};
