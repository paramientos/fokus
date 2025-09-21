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
        $taggables = DB::table('taggables')->get();
        
        // MySQL ve PostgreSQL için farklı yaklaşımlar
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        
        if ($driver === 'mysql') {
            // MySQL için: Yeni bir tablo oluştur, verileri taşı ve tabloları değiştir
            Schema::create('taggables_new', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tag_id');
                $table->uuid('taggable_id');
                $table->string('taggable_type');
                $table->timestamps();
                
                $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
            });
            
            // Sadece Task tipindeki kayıtları yeni tabloya ekleyelim
            foreach ($taggables as $taggable) {
                if ($taggable->taggable_type === 'App\\Models\\Task') {
                    DB::table('taggables_new')->insert([
                        'id' => \Illuminate\Support\Str::uuid()->toString(),
                        'tag_id' => $taggable->tag_id,
                        'taggable_id' => $taggable->taggable_id,
                        'taggable_type' => $taggable->taggable_type,
                        'created_at' => $taggable->created_at,
                        'updated_at' => $taggable->updated_at
                    ]);
                }
            }
            
            // Eski tabloyu kaldır ve yeni tabloyu yeniden adlandır
            Schema::dropIfExists('taggables');
            Schema::rename('taggables_new', 'taggables');
        } else {
            // PostgreSQL için: Orijinal yaklaşım
            // Mevcut taggables tablosundan unique kısıtlamasını kaldıralım
            Schema::table('taggables', function (Blueprint $table) {
                $table->dropUnique(['tag_id', 'taggable_id', 'taggable_type']);
            });

            // taggable_id alanını UUID'ye uyumlu hale getirelim
            Schema::table('taggables', function (Blueprint $table) {
                $table->dropColumn('taggable_id');
            });
            
            Schema::table('taggables', function (Blueprint $table) {
                $table->uuid('taggable_id')->after('tag_id');
            });
            
            // Unique kısıtlamasını tekrar ekleyelim
            Schema::table('taggables', function (Blueprint $table) {
                $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
            });
            
            // Yedeklenen verileri geri yükleyelim
            foreach ($taggables as $taggable) {
                // Sadece Task tipindeki kayıtları UUID olarak ekleyelim
                if ($taggable->taggable_type === 'App\\Models\\Task') {
                    DB::table('taggables')->insert([
                        'id' => \Illuminate\Support\Str::uuid()->toString(),
                        'tag_id' => $taggable->tag_id,
                        'taggable_id' => $taggable->taggable_id,
                        'taggable_type' => $taggable->taggable_type,
                        'created_at' => $taggable->created_at,
                        'updated_at' => $taggable->updated_at
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
            Schema::create('taggables_new', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tag_id');
                $table->unsignedBigInteger('taggable_id');
                $table->string('taggable_type');
                $table->timestamps();
                
                $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
            });
            
            // Eski tabloyu kaldır ve yeni tabloyu yeniden adlandır
            Schema::dropIfExists('taggables');
            Schema::rename('taggables_new', 'taggables');
        } else {
            // PostgreSQL için: Orijinal yaklaşım
            // Mevcut taggables tablosundan unique kısıtlamasını kaldıralım
            Schema::table('taggables', function (Blueprint $table) {
                $table->dropUnique(['tag_id', 'taggable_id', 'taggable_type']);
            });

            // taggable_id alanını bigint'e geri çevirelim
            Schema::table('taggables', function (Blueprint $table) {
                $table->dropColumn('taggable_id');
            });
            
            Schema::table('taggables', function (Blueprint $table) {
                $table->unsignedBigInteger('taggable_id')->after('tag_id');
            });
            
            // Unique kısıtlamasını tekrar ekleyelim
            Schema::table('taggables', function (Blueprint $table) {
                $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
            });
        }
    }
};
