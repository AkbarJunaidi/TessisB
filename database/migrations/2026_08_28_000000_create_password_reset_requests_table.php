<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menjalankan proses pembuatan tabel password_reset_requests.
     *
     * Tabel ini MENYIMPAN status (bukan sekadar mengirim email) karena alur
     * "Lupa Password" di project ini bukan reset link via email, melainkan
     * permintaan yang perlu ditindaklanjuti manual oleh Super Admin lewat
     * halaman User Management. Satu baris = satu permintaan.
     */
    public function up(): void
    {
        Schema::create('password_reset_requests', function (Blueprint $table) {

            $table->id();

            // User yang emailnya cocok saat mengisi form Lupa Password.
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Snapshot email yang diinput saat request dibuat (histori,
            // tidak berubah walau email user diganti setelahnya).
            $table->string('email');

            // pending -> baru diajukan, belum ditindaklanjuti.
            // resolved -> sudah ditindaklanjuti (password direset Super Admin).
            $table->enum('status', ['pending', 'resolved'])->default('pending');

            // Super Admin yang menindaklanjuti (menyelesaikan) permintaan ini.
            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            // Query utama: cari permintaan pending milik user tertentu.
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Menghapus tabel password_reset_requests.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_requests');
    }
};
