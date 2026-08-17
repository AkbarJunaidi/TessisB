<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('surat_jalans')) {
            Schema::create('surat_jalans', function (Blueprint $table) {
                $table->id();
                $table->string('nomor')->unique(); // contoh: SJ-2026-0007

                $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

                $table->string('kepada')->nullable();
                $table->string('keperluan')->nullable();

                $table->string('pic')->nullable();
                $table->date('tanggal_terbit');

                // Laporan Kerja (mengikuti template surat jalan CV. Arindra Production)
                $table->date('tanggal_keberangkatan')->nullable();
                $table->time('jam_berangkat')->nullable();
                $table->date('tanggal_gladi_bersih')->nullable();
                $table->time('waktu_gladi_bersih')->nullable();
                $table->date('tanggal_acara')->nullable();
                $table->date('tanggal_acara_selesai')->nullable();
                $table->time('waktu_acara')->nullable();
                $table->string('lokasi_acara')->nullable();

                $table->text('catatan')->nullable();
                $table->string('file_path')->nullable();
                $table->string('status')->default('Aktif');

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_jalans');
    }
};
