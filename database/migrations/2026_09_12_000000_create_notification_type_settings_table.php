<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel kecil untuk Super Admin mengatur jenis-jenis notifikasi OTOMATIS
 * (dihitung dari data, lihat NotificationService) - mana yang aktif
 * ditampilkan di navbar, dan urutannya. Beda dari tabel `notifications`
 * (isi pesan sungguhan per-user) - tabel ini cuma metadata/preferensi
 * per JENIS notifikasi, cuma 5 baris seterusnya (tidak bertambah per
 * user/kejadian).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_type_settings', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed 5 jenis yang sudah ada saat ini, urutan default = urutan
        // yang sudah berjalan sekarang (lihat NotificationService).
        // Kalau nanti nambah jenis baru di kode, tidak WAJIB nambah
        // migration baru lagi - NotificationService sudah didesain fallback
        // "enabled=true, tampil di akhir" untuk jenis yang belum ada
        // baris settingnya (lihat NotificationService::getTypeConfig()).
        $now = now();
        $defaults = [
            ['type' => 'password_reset_request', 'sort_order' => 0],
            ['type' => 'report_ready', 'sort_order' => 1],
            ['type' => 'unpaid_deadline', 'sort_order' => 2],
            ['type' => 'finance_missing', 'sort_order' => 3],
            ['type' => 'announcement', 'sort_order' => 4],
        ];

        foreach ($defaults as $row) {
            \DB::table('notification_type_settings')->insert([
                'type'       => $row['type'],
                'enabled'    => true,
                'sort_order' => $row['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_type_settings');
    }
};
