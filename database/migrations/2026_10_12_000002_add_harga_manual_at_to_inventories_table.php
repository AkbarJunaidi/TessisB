<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Kapan harga manual terakhir diisi atau diubah; ditampilkan di Daftar Harga Barang. */
    public function up(): void
    {
        if (!Schema::hasColumn('inventories', 'harga_manual_at')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->timestamp('harga_manual_at')->nullable()->after('harga_manual');
            });

            // Harga yang sudah terisi belum punya riwayat tanggal; pakai waktu ubah terakhir barang sebagai perkiraan.
            DB::table('inventories')->whereNotNull('harga_manual')->update(['harga_manual_at' => DB::raw('updated_at')]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventories', 'harga_manual_at')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->dropColumn('harga_manual_at');
            });
        }
    }
};
