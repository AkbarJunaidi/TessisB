<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel pelacak proses generate laporan PDF di background (queue) -
     * dipakai supaya proses berat (mis. Laporan Massal Seluruh Inventaris)
     * tidak lagi dikerjakan langsung di dalam HTTP request (berisiko timeout
     * kalau data sudah banyak), melainkan diantrekan lewat Job dan hasilnya
     * dicek/diunduh lewat baris ini.
     */
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table) {

            $table->id();

            // Jenis laporan - dibuat generik supaya bisa dipakai jenis laporan
            // lain di masa depan (bukan cuma "inventory_all").
            $table->string('type');

            // processing -> masih diproses Job
            // completed  -> siap diunduh, file_path terisi
            // failed     -> gagal, error_message terisi
            $table->string('status')->default('processing');

            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable();

            $table->foreignId('requested_by')
                ->constrained('users')
                ->cascadeOnDelete();

            // Diisi saat file benar-benar diunduh - dipakai supaya notifikasi
            // "Laporan Siap Diunduh" otomatis hilang setelah diunduh.
            $table->timestamp('downloaded_at')->nullable();

            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
