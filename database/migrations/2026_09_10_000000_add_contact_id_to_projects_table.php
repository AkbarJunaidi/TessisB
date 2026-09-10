<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link ASLI (foreign key) dari Project ke Contact - dipakai fitur
     * autocomplete di field Client saat buat/edit Project. Nullable
     * karena Project tetap boleh diisi client bebas teks (tidak dipilih
     * dari Kontak) - link ini cuma terisi kalau user benar-benar memilih
     * salah satu saran dari daftar Kontak.
     *
     * onDelete('set null'): kalau Kontak-nya dihapus, Project TIDAK ikut
     * terhapus - cuma link-nya lepas, project.client (teks) tetap ada.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'contact_id')) {
                $table->foreignId('contact_id')
                    ->nullable()
                    ->after('client')
                    ->constrained('contacts')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'contact_id')) {
                $table->dropConstrainedForeignId('contact_id');
            }
        });
    }
};
