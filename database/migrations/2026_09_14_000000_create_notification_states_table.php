<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 4 jenis notifikasi "otomatis" (password_reset_request, report_ready,
 * unpaid_deadline, finance_missing - lihat NotificationService) BUKAN
 * baris tersimpan - dihitung ulang dari data yang masih hidup setiap
 * request. Jadi tidak ada baris asli yang bisa "disematkan"/"dihapus"
 * seperti Pengumuman (tabel `notifications`).
 *
 * Tabel ini menyimpan preferensi terhadap 1 kemunculan notifikasi
 * otomatis tertentu, dikunci lewat `notification_key` - string stabil
 * yang SUDAH dihasilkan tiap builder di NotificationService (contoh:
 * "unpaid-42" untuk notifikasi Project id 42 yang belum lunas,
 * "report-15" untuk ReportExport id 15). Bukan tabel baru untuk
 * notifikasi itu sendiri - notifikasi tetap dihitung fresh seperti biasa.
 *
 * `pinned_at` = preferensi PRIBADI (dicek scoped ke user_id). `dismissed_at`
 * = penghapusan GLOBAL (dicek TANPA filter user_id, lihat
 * NotificationService::applyUserNotificationStates()) - user_id di baris
 * dismissed_at cuma mencatat SIAPA yang menghapus, bukan pembatas siapa
 * yang tidak lagi melihatnya.
 *
 * KETERBATASAN YANG DISENGAJA (bukan bug, dokumentasikan supaya jelas):
 * karena notification_key terpasang ke SATU record (misal Project #42),
 * bukan ke "kejadian" tertentu - kalau user menghapus notifikasi ini
 * saat project #42 belum lunas, LALU project itu lunas (notifikasi
 * hilang wajar), LALU entah bagaimana jadi belum lunas lagi (jarang,
 * tapi mungkin kalau data keuangan dihapus manual) - notifikasi utang
 * yang baru itu akan tetap tersembunyi karena masih pakai kunci yang
 * sama ("unpaid-42"). Diterima sebagai trade-off sederhana, bukan
 * dianggap kasus yang cukup sering terjadi untuk butuh desain lebih
 * rumit (misal ikut sertakan timestamp/hash kondisi ke dalam kuncinya).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('notification_key');
            $table->timestamp('pinned_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'notification_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_states');
    }
};
