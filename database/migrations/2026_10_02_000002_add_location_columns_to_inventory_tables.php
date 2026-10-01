<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - inventory_units     : lokasi utama (tempat simpan) dan lokasi sekarang tiap unit.
     *                         Posisi unit yang sedang dipinjam lewat Surat Jalan tetap dihitung dari
     *                         surat_jalan_item_id ("di lapangan"), bukan dari kolom ini.
     * - inventory_mutations : jejak kejadian "pindah_lokasi" (asal, tujuan, cara penentuan, koordinat).
     */
    public function up(): void
    {
        Schema::table('inventory_units', function (Blueprint $table) {
            $table->foreignId('lokasi_utama_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('lokasi_sekarang_id')->nullable()->constrained('locations')->nullOnDelete();
        });

        Schema::table('inventory_mutations', function (Blueprint $table) {
            $table->foreignId('lokasi_asal_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('lokasi_tujuan_id')->nullable()->constrained('locations')->nullOnDelete();

            // gps | manual | luar_jangkauan | akurasi_rendah (hanya untuk event pindah_lokasi)
            $table->string('lokasi_metode', 20)->nullable();
            $table->decimal('lokasi_lat', 10, 7)->nullable();
            $table->decimal('lokasi_lng', 10, 7)->nullable();
            $table->unsignedInteger('lokasi_akurasi_m')->nullable();
        });

        // Backfill: semua unit yang sudah ada ditempatkan di lokasi awal.
        $defaultId = DB::table('locations')->where('is_default', true)->value('id');

        if ($defaultId) {
            DB::table('inventory_units')
                ->whereNull('lokasi_utama_id')
                ->update([
                    'lokasi_utama_id'    => $defaultId,
                    'lokasi_sekarang_id' => $defaultId,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('inventory_mutations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lokasi_asal_id');
            $table->dropConstrainedForeignId('lokasi_tujuan_id');
            $table->dropColumn(['lokasi_metode', 'lokasi_lat', 'lokasi_lng', 'lokasi_akurasi_m']);
        });

        Schema::table('inventory_units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lokasi_utama_id');
            $table->dropConstrainedForeignId('lokasi_sekarang_id');
        });
    }
};
