<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahan untuk halaman Detail Kontak & card Kontak:
     * - has_whatsapp: toggle "Punya WhatsApp" (menentukan tombol WA tampil
     *   atau tidak di card, terpisah dari No. HP/WA itu sendiri - nomor
     *   telepon belum tentu nomor WhatsApp)
     * - notes: catatan bebas tentang kontak ini
     * - phone jadi opsional (nullable) - supaya tombol Telepon di card bisa
     *   ikut disembunyikan kalau memang belum diisi, sama seperti Email
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'has_whatsapp')) {
                $table->boolean('has_whatsapp')->default(false)->after('phone');
            }
            if (!Schema::hasColumn('contacts', 'notes')) {
                $table->text('notes')->nullable()->after('address');
            }
        });

        // phone -> nullable. Pakai raw SQL (bukan ->nullable()->change())
        // karena package doctrine/dbal belum terpasang di project ini.
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE contacts MODIFY phone VARCHAR(255) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE contacts ALTER COLUMN phone DROP NOT NULL');
        }
        // sqlite: dilewati (constraint NOT NULL bawaan SQLite tidak
        // sederhana diubah lewat ALTER TABLE biasa) - tidak dipakai di
        // environment project ini (project ini pakai MySQL).
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'has_whatsapp')) {
                $table->dropColumn('has_whatsapp');
            }
            if (Schema::hasColumn('contacts', 'notes')) {
                $table->dropColumn('notes');
            }
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("UPDATE contacts SET phone = '' WHERE phone IS NULL");
            DB::statement('ALTER TABLE contacts MODIFY phone VARCHAR(255) NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement("UPDATE contacts SET phone = '' WHERE phone IS NULL");
            DB::statement('ALTER TABLE contacts ALTER COLUMN phone SET NOT NULL');
        }
    }
};
