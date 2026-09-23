<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dukung "Surat Jalan mini" (peminjaman langsung lewat fitur Scan, TANPA
 * Project - referensinya akun yang scan). project_id jadi nullable (null =
 * peminjaman langsung), dipinjam_oleh_user_id diisi HANYA untuk kasus itu.
 * Lihat SuratJalan::isPeminjamanLangsung()/referensiLabel().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_jalans', function (Blueprint $table) {
            if (!Schema::hasColumn('surat_jalans', 'dipinjam_oleh_user_id')) {
                $table->foreignId('dipinjam_oleh_user_id')->nullable()->after('project_id')
                    ->constrained('users')->nullOnDelete();
            }
        });

        // project_id -> nullable. Raw SQL per driver (bukan ->nullable()->change())
        // karena package doctrine/dbal belum terpasang di project ini - pola sama
        // seperti migration add_whatsapp_notes_to_contacts_table.
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE surat_jalans MODIFY project_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE surat_jalans ALTER COLUMN project_id DROP NOT NULL');
        }
        // sqlite: dilewati, tidak dipakai di environment project ini (pakai MySQL).
    }

    public function down(): void
    {
        Schema::table('surat_jalans', function (Blueprint $table) {
            if (Schema::hasColumn('surat_jalans', 'dipinjam_oleh_user_id')) {
                $table->dropConstrainedForeignId('dipinjam_oleh_user_id');
            }
        });

        // project_id sengaja TIDAK dikembalikan ke NOT NULL - kalau sudah ada
        // baris peminjaman langsung (project_id null), tidak ada nilai Project
        // yang valid untuk diisikan otomatis di sini.
    }
};
