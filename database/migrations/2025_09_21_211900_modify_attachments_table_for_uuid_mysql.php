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
        $attachments = DB::table('attachments')->get();
        
        // MySQL ve PostgreSQL için farklı yaklaşımlar
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        
        if ($driver === 'mysql') {
            // MySQL için: Yeni bir tablo oluştur, verileri taşı ve tabloları değiştir
            Schema::create('attachments_new', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('filename');
                $table->string('path');
                $table->string('mime_type');
                $table->integer('size')->comment('File size in bytes');
                $table->string('description')->nullable();
                $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('attachable_type');
                $table->uuid('attachable_id')->nullable();
                $table->timestamps();
            });
            
            // Mevcut verileri yeni tabloya kopyalayalım
            foreach ($attachments as $attachment) {
                $data = (array) $attachment;
                
                // attachable_id alanını Task modeli için UUID olarak ayarla
                if ($attachment->attachable_type === 'App\\Models\\Task') {
                    // Diğer alanları kopyala
                    DB::table('attachments_new')->insert([
                        'id' => $attachment->id,
                        'filename' => $attachment->filename,
                        'path' => $attachment->path,
                        'mime_type' => $attachment->mime_type,
                        'size' => $attachment->size,
                        'description' => $attachment->description,
                        'user_id' => $attachment->user_id,
                        'attachable_type' => $attachment->attachable_type,
                        'attachable_id' => $attachment->attachable_id,
                        'created_at' => $attachment->created_at,
                        'updated_at' => $attachment->updated_at
                    ]);
                }
            }
            
            // Eski tabloyu kaldır ve yeni tabloyu yeniden adlandır
            Schema::dropIfExists('attachments');
            Schema::rename('attachments_new', 'attachments');
        } else {
            // PostgreSQL için: Orijinal yaklaşım
            // attachable_id alanını UUID'ye uyumlu hale getirelim
            Schema::table('attachments', function (Blueprint $table) {
                $table->dropColumn('attachable_id');
            });
            
            Schema::table('attachments', function (Blueprint $table) {
                $table->uuid('attachable_id')->after('attachable_type')->nullable();
            });
            
            // Yedeklenen verileri geri yükleyelim
            foreach ($attachments as $attachment) {
                // Sadece Task tipindeki kayıtları UUID olarak ekleyelim
                if ($attachment->attachable_type === 'App\\Models\\Task') {
                    DB::table('attachments')->where('id', $attachment->id)->update([
                        'attachable_id' => $attachment->attachable_id,
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
            Schema::create('attachments_new', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('filename');
                $table->string('path');
                $table->string('mime_type');
                $table->integer('size')->comment('File size in bytes');
                $table->string('description')->nullable();
                $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('attachable_type');
                $table->unsignedBigInteger('attachable_id')->nullable();
                $table->timestamps();
            });
            
            // Eski tabloyu kaldır ve yeni tabloyu yeniden adlandır
            Schema::dropIfExists('attachments');
            Schema::rename('attachments_new', 'attachments');
        } else {
            // PostgreSQL için: Orijinal yaklaşım
            // attachable_id alanını bigint'e geri çevirelim
            Schema::table('attachments', function (Blueprint $table) {
                $table->dropColumn('attachable_id');
            });
            
            Schema::table('attachments', function (Blueprint $table) {
                $table->unsignedBigInteger('attachable_id')->after('attachable_type')->nullable();
            });
            
            // Yedeklenen verileri geri yükleyelim (Task tipindeki kayıtlar hariç)
            foreach ($attachments as $attachment) {
                if ($attachment->attachable_type !== 'App\\Models\\Task') {
                    DB::table('attachments')->where('id', $attachment->id)->update([
                        'attachable_id' => $attachment->attachable_id,
                    ]);
                }
            }
        }
    }
};
