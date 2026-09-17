<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahan KHUSUS untuk TessisB pada tabel `notifications` bawaan
 * Laravel - kolom ini TIDAK ada di skema standar framework, jadi aman
 * ditambah tanpa mengganggu perilaku inti Notifiable/DatabaseNotification
 * (Laravel cuma pernah membaca/menulis kolom id/type/notifiable/data/
 * read_at/timestamps, tidak pernah menyentuh kolom ini).
 *
 * Notifikasi yang disematkan (pinned_at terisi) TIDAK ikut kena batas
 * "24 jam terakhir" pada halaman Notifikasi - itulah tujuan sematkan:
 * supaya tetap terlihat lebih lama dari batas normal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->timestamp('pinned_at')->nullable()->after('read_at');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('pinned_at');
        });
    }
};
