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
        $attachments = DB::table('attachments')->get();
        
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Önce mevcut verileri yedekleyelim
        $attachments = DB::table('attachments')->get();
        
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
};
