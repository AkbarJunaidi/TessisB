<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanda tangan digital milik user - bisa lebih dari 1 per user (digambar
     * langsung di kanvas ATAU upload gambar), dipilih salah satu saat cetak
     * Kwitansi (lihat kwitansis.signature_id di migration berikutnya).
     */
    public function up(): void
    {
        if (!Schema::hasTable('signatures')) {
            Schema::create('signatures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('label');
                $table->string('file_path');
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->index(['user_id', 'is_default']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
